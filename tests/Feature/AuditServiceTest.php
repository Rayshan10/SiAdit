<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\AuditResult;
use App\Models\DataMakam;
use App\Models\Tpu;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tpu $tpu;

    private AuditResult $hasil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tpu = Tpu::create([
            'nama' => 'TPU Pandu',
            'kode' => 'TPU-PANDU',
        ]);

        /*
         | Skenario lengkap, masing-masing kategori audit terwakili:
         |
         | pusat P1 AHMAD S 2020     -> match_full    (A)
         | pusat P2 BUDI S 2019      -> tahun_beda    (B tahunnya 2018)
         | pusat P3 CICI   2021      -> tahun_beda    (D tahunnya 2022)
         | pusat P4 CICI   2021(dup) -> pusat_tidak_ada
         | pusat P5 AHMAD SA 2020    -> fuzzy_match   (C "AHMAD SAA", jarak 1)
         | pusat P6 DUDI   2020      -> pusat_tidak_ada
         | cabang E ZAKI 2020        -> cabang_tidak_ada
         */
        $pusat = [
            ['AHMAD S', '2020-05-01', 'AHMAD S-2020'],
            ['BUDI S', '2019-06-14', 'BUDI S-2019'],
            ['CICI', '2021-07-20', 'CICI-2021'],
            ['CICI', '2021-07-20', 'CICI-2021'],
            ['AHMAD SA', '2020-08-02', 'AHMAD SA-2020'],
            ['DUDI', '2020-03-30', 'DUDI-2020'],
        ];

        $cabang = [
            ['AHMAD S', '2020-05-01', 'AHMAD S-2020'],
            ['BUDI S', '2018-06-14', 'BUDI S-2018'],
            ['AHMAD SAA', '2020-08-02', 'AHMAD SAA-2020'],
            ['CICI', '2022-07-20', 'CICI-2022'],
            ['ZAKI', '2020-11-11', 'ZAKI-2020'],
        ];

        foreach ($pusat as [$nama, $tanggal, $idMatch]) {
            DataMakam::create([
                'tpu_id' => $this->tpu->id,
                'import_id' => 1,
                'sumber' => 'pusat',
                'nama' => $nama,
                'tanggal_raw' => $tanggal,
                'nama_clean' => $nama,
                'tanggal_clean' => $tanggal,
                'id_match' => $idMatch,
            ]);
        }

        foreach ($cabang as [$nama, $tanggal, $idMatch]) {
            DataMakam::create([
                'tpu_id' => $this->tpu->id,
                'import_id' => 2,
                'sumber' => 'cabang',
                'nama' => $nama,
                'tanggal_raw' => $tanggal,
                'nama_clean' => $nama,
                'tanggal_clean' => $tanggal,
                'id_match' => $idMatch,
            ]);
        }

        $this->hasil = app(AuditService::class)->generate($this->tpu->id);
    }

    /** @test */
    public function hasil_ringkasan_sesuai_skenario(): void
    {
        $this->assertSame(1, $this->hasil->total_match);
        $this->assertSame(2, $this->hasil->total_tahun_beda);
        $this->assertSame(1, $this->hasil->total_fuzzy_match);
        $this->assertSame(2, $this->hasil->total_pusat_tidak_ada);
        $this->assertSame(1, $this->hasil->total_cabang_tidak_ada);
        $this->assertSame(2, $this->hasil->total_duplikat_pusat);
        $this->assertSame(0, $this->hasil->total_duplikat_cabang);
    }

    /** @test */
    public function jumlah_baris_per_status_cocok_dengan_ringkasan(): void
    {
        foreach (
            [
                'match_full'       => 1,
                'tahun_beda'       => 2,
                'fuzzy_match'      => 1,
                'pusat_tidak_ada'  => 2,
                'cabang_tidak_ada' => 1,
                'duplikat_pusat'   => 2,
                'duplikat_cabang'  => 0,
            ] as $status => $jumlah
        ) {
            $this->assertSame(
                $jumlah,
                Audit::where('audit_result_id', $this->hasil->id)->where('status', $status)->count(),
                "Jumlah baris status '{$status}' tidak sesuai."
            );
        }
    }

    /** @test */
    public function baris_fuzzy_menyimpan_skor_kemiripan_dan_jarak(): void
    {
        $fuzzy = Audit::where('audit_result_id', $this->hasil->id)
            ->where('status', 'fuzzy_match')
            ->first();

        $this->assertNotNull($fuzzy);
        $this->assertNotNull($fuzzy->dataMakam);
        $this->assertSame('AHMAD SA', $fuzzy->dataMakam->nama_clean);

        // "AHMAD SA" vs "AHMAD SAA": jarak Levenshtein 1
        $this->assertSame(1, $fuzzy->levenshtein_distance);
        $this->assertGreaterThanOrEqual(90, $fuzzy->similarity_score);

        // Dipasangkan dengan cabang "AHMAD SAA"
        $this->assertNotNull($fuzzy->matchedData);
        $this->assertSame('AHMAD SAA', $fuzzy->matchedData->nama_clean);
    }

    /** @test */
    public function menjalankan_ulang_audit_mereset_hasil_lama(): void
    {
        $idLama = $this->hasil->id;

        app(AuditService::class)->generate($this->tpu->id);

        $this->assertDatabaseMissing('audit_results', ['id' => $idLama]);
        $this->assertDatabaseCount('audit_results', 1);
        $this->assertDatabaseCount('audits', 9);
    }

    /**
     * Regresi: satu baris cabang tidak boleh dipakai dua kali.
     *
     * Dulu baris cabang yang sudah diklaim lewat tahun_beda masih tertinggal
     * di kamus id/nama sehingga bisa diklaim ulang baris pusat lain lewat
     * jalur match_full (57 baris terdampak pada data nyata TPU Pandu).
     */
    public function test_satu_baris_cabang_tidak_dipakai_dua_kali(): void
    {
        $tpu = Tpu::create([
            'nama' => 'TPU Uji Booking Ganda',
            'kode' => 'TPU-BOOKING-GANDA',
        ]);

        /*
         | pusat P1 BUDI S-2020 -> tahun_beda   (klaim C "BUDI S-2021")
         | pusat P2 BUDI S-2021 -> pusat_tidak_ada (C sudah terpakai)
         */
        foreach ([
            ['BUDI S', '2020-05-01', 'BUDI S-2020'],
            ['BUDI S', '2021-05-01', 'BUDI S-2021'],
        ] as [$nama, $tanggal, $idMatch]) {
            DataMakam::create([
                'tpu_id' => $tpu->id,
                'import_id' => 1,
                'sumber' => 'pusat',
                'nama' => $nama,
                'tanggal_raw' => $tanggal,
                'nama_clean' => $nama,
                'tanggal_clean' => $tanggal,
                'id_match' => $idMatch,
            ]);
        }

        DataMakam::create([
            'tpu_id' => $tpu->id,
            'import_id' => 2,
            'sumber' => 'cabang',
            'nama' => 'BUDI S',
            'tanggal_raw' => '2021-05-01',
            'nama_clean' => 'BUDI S',
            'tanggal_clean' => '2021-05-01',
            'id_match' => 'BUDI S-2021',
        ]);

        $hasilAudit = app(AuditService::class)->generate($tpu->id);

        $this->assertSame(0, $hasilAudit->total_match);
        $this->assertSame(1, $hasilAudit->total_tahun_beda);
        $this->assertSame(1, $hasilAudit->total_pusat_tidak_ada);
        $this->assertSame(0, $hasilAudit->total_cabang_tidak_ada);

        $query = Audit::where('audit_result_id', $hasilAudit->id)
            ->whereIn('status', ['match_full', 'tahun_beda', 'fuzzy_match']);

        // Tiap baris cabang hanya pernah jadi pasangan satu kali
        $this->assertSame(
            (clone $query)->count(),
            (clone $query)->distinct()->count('matched_with'),
            'Ada baris cabang yang dipakai lebih dari satu kali.'
        );
    }
}
