<?php

namespace Tests\Unit;

use App\Services\MatchingService;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Tests\TestCase;

class MatchingServiceTest extends TestCase
{
    public function test_id_match_dibentuk_nama_tahun(): void
    {
        $this->assertSame(
            'AHMAD SUBARJO-2020',
            MatchingService::generateIdMatch('AHMAD SUBARJO', '2020-05-12')
        );
    }

    public function test_tanggal_kosong_berniali_tahun_unknown(): void
    {
        $this->assertSame(
            'AHMAD SUBARJO-0000',
            MatchingService::generateIdMatch('AHMAD SUBARJO', null)
        );
    }

    public function test_serial_number_excel_dikonversi_ke_tahun(): void
    {
        // 2021-01-01 => 44197
        $tahun = ExcelDate::excelToDateTimeObject(44197)->format('Y');

        $this->assertSame(
            $tahun,
            MatchingService::extractYear(44197)
        );
    }

    public function test_format_teks_dengan_tahun_dikenali(): void
    {
        $this->assertSame('2019', MatchingService::extractYear('12/05/2019'));
        $this->assertSame('2007', MatchingService::extractYear("'07"));
        $this->assertSame('1989', MatchingService::extractYear("'89"));
        $this->assertSame('0000', MatchingService::extractYear('bulan hijriah'));
    }

    public function test_parse_tanggal_serial_excel(): void
    {
        $carbon = MatchingService::parseDate(44197);

        $this->assertNotNull($carbon);
        $this->assertSame('2021', $carbon->format('Y'));
    }

    public function test_parse_format_indonesia_dd_mm_yyyy(): void
    {
        $carbon = MatchingService::parseDate('12/05/2020');

        $this->assertNotNull($carbon);
        $this->assertSame('2020-05-12', $carbon->format('Y-m-d'));
    }

    public function test_parse_format_iso(): void
    {
        $carbon = MatchingService::parseDate('2020-01-05');

        $this->assertNotNull($carbon);
        $this->assertSame('2020-01-05', $carbon->format('Y-m-d'));
    }

    public function test_tanggal_tak_kenal_mengembalikan_null(): void
    {
        $this->assertNull(MatchingService::parseDate(null));
        $this->assertNull(MatchingService::parseDate(''));
        $this->assertNull(MatchingService::parseDate('tidak ada tanggal di sini'));
    }

    /**
     * Regresi: satu baris file pusat berisi '0000-00-00' yang diubah PHP
     * menjadi tanggal tahun -0001 (1 SM) dan ditolak MySQL — harus
     * diperlakukan sebagai "tanpa tanggal".
     */
    public function test_tanggal_nol_diperlakukan_sebagai_tanpa_tanggal(): void
    {
        $this->assertNull(MatchingService::parseDate('0000-00-00'));
        $this->assertNull(MatchingService::parseDate('0000'));
        $this->assertNull(MatchingService::parseDate(0));
        $this->assertNull(MatchingService::parseDate(''));
    }

    public function test_tanggal_nol_memberi_tahun_unknown(): void
    {
        $this->assertSame('0000', MatchingService::extractYear('0000-00-00'));
        $this->assertSame('0000', MatchingService::extractYear(0));
    }
}
