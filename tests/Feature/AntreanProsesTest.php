<?php

namespace Tests\Feature;

use App\Jobs\ProcessAudit;
use App\Jobs\ProcessImportMakam;
use App\Models\AuditResult;
use App\Models\DataMakam;
use App\Models\Tpu;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ImportMakamService;
use App\Support\ProgresProses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AntreanProsesTest extends TestCase
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

    private function buatTpu(): Tpu
    {
        return Tpu::create(['nama' => 'TPU Pandu', 'kode' => 'PD']);
    }

    private function siapkanData(Tpu $tpu): void
    {
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
    }

    public function test_unggahan_diterima_lalu_dikirim_ke_antrean(): void
    {
        Storage::fake('local');
        Queue::fake();

        $tpu = $this->buatTpu();

        $respons = $this->actingAs($this->admin())->post('/upload', [
            'tpu_id' => $tpu->id,
            'file_pusat' => UploadedFile::fake()->create('pusat.xlsx', 5),
            'file_cabang' => UploadedFile::fake()->create('cabang.xlsx', 5),
        ]);

        $respons->assertRedirect('/upload')->assertSessionHas('success');

        $job = null;

        Queue::assertPushed(ProcessImportMakam::class, function (ProcessImportMakam $diterima) use (&$job) {
            $job = $diterima;

            return $diterima->namaPusat === 'pusat.xlsx'
                && $diterima->namaCabang === 'cabang.xlsx'
                && $diterima->pathPusat !== $diterima->pathCabang;
        });

        $this->assertTrue(ProgresProses::berjalan('import', $tpu->id));

        Storage::disk('local')->assertExists('uploads/' . basename($job->pathPusat));
        Storage::disk('local')->assertExists('uploads/' . basename($job->pathCabang));
    }

    public function test_unggahan_ulang_ditolak_selama_impor_sebelumnya_masih_berjalan(): void
    {
        Storage::fake('local');
        Queue::fake();

        $tpu = $this->buatTpu();
        $admin = $this->admin();

        $berkas = [
            'tpu_id' => $tpu->id,
            'file_pusat' => UploadedFile::fake()->create('pusat.xlsx', 5),
            'file_cabang' => UploadedFile::fake()->create('cabang.xlsx', 5),
        ];

        $this->actingAs($admin)->post('/upload', $berkas);

        $ulang = $this->actingAs($admin)->post('/upload', $berkas);

        $ulang->assertRedirect('/upload')->assertSessionHas('error');
        Queue::assertPushed(ProcessImportMakam::class, 1);
    }

    public function test_audit_dikirim_ke_antrean_dan_ulangannya_ditolak_selama_berjalan(): void
    {
        Queue::fake();

        $tpu = $this->buatTpu();
        $admin = $this->admin();
        $this->siapkanData($tpu);

        $pertama = $this->actingAs($admin)
            ->post('/audit/generate', ['tpu_id' => $tpu->id]);

        $pertama->assertRedirect('/audit')->assertSessionHas('success');

        Queue::assertPushed(ProcessAudit::class, function (ProcessAudit $job) use ($tpu) {
            return $job->tpuId === $tpu->id;
        });

        $this->assertTrue(ProgresProses::berjalan('audit', $tpu->id));

        $kedua = $this->actingAs($admin)
            ->post('/audit/generate', ['tpu_id' => $tpu->id]);

        $kedua->assertRedirect('/audit')->assertSessionHas('error');
        Queue::assertPushed(ProcessAudit::class, 1);
    }

    public function test_job_audit_selesai_menghasilkan_data_dan_menghapus_status_aktif(): void
    {
        $tpu = $this->buatTpu();
        $this->siapkanData($tpu);

        ProgresProses::mulai('audit', $tpu->id);

        (new ProcessAudit($tpu->id))->handle(app(AuditService::class));

        $this->assertSame(1, AuditResult::where('tpu_id', $tpu->id)->count());
        $this->assertFalse(ProgresProses::berjalan('audit', $tpu->id));
        $this->assertNull(ProgresProses::ambilPesanGagal('audit', $tpu->id));
    }

    public function test_job_audit_gagal_mencatat_pesan_dan_meluruskan_status(): void
    {
        $tpu = $this->buatTpu();

        ProgresProses::mulai('audit', $tpu->id);

        $audit = Mockery::mock(AuditService::class);
        $audit->shouldReceive('generate')->andThrow(new \RuntimeException('koneksi database putus'));

        $kegagalan = null;

        try {
            (new ProcessAudit($tpu->id))->handle($audit);
        } catch (\RuntimeException $e) {
            $kegagalan = $e;
        }

        $this->assertNotNull($kegagalan);

        (new ProcessAudit($tpu->id))->failed($kegagalan);

        $this->assertSame('koneksi database putus', ProgresProses::ambilPesanGagal('audit', $tpu->id));
        $this->assertFalse(ProgresProses::berjalan('audit', $tpu->id));
        $this->assertNull(ProgresProses::ambilPesanHasil('audit', $tpu->id));
    }

    public function test_job_impor_selesai_mencatat_hasil_dan_menghapus_berkas_kerja(): void
    {
        Storage::fake('local');

        $tpu = $this->buatTpu();

        ProgresProses::mulai('import', $tpu->id);

        Storage::disk('local')->put('uploads/kirim-pusat.xlsx', 'berkas pusat');
        Storage::disk('local')->put('uploads/kirim-cabang.xlsx', 'berkas cabang');

        $pathPusat = Storage::disk('local')->path('uploads/kirim-pusat.xlsx');
        $pathCabang = Storage::disk('local')->path('uploads/kirim-cabang.xlsx');

        $impor = Mockery::mock(ImportMakamService::class);
        $impor->shouldReceive('gantiDataDanImport')->twice()->andReturn(3, 4);

        $job = new ProcessImportMakam($tpu->id, $pathPusat, $pathCabang, 'pusat.xlsx', 'cabang.xlsx');
        $job->handle($impor);

        $this->assertSame(
            'Impor selesai — Pusat: 3 baris, Cabang: 4 baris. '
            . 'Hasil audit lama di-reset; lanjutkan dengan menjalankan audit baru.',
            ProgresProses::ambilPesanHasil('import', $tpu->id)
        );
        $this->assertFalse(ProgresProses::berjalan('import', $tpu->id));
        Storage::disk('local')->assertMissing('uploads/kirim-pusat.xlsx');
        Storage::disk('local')->assertMissing('uploads/kirim-cabang.xlsx');
    }
}
