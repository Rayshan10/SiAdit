<?php

namespace App\Services;

use App\Models\DataMakam;
use App\Models\Audit;
use App\Models\AuditResult;
use Illuminate\Support\Facades\DB;

class AuditService
{
    /**
     * Batas minimum kemiripan nama (0-100, persen similar_text)
     * agar dua baris dianggap Fuzzy Match.
     */
    public const FUZZY_MIN_SIMILARITY = 90;

    /**
     * Batas maksimum jarak Levenshtein untuk Fuzzy Match.
     */
    public const FUZZY_MAX_DISTANCE = 3;

    public function generate(int $tpuId): AuditResult
    {
        /*
        |--------------------------------------------------------------------------
        | TRANSAKSI: SEMUA ATAU TIDAK SAMA-SEKALI
        |--------------------------------------------------------------------------
        | Reset hasil lama + generate hasil baru dalam satu transaksi agar
        | data audit tidak pernah berada dalam kondisi "setengah jadi".
        */
        return DB::transaction(
            function () use ($tpuId) {

                $this->resetHasilLama($tpuId);

                $pusat = DataMakam::where('tpu_id', $tpuId)->where('sumber', 'pusat')->get();
                $cabang = DataMakam::where('tpu_id', $tpuId)->where('sumber', 'cabang')->get();

                $auditRows = [];
                $now = now();

                // Data pusat tetap mengikut sertakan baris duplikat dalam proses
                // matching (semantik lama dipertahankan), duplikatnya sendiri
                // ditandai lewat status duplikat_pusat.
                $totalDuplikatPusat = $this->deteksiDuplikat(
                    $pusat,
                    $auditRows,
                    $now,
                    'duplikat_pusat'
                );

                $totalDuplikatCabang = $this->deteksiDuplikat(
                    $cabang,
                    $auditRows,
                    $now,
                    'duplikat_cabang'
                );

                $pusatUnik = $pusat->values();

                /*
                |--------------------------------------------------------------------------
                | KAMUS DATA CABANG (VLOOKUP PREPARATION)
                |--------------------------------------------------------------------------
                */
                $cabangDictById = [];
                $cabangDictByNama = [];

                // Kandidat fuzzy dikelompokkan berdasarkan PANJANG nama,
                // karena Levenshtein <= 3 hanya mungkin terjadi antara dua nama
                // yang panjangnya tidak beda lebih dari 3 karakter. Ini memangkas
                // jumlah perbandingan dari n x m menjadi jauh lebih kecil.
                $cabangDictByPanjang = [];

                foreach ($cabang as $c) {
                    $idM = CleaningService::softNormalize($c->id_match);
                    $nama = CleaningService::softNormalize($c->nama_clean);

                    if (!isset($cabangDictById[$idM])) {
                        $cabangDictById[$idM] = [];
                    }

                    $cabangDictById[$idM][] = $c;

                    if (!isset($cabangDictByNama[$nama])) {
                        $cabangDictByNama[$nama] = [];
                    }

                    $cabangDictByNama[$nama][] = $c;

                    $panjang = mb_strlen($nama);
                    $cabangDictByPanjang[$panjang][] = $c;
                }

                /*
                |--------------------------------------------------------------------------
                | TAHAP 2: REKONSILIASI PUSAT VS CABANG
                |--------------------------------------------------------------------------
                */
                $totalMatch = 0;
                $totalTahunBeda = 0;
                $totalFuzzyMatch = 0;
                $totalPusatTidakAda = 0;
                $totalCabangTidakAda = 0;

                // Mencatat data cabang mana saja yang sudah mendapat pasangan
                $cabangUsedIds = [];

                foreach ($pusatUnik as $p) {
                    $idM = CleaningService::softNormalize($p->id_match);
                    $nama = CleaningService::softNormalize($p->nama_clean);

                    // Cek 1: MATCH FULL (id_match sama persis)
                    if (!empty($cabangDictById[$idM])) {
                        $cMatch = array_shift($cabangDictById[$idM]);

                        $auditRows[] = $this->barisAudit(
                            $p->id,
                            $cMatch->id,
                            100,
                            0,
                            'match_full',
                            'ID Match sama',
                            $now
                        );

                        $totalMatch++;
                        $cabangUsedIds[$cMatch->id] = true;

                        // Hapus dari kamus nama agar tidak diklaim lagi oleh Tahun Beda
                        if (isset($cabangDictByNama[$nama])) {
                            foreach ($cabangDictByNama[$nama] as $idx => $cItem) {
                                if ($cItem->id === $cMatch->id) {
                                    unset($cabangDictByNama[$nama][$idx]);
                                    break;
                                }
                            }
                        }
                    }
                    // Cek 2: TAHUN BEDA (id_match beda, tapi nama_clean persis sama)
                    elseif (!empty($cabangDictByNama[$nama])) {
                        $cMatch = array_shift($cabangDictByNama[$nama]);

                        $auditRows[] = $this->barisAudit(
                            $p->id,
                            $cMatch->id,
                            100,
                            0,
                            'tahun_beda',
                            'Nama sama, tahun berbeda',
                            $now
                        );

                        $totalTahunBeda++;
                        $cabangUsedIds[$cMatch->id] = true;
                    }
                    // Cek 3: CARI KANDIDAT TERMIRIP (FUZZY MATCH)
                    else {
                        [$bestMatch, $bestSimilarity, $bestDistance] = $this->cariKandidatTermirip(
                            $nama,
                            $cabangDictByPanjang,
                            $cabangUsedIds
                        );

                        if (
                            $bestMatch !== null
                            && $bestSimilarity >= self::FUZZY_MIN_SIMILARITY
                            && $bestDistance <= self::FUZZY_MAX_DISTANCE
                        ) {
                            $auditRows[] = $this->barisAudit(
                                $p->id,
                                $bestMatch->id,
                                $bestSimilarity,
                                $bestDistance,
                                'fuzzy_match',
                                "Kemiripan {$bestSimilarity}% (jarak {$bestDistance})",
                                $now
                            );

                            $totalFuzzyMatch++;
                            $cabangUsedIds[$bestMatch->id] = true;

                            continue;
                        }

                        // Tidak ditemukan: data pusat tidak ada di cabang
                        $auditRows[] = $this->barisAudit(
                            $p->id,
                            null,
                            null,
                            null,
                            'pusat_tidak_ada',
                            null,
                            $now
                        );

                        $totalPusatTidakAda++;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | TAHAP 3: CABANG TIDAK ADA DI PUSAT
                |--------------------------------------------------------------------------
                | Sisa data cabang yang tidak pernah mendapat pasangan
                */
                foreach ($cabang as $c) {
                    if (!isset($cabangUsedIds[$c->id])) {
                        $auditRows[] = $this->barisAudit(
                            $c->id,
                            null,
                            null,
                            null,
                            'cabang_tidak_ada',
                            null,
                            $now
                        );

                        $totalCabangTidakAda++;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | SIMPAN HASIL & BULK INSERT (chunked, hemat memori)
                |--------------------------------------------------------------------------
                */
                $auditResult = AuditResult::create([
                    'tpu_id'                 => $tpuId,
                    'total_match'            => $totalMatch,
                    'total_tahun_beda'       => $totalTahunBeda,
                    'total_fuzzy_match'      => $totalFuzzyMatch,
                    'total_pusat_tidak_ada'  => $totalPusatTidakAda,
                    'total_cabang_tidak_ada' => $totalCabangTidakAda,
                    'total_duplikat_pusat'   => $totalDuplikatPusat,
                    'total_duplikat_cabang'  => $totalDuplikatCabang,
                ]);

                foreach ($auditRows as &$row) {
                    $row['audit_result_id'] = $auditResult->id;
                }

                foreach (array_chunk($auditRows, 1000) as $chunk) {
                    Audit::insert($chunk);
                }

                return $auditResult;
            }
        );
    }

    /**
     * Hapus hasil audit lama untuk sebuah TPU.
     */
    private function resetHasilLama(int $tpuId): void
    {
        AuditResult::where('tpu_id', $tpuId)
            ->select('id')
            ->chunkById(1000, function ($results) {
                Audit::whereIn('audit_result_id', $results->pluck('id'))->delete();
            });

        AuditResult::where('tpu_id', $tpuId)->delete();
    }

    /**
     * Deteksi duplikat (baris dengan id_match yang muncul lebih dari sekali)
     * — setara COUNTIF di Excel — lalu tambahkan ke array baris audit.
     */
    private function deteksiDuplikat($rows, array &$auditRows, $now, string $status): int
    {
        $frekuensi = [];
        $total = 0;

        foreach ($rows as $row) {
            $idM = CleaningService::softNormalize($row->id_match);

            if (!isset($frekuensi[$idM])) {
                $frekuensi[$idM] = 0;
            }

            $frekuensi[$idM]++;
        }

        foreach ($rows as $row) {
            $idM = CleaningService::softNormalize($row->id_match);

            if ($frekuensi[$idM] > 1) {
                $auditRows[] = [
                    'audit_result_id'      => 0,
                    'data_makam_id'        => $row->id,
                    'matched_with'         => null,
                    'similarity_score'     => null,
                    'levenshtein_distance' => null,
                    'status'               => $status,
                    'keterangan'           => null,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];

                $total++;
            }
        }

        return $total;
    }

    /**
     * Cari kandidat cabang yang paling mirip dengan sebuah nama.
     *
     * Optimasi performa:
     * 1. Hanya membandingkan kandidat yang panjangnya tidak lebih beda dari
     *    FUZZY_MAX_DISTANCE — syarat mutlak bagi Levenshtein <= 3.
     * 2. Levenshtein (murah) dihitung lebih dulu; similar_text hanya jalan
     *    bila jaraknya sudah lolos batas.
     *
     * @return array{0: ?DataMakam, 1: float, 2: int}
     */
    private function cariKandidatTermirip(string $nama, array $cabangDictByPanjang, array $cabangUsedIds): array
    {
        $bestMatch = null;
        $bestSimilarity = 0;
        $bestDistance = PHP_INT_MAX;

        $panjangNama = mb_strlen($nama);

        for (
            $panjang = max(1, $panjangNama - self::FUZZY_MAX_DISTANCE);
            $panjang <= $panjangNama + self::FUZZY_MAX_DISTANCE;
            $panjang++
        ) {
            $kandidats = $cabangDictByPanjang[$panjang] ?? [];

            foreach ($kandidats as $candidate) {
                if (isset($cabangUsedIds[$candidate->id])) {
                    continue;
                }

                $candidateName = CleaningService::softNormalize($candidate->nama_clean);

                // Urutan ukur: jarak lebih dulu (murah), kemiripan kemudian
                $distance = levenshtein($nama, $candidateName);

                if ($distance > self::FUZZY_MAX_DISTANCE) {
                    continue;
                }

                similar_text($nama, $candidateName, $percent);
                $percent = round($percent, 2);

                if ($percent > $bestSimilarity) {
                    $bestSimilarity = $percent;
                    $bestDistance = $distance;
                    $bestMatch = $candidate;
                }
            }
        }

        return [$bestMatch, $bestSimilarity, $bestDistance];
    }

    /**
     * Baris audit standar supaya bentuk datanya konsisten di semua cabang.
     */
    private function barisAudit(
        int $dataMakamId,
        ?int $matchedWith,
        ?float $similarity,
        ?int $distance,
        string $status,
        ?string $keterangan,
        $now
    ): array {
        return [
            'audit_result_id'      => 0,
            'data_makam_id'        => $dataMakamId,
            'matched_with'         => $matchedWith,
            'similarity_score'     => $similarity,
            'levenshtein_distance' => $distance,
            'status'               => $status,
            'keterangan'           => $keterangan,
            'created_at'           => $now,
            'updated_at'           => $now,
        ];
    }
}
