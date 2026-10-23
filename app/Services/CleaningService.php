<?php

namespace App\Services;

/**
 * Pembersih & standardisasi data teks sebelum dipakai untuk matching.
 *
 * Semua jalur (import Excel, audit, dsb) WAJIB lewat service ini agar
 * tidak ada dua implementasi yang berbeda (single source of truth).
 */
class CleaningService
{
    /**
     * Bersihkan nama almarhum: huruf besar, hapus simbol,
     * rapikan spasi ganda dan spasi di ujung.
     */
    public static function cleanName(?string $name): string
    {
        if ($name === null) {
            return '';
        }

        $clean = strtoupper($name);

        // Hapus semua karakter selain huruf, angka, dan spasi
        $clean = preg_replace('/[^A-Z0-9 ]/', '', $clean);

        // Rapikan spasi beruntun jadi satu spasi
        $clean = preg_replace('/\s+/', ' ', $clean);

        return trim($clean);
    }

    /**
     * Normalisasi ringan untuk scorring: uppercase + trim saja,
     * tanpa menghapus karakter (dipakai saat audit, bukan saat import).
     */
    public static function softNormalize(?string $value): string
    {
        return strtoupper(trim((string) $value));
    }
}
