<?php

namespace Tests\Feature;

use App\Models\Tpu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UploadAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_kepala_uptd_dilarang_membuka_halaman_upload(): void
    {
        $uptd = User::create([
            'name' => 'Kepala UPTD',
            'email' => 'uptd@siadit.test',
            'password' => 'password', // dihash otomatis via cast
            'role' => 'kepala_uptd',
        ]);

        $this->actingAs($uptd)
            ->get('/upload')
            ->assertForbidden();
    }

    public function test_admin_dapat_membuka_halaman_upload(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@siadit.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/upload')
            ->assertOk();
    }

    public function test_upload_tanpa_file_ditolak_validasi_bukan_error_500(): void
    {
        $tpu = Tpu::create(['nama' => 'TPU Pandu', 'kode' => 'TPU-PANDU']);

        $kepala = User::create([
            'name' => 'Kepala TPU',
            'email' => 'kepala@siadit.test',
            'password' => 'password',
            'role' => 'kepala_tpu',
            'tpu_id' => $tpu->id,
        ]);

        $respons = $this->actingAs($kepala)
            ->post('/upload', ['tpu_id' => (string) $tpu->id]);

        $respons->assertSessionHasErrors(['file_pusat', 'file_cabang']);
    }

    public function test_halaman_register_sudah_dinonaktifkan(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
