<?php

namespace App\Exports\Sheets;

use App\Models\AuditResult;
use App\Models\DataMakam;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Lembar ringkasan: identitas audit, angka tiap kategori,
 * dan tingkat sinkronisasi — muat untuk lampiran rapat/manajemen.
 */
class AuditRingkasanSheet implements FromArray, WithTitle, WithColumnWidths
{
    public function __construct(private AuditResult $hasil)
    {
    }

    public function title(): string
    {
        return 'Ringkasan';
    }

    public function array(): array
    {
        $tpu = $this->hasil->tpu;

        $totalPusat = DataMakam::where('tpu_id', $this->hasil->tpu_id)
            ->where('sumber', 'pusat')
            ->count();

        $totalCabang = DataMakam::where('tpu_id', $this->hasil->tpu_id)
            ->where('sumber', 'cabang')
            ->count();

        $persenSinkron = $totalPusat > 0
            ? round(($this->hasil->total_match / $totalPusat) * 100, 1)
            : 0;

        return [
            ['LAPORAN AUDIT DATA PEMAKAMAN'],
            ['TPU', $tpu->nama],
            ['Kode TPU', $tpu->kode],
            ['Tanggal Audit', $this->hasil->created_at->format('d/m/Y H:i')],
            ['Data Pusat (Dinas)', $totalPusat],
            ['Data Cabang (TPU)', $totalCabang],
            ['Total Data', $totalPusat + $totalCabang],
            [],
            ['Kategori', 'Jumlah'],
            ['Match Full', $this->hasil->total_match],
            ['Tahun Beda', $this->hasil->total_tahun_beda],
            ['Fuzzy Match', $this->hasil->total_fuzzy_match],
            ['Pusat Tidak Ada', $this->hasil->total_pusat_tidak_ada],
            ['Cabang Tidak Ada', $this->hasil->total_cabang_tidak_ada],
            ['Duplikat Pusat', $this->hasil->total_duplikat_pusat],
            ['Duplikat Cabang', $this->hasil->total_duplikat_cabang],
            [],
            ['Tingkat Sinkronisasi', $persenSinkron . '%'],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 34, 'B' => 18];
    }
}
