<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Penanda proses panjang (impor & audit) yang berjalan di antrean.
 *
 * Status disimpan sebagai kunci cache per jenis proses + TPU agar
 * halaman web dapat menampilkan banner "sedang diproses" tanpa perlu
 * kolom status baru di database. Semua kunci punya masa hidup terbatas,
 * sehingga tidak ada status yang menggantung selamanya meski worker
 * mati di tengah jalan.
 */
final class ProgresProses
{
    /** Batas hidup kunci "aktif" (detik) — melampaui durasi proses terlama. */
    private const HIDUP_AKTIF = 7200;

    /** Batas hidup pesan hasil/gagal (detik) — sampai seseorang membacanya. */
    private const HIDUP_PESAN = 1800;

    /**
     * Awali proses: bersihkan pesan lama, catat waktu mulai.
     *
     * @param  'import'|'audit'  $jenis
     */
    public static function mulai(string $jenis, int $tpuId): void
    {
        Cache::forget(self::kunci('gagal', $jenis, $tpuId));
        Cache::forget(self::kunci('hasil', $jenis, $tpuId));

        Cache::put(self::kunci('aktif', $jenis, $tpuId), now()->timestamp, self::HIDUP_AKTIF);
    }

    /**
     * Hapus penanda "sedang berjalan" untuk proses yang telah berakhir.
     */
    public static function berhenti(string $jenis, int $tpuId): void
    {
        Cache::forget(self::kunci('aktif', $jenis, $tpuId));
    }

    /**
     * Proses tamat dengan sukses + catat pesan hasil (dipakai impor).
     */
    public static function berhasil(string $jenis, int $tpuId, string $pesan): void
    {
        self::berhenti($jenis, $tpuId);

        Cache::put(self::kunci('hasil', $jenis, $tpuId), $pesan, self::HIDUP_PESAN);
    }

    /**
     * Proses tamat dengan gagal + catat pesan penyebabnya.
     */
    public static function gagal(string $jenis, int $tpuId, string $pesan): void
    {
        self::berhenti($jenis, $tpuId);

        Cache::put(self::kunci('gagal', $jenis, $tpuId), $pesan, self::HIDUP_PESAN);
    }

    /**
     * Apakah proses (jenis, TPU) sedang berjalan?
     */
    public static function berjalan(string $jenis, int $tpuId): bool
    {
        return self::berjalanSejak($jenis, $tpuId) !== null;
    }

    /**
     * Unix timestamp awal proses, atau null bila tidak sedang berjalan.
     */
    public static function berjalanSejak(string $jenis, int $tpuId): ?int
    {
        $mulai = Cache::get(self::kunci('aktif', $jenis, $tpuId));

        return $mulai !== null ? (int) $mulai : null;
    }

    /**
     * Ambil pesan hasil terakhir lalu hapus kuncinya (tampil sekali).
     */
    public static function ambilPesanHasil(string $jenis, int $tpuId): ?string
    {
        $pesan = Cache::get(self::kunci('hasil', $jenis, $tpuId));

        if ($pesan === null) {
            return null;
        }

        Cache::forget(self::kunci('hasil', $jenis, $tpuId));

        return (string) $pesan;
    }

    /**
     * Ambil pesan gagal terakhir lalu hapus kuncinya (tampil sekali).
     */
    public static function ambilPesanGagal(string $jenis, int $tpuId): ?string
    {
        $pesan = Cache::get(self::kunci('gagal', $jenis, $tpuId));

        if ($pesan === null) {
            return null;
        }

        Cache::forget(self::kunci('gagal', $jenis, $tpuId));

        return (string) $pesan;
    }

    private static function kunci(string $macam, string $jenis, int $tpuId): string
    {
        return "siadit:proses:{$macam}:{$jenis}:{$tpuId}";
    }
}
