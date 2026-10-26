<?php

namespace App\Jobs;

use App\Services\AuditService;
use App\Support\ProgresProses;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Jalankan audit rekonsiliasi satu TPU di latar belakang.
 *
 * Tombol web hanya mengantre job ini; halaman Hasil Audit menampilkan
 * status berjalan & memperbarui otomatis sampai hasil baru tersaji.
 */
class ProcessAudit implements ShouldQueue
{
    use Queueable;

    /**
     * Tidak diulang otomatis: semantik ganti-data membuat eksekusi ganda
     * mahal. Bila gagal, pengguna cukup menjalankan ulang audit dari web
     * atau lewat CLI.
     */
    public int $tries = 1;

    /** Batas eksekusi per job (detik) — dataset besar perlu belasan menit. */
    public int $timeout = 1800;

    public function __construct(
        public readonly int $tpuId,
    ) {
    }

    public function handle(AuditService $auditService): void
    {
        try {
            $auditService->generate($this->tpuId);
        } finally {
            ProgresProses::berhenti('audit', $this->tpuId);
        }
    }

    public function failed(?Throwable $e): void
    {
        ProgresProses::gagal(
            'audit',
            $this->tpuId,
            $e?->getMessage() ?: 'Audit gagal dengan alasan tidak diketahui.'
        );
    }
}
