<?php

namespace Tests\Feature;

use App\Models\DataMakam;
use App\Models\Tpu;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditExportTest extends TestCase
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

    private function siapkanHasilAudit(): void
    {
        $tpu = Tpu::create(['nama' => 'TPU Pandu', 'kode' => 'PD']);

        foreach (['pusat' => 1, 'cabang' => 2] as $sumber => $importId) {
            DataMakam::create([
                'tpu_id' => $tpu->id,
                'import_id' => $importId,
                'sumber' => $sumber,
                'nama' => 'AHMAD S',
                'tanggal_raw' => '2020-05-01',
                'nama_clean' => 'AHMAD S',
                'tanggal_clean' => '2020-05-01',
                'id_match' => 'AHMAD S-2020',
            ]);
        }

        app(AuditService::class)->generate($tpu->id);
    }

    public function test_admin_bisa_mengunduh_excel_hasil_audit(): void
    {
        $this->siapkanHasilAudit();

        $respons = $this->actingAs($this->admin())->get('/audit/export');

        $respons->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            $respons->headers->get('content-type')
        );
        $this->assertStringContainsString(
            'hasil-audit-tpu-pandu-',
            $respons->headers->get('content-disposition')
        );
    }

    public function test_ekspor_tanpa_hasil_audit_dialihkan_dengan_pesan(): void
    {
        $respons = $this->actingAs($this->admin())->get('/audit/export');

        $respons->assertRedirect('/audit');
        $respons->assertSessionHas('error');
    }
}
