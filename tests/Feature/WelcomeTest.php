<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_sambutan_dapat_dibuka_tanpa_login(): void
    {
        $respons = $this->get('/');

        $respons->assertOk();
        $respons->assertSee('Audit & Rekonsiliasi Data Pemakaman TPU Pandu');
        $respons->assertSee('Masuk ke Aplikasi');
    }

    public function test_pengguna_yang_sudah_masuk_dialihkan_ke_dashboard(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@siadit.test',
            'password' => 'password', // dihash otomatis via cast
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->get('/')->assertRedirect(route('dashboard'));
    }
}
