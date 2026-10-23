<?php

namespace App\Console\Commands;

use App\Models\Tpu;
use App\Services\AuditService;
use Illuminate\Console\Command;

class GenerateAuditCommand extends Command
{
    /**
     * Signature perintah.
     */
    protected $signature = 'siadit:generate-audit
                            {tpu : ID atau KODE TPU yang akan diaudit}
                            {--force : Lewati konfirmasi saat proses audit berjalan lama}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Jalankan audit & pencocokan data makam Pusat vs Cabang untuk satu TPU (dianjurkan untuk dataset besar)';

    public function handle(AuditService $auditService): int
    {
        $cari = $this->argument('tpu');

        $tpu = Tpu::query()
            ->where('id', is_numeric($cari) ? $cari : 0)
            ->orWhere('kode', $cari)
            ->first();

        if (!$tpu) {
            $this->error("TPU '{$cari}' tidak ditemukan.");
            $this->line('Daftar TPU tersedia:');

            Tpu::query()
                ->orderBy('id')
                ->get(['id', 'kode', 'nama'])
                ->each(
                    fn ($t) => $this->line("  [{$t->id}] {$t->kode} — {$t->nama}")
                );

            return self::FAILURE;
        }

        $jumlahPusat = $tpu->dataMakam()->where('sumber', 'pusat')->count();
        $jumlahCabang = $tpu->dataMakam()->where('sumber', 'cabang')->count();

        $this->info("TPU : {$tpu->nama} ({$tpu->kode})");
        $this->info("Data pusat  : " . number_format($jumlahPusat) . ' baris');
        $this->info("Data cabang : " . number_format($jumlahCabang) . ' baris');

        if ($jumlahPusat === 0 || $jumlahCabang === 0) {
            $this->error('Data pusat dan cabang harus keduanya terisi sebelum audit. Unggah lewat menu Upload File.');

            return self::FAILURE;
        }

        if (
            ($jumlahPusat + $jumlahCabang) > 50_000
            && !$this->option('force')
            && !$this->confirm('Dataset cukup besar — proses bisa memakan waktu beberapa menit. Lanjutkan?')
        ) {
            return self::FAILURE;
        }

        // Naikkan batas agar aman untuk 100+ ribu baris
        ini_set('memory_limit', '1024M');

        $mulai = microtime(true);

        $hasil = $auditService->generate($tpu->id);

        $durasi = microtime(true) - $mulai;

        $this->newLine();
        $this->info('✔ Audit selesai dalam ' . number_format($durasi, 1) . ' detik');
        $this->table(
            ['Kategori', 'Jumlah'],
            [
                ['Match Full', number_format($hasil->total_match)],
                ['Tahun Beda', number_format($hasil->total_tahun_beda)],
                ['Fuzzy Match', number_format($hasil->total_fuzzy_match)],
                ['Pusat Tidak Ada', number_format($hasil->total_pusat_tidak_ada)],
                ['Cabang Tidak Ada', number_format($hasil->total_cabang_tidak_ada)],
                ['Duplikat Pusat', number_format($hasil->total_duplikat_pusat)],
                ['Duplikat Cabang', number_format($hasil->total_duplikat_cabang)],
            ]
        );

        $this->line('Hasil lengkap dapat dilihat di menu "Hasil Audit".');
        $this->line("AuditResult ID: {$hasil->id}");

        return self::SUCCESS;
    }
}
