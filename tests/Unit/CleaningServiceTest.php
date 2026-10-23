<?php

namespace Tests\Unit;

use App\Services\CleaningService;
use Tests\TestCase;

class CleaningServiceTest extends TestCase
{
    public function test_nama_dijadikan_huruf_besar(): void
    {
        $this->assertSame(
            'AHMAD SUBARJO',
            CleaningService::cleanName('ahmad subarjo')
        );
    }

    public function test_simbol_tanda_baca_dan_enter_dihapus(): void
    {
        $this->assertSame(
            'SITI AISYAH PUTRI',
            CleaningService::cleanName("Siti, Aisyah. \n ( Putri )")
        );
    }

    public function test_spasi_ganda_dirapikan(): void
    {
        $this->assertSame(
            'MUH ADIP KUSUMA',
            CleaningService::cleanName('   MUH    ADIP  KUSUMA  ')
        );
    }

    public function test_angka_tetap_dipertahankan(): void
    {
        $this->assertSame(
            'H MUCCAD 98',
            CleaningService::cleanName('h muc-Cad 98!')
        );
    }

    public function test_input_kosong_mengembalikan_string_kosong(): void
    {
        $this->assertSame('', CleaningService::cleanName(null));
        $this->assertSame('', CleaningService::cleanName('   '));
    }
}
