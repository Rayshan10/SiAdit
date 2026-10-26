<?php

namespace Tests\Feature;

use App\Models\DataMakam;
use App\Models\Tpu;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Penjaga tampilan: kartu "Estimasi Potensi Selisih" (Rupiah) sengaja
 * tidak dirender karena nilai pengalinya tidak memiliki dasar angka
 * resmi — kartu jumlah baris di ringkasan sudah cukup.
 */
class AuditPotensiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@siadit.test',
            'password' => 'password', // dihash otomatis via cast
            'role' => 'admin',
        ]);
    }

    private function buatBaris(Tpu $tpu, int $importId, string $sumber, string $nama, string $tanggal, string $idMatch): void
    {
        DataMakam::create([
            'tpu_id' => $tpu->id,
            'import_id' => $importId,
            'sumber' => $sumber,
            'nama' => $nama,
            'tanggal_raw' => $tanggal,
            'nama_clean' => $nama,
            'tanggal_clean' => $tanggal,
            'id_match' => $idMatch,
        ]);
    }

    private function siapkanDataAudit(): void
    {
        $tpu = Tpu::create(['nama' => 'TPU Pandu', 'kode' => 'PD']);

        // Satu pasangan yang cocok sama persis.
        $this->buatBaris($tpu, 1, 'pusat', 'AHMAD S', '2020-05-01', 'AHMAD S-2020');
        $this->buatBaris($tpu, 2, 'cabang', 'AHMAD S', '2020-05-01', 'AHMAD S-2020');

        // Satu baris pusat tanpa pasangan dan satu baris cabang tanpa pasangan.
        $this->buatBaris($tpu, 1, 'pusat', 'BUDI W', '2019-03-05', 'BUDI W-2019');
        $this->buatBaris($tpu, 2, 'cabang', 'CICI R', '2021-08-11', 'CICI R-2021');

        app(AuditService::class)->generate($tpu->id);
    }

    public function test_halaman_audit_dengan_hasil_tidak_menampilkan_estimasi_rupiah(): void
    {
        $this->siapkanDataAudit();

        $respons = $this->actingAs($this->admin())->get('/audit');

        $respons->assertOk();
        $respons->assertDontSee('Estimasi Potensi Selisih');
        $respons->assertDontSee('Rp 475.000');
    }

    public function test_halaman_audit_tanpa_hasil_tidak_menampilkan_estimasi_rupiah(): void
    {
        $respons = $this->actingAs($this->admin())->get('/audit');

        $respons->assertOk();
        $respons->assertSee('Belum ada data audit');
        $respons->assertDontSee('Estimasi Potensi Selisih');
    }
}
