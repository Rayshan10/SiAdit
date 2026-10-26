<?php

namespace App\Jobs;

use App\Services\ImportMakamService;
use App\Support\ProgresProses;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Impor dua file data makam (pusat & cabang) untuk satu TPU di latar
 * belakang, dengan semantik ganti-data dalam satu transaksi sehingga
 * tidak pernah ada keadaan campuran (pusat baru + cabang tua).
 */
class ProcessImportMakam implements ShouldQueue
{
    use Queueable;

    /**
     * Tidak diulang otomatis: proses ini mengganti data & menghapus hasil
     * audit lama, sehingga eksekusi ganda berisiko tanpa manfaat.
     */
    public int $tries = 1;

    /** Batas eksekusi per job (detik) — dataset 47 ribu baris perlu ±130 detik. */
    public int $timeout = 3600;

    public function __construct(
        public readonly int $tpuId,
        public readonly string $pathPusat,
        public readonly string $pathCabang,
        public readonly string $namaPusat,
        public readonly string $namaCabang,
    ) {
    }

    public function handle(ImportMakamService $importService): void
    {
        try {
            [$jumlahPusat, $jumlahCabang] = DB::transaction(
                function () use ($importService): array {
                    $pusat = $importService->gantiDataDanImport(
                        $this->tpuId,
                        'pusat',
                        $this->pathPusat,
                        useTransaction: false,
                        namaTersimpan: $this->namaPusat,
                    );

                    $cabang = $importService->gantiDataDanImport(
                        $this->tpuId,
                        'cabang',
                        $this->pathCabang,
                        useTransaction: false,
                        namaTersimpan: $this->namaCabang,
                    );

                    return [$pusat, $cabang];
                }
            );

            ProgresProses::berhasil(
                'import',
                $this->tpuId,
                'Impor selesai — Pusat: ' . number_format($jumlahPusat) . ' baris, '
                . 'Cabang: ' . number_format($jumlahCabang) . ' baris. '
                . 'Hasil audit lama di-reset; lanjutkan dengan menjalankan audit baru.'
            );
        } finally {
            $this->bersihkan();
        }
    }

    public function failed(?Throwable $e): void
    {
        ProgresProses::gagal(
            'import',
            $this->tpuId,
            $e?->getMessage() ?: 'Impor gagal dengan alasan tidak diketahui.'
        );

        $this->bersihkan();
    }

    /**
     * Berhenti menandai proses aktif dan hapus berkas kerja job agar
     * disk lokal tidak mengumpulkan sisa berkas unggahan lama.
     */
    private function bersihkan(): void
    {
        ProgresProses::berhenti('import', $this->tpuId);

        File::delete([$this->pathPusat, $this->pathCabang]);
    }
}
