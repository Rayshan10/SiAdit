<?php

namespace App\Console\Commands;

use App\Models\Tpu;
use App\Services\ImportMakamService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportExcelCommand extends Command
{
    /**
     * Signature perintah.
     */
    protected $signature = 'siadit:import-excel
                            {tpu : ID atau KODE TPU tujuan}
                            {pusat : Path file Excel data PUSAT (Dinas)}
                            {cabang : Path file Excel data CABANG (TPU)}';

    /**
     * Deskripsi perintah.
     */
    protected $description = 'Import file Excel data makam Pusat & Cabang ke sebuah TPU (mengganti data lama per sumber)';

    public function handle(ImportMakamService $importMakam): int
    {
        $cari = $this->argument('tpu');

        $tpu = Tpu::query()
            ->where('id', is_numeric($cari) ? $cari : 0)
            ->orWhere('kode', $cari)
            ->first();

        if (!$tpu) {
            $this->error("TPU '{$cari}' tidak ditemukan.");

            Tpu::query()
                ->orderBy('id')
                ->get(['id', 'kode', 'nama'])
                ->each(
                    fn ($t) => $this->line("  [{$t->id}] {$t->kode} — {$t->nama}")
                );

            return self::FAILURE;
        }

        $filePusat = $this->argument('pusat');
        $fileCabang = $this->argument('cabang');

        foreach ([
            'PUSAT'  => $filePusat,
            'CABANG' => $fileCabang,
        ] as $label => $path) {
            if (!is_file($path)) {
                $this->error("File {$label} tidak ditemukan: {$path}");

                return self::FAILURE;
            }
        }

        $mulai = microtime(true);

        try {
            DB::transaction(
                function () use ($importMakam, $tpu, $filePusat, $fileCabang, &$jumlahPusat, &$jumlahCabang) {
                    $jumlahPusat = $importMakam->gantiDataDanImport(
                        (int) $tpu->id,
                        'pusat',
                        $filePusat,
                        useTransaction: false
                    );

                    $jumlahCabang = $importMakam->gantiDataDanImport(
                        (int) $tpu->id,
                        'cabang',
                        $fileCabang,
                        useTransaction: false
                    );
                }
            );
        } catch (\Throwable $e) {
            report($e);

            $this->error('Import gagal: ' . $e->getMessage());

            return self::FAILURE;
        }

        $durasi = microtime(true) - $mulai;

        $this->info('✔ Import selesai dalam ' . number_format($durasi, 1) . ' detik');
        $this->line("TPU              : {$tpu->nama}");
        $this->line('Baris PUSAT baru : ' . number_format($jumlahPusat));
        $this->line('Baris CABANG baru: ' . number_format($jumlahCabang));
        $this->line('Data lama per sumber telah diganti dan hasil audit direset.');

        $this->newLine();
        $this->line('Lanjutkan dengan: php artisan siadit:generate-audit ' . $tpu->kode);

        return self::SUCCESS;
    }
}
