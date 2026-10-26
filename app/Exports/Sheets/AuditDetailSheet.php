<?php

namespace App\Exports\Sheets;

use App\Models\Audit;
use App\Models\AuditResult;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Lembar detail satu/lebih kategori audit — berisi seluruh baris data
 * (bukan sekadar 100 baris seperti tampilan layar), dibaca per giliran
 * dari 1.000 baris agar memori tetap aman pada dataset besar.
 */
class AuditDetailSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithColumnWidths
{
    /** Label tampilan status audit berbahasa Indonesia. */
    private const LABEL_STATUS = [
        'match_full'       => 'Match Full',
        'tahun_beda'       => 'Tahun Beda',
        'fuzzy_match'      => 'Fuzzy Match',
        'pusat_tidak_ada'  => 'Pusat Tidak Ada',
        'cabang_tidak_ada' => 'Cabang Tidak Ada',
        'duplikat_pusat'   => 'Duplikat (Pusat)',
        'duplikat_cabang'  => 'Duplikat (Cabang)',
    ];

    public function __construct(
        private AuditResult $hasil,
        private string $judul,
        private array $status,
    ) {
    }

    /** Ukuran peladenan data per giliran (dibaca langsung oleh Laravel Excel). */
    public $chunkSize = 1000;

    public function title(): string
    {
        return $this->judul;
    }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return Audit::query()
            ->with([
                'dataMakam:id,nama,id_match,sumber',
                'matchedData:id,nama',
            ])
            ->where('audit_result_id', $this->hasil->id)
            ->whereIn('status', $this->status)
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Nama',
            'ID Match',
            'Sumber',
            'Dipasangkan Dengan',
            'Skor Kemiripan (%)',
            'Jarak Levenshtein',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->dataMakam?->nama,
            $row->dataMakam?->id_match,
            $row->dataMakam?->sumber === 'pusat' ? 'Pusat' : 'Cabang',
            $row->matchedData?->nama,
            $row->similarity_score,
            $row->levenshtein_distance,
            self::LABEL_STATUS[$row->status] ?? $row->status,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 26,
            'C' => 10,
            'D' => 40,
            'E' => 16,
            'F' => 16,
            'G' => 18,
        ];
    }
}
