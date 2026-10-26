<?php

namespace App\Exports;

use App\Exports\Sheets\AuditDetailSheet;
use App\Exports\Sheets\AuditRingkasanSheet;
use App\Models\AuditResult;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Ekspor satu hasil audit lengkap menjadi berkas Excel multi-sheet:
 * lembar pertama berisi ringkasan, sisanya detail per kategori audit.
 */
class AuditResultExport implements WithMultipleSheets
{
    public function __construct(private AuditResult $hasil)
    {
    }

    public function sheets(): array
    {
        return [
            new AuditRingkasanSheet($this->hasil),
            new AuditDetailSheet($this->hasil, 'Match Full', ['match_full']),
            new AuditDetailSheet($this->hasil, 'Tahun Beda', ['tahun_beda']),
            new AuditDetailSheet($this->hasil, 'Fuzzy Match', ['fuzzy_match']),
            new AuditDetailSheet($this->hasil, 'Pusat Tidak Ada', ['pusat_tidak_ada']),
            new AuditDetailSheet($this->hasil, 'Cabang Tidak Ada', ['cabang_tidak_ada']),
            new AuditDetailSheet($this->hasil, 'Duplikat', ['duplikat_pusat', 'duplikat_cabang']),
        ];
    }
}
