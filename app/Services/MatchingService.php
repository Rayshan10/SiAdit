<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Pembentuk identitas unik (id_match = NAMA-TAHUN) dan pembaca
 * tanggal dari berbagai format yang umum ditemukan di file Excel dinas.
 */
class MatchingService
{
    /** Tahun fallback ketika tanggal tidak bisa dibaca. */
    public const UNKNOWN_YEAR = '0000';

    /**
     * Buat id_match dari nama_clean + tanggal pemakaman.
     */
    public static function generateIdMatch(string $namaClean, mixed $tanggal): string
    {
        $year = self::extractYear($tanggal);

        return $namaClean . '-' . $year;
    }

    /**
     * Ambil tahun dari input tanggal dengan beragam format:
     * - Serial number Excel (numeric)
     * - Format bebas yang mengandung 19xx / 20xx
     * - Format singkat '89 / '07 (=> 1989 / 2007)
     */
    public static function extractYear(mixed $tanggal): string
    {
        $tanggal = trim((string) ($tanggal ?? ''));

        if ($tanggal === '') {
            return self::UNKNOWN_YEAR;
        }

        // Serial number Excel (0 tidak valid — berarti tanpa tanggal)
        if (is_numeric($tanggal)) {
            if ((float) $tanggal <= 0) {
                return self::UNKNOWN_YEAR;
            }

            try {
                $tahun = ExcelDate::excelToDateTimeObject(
                    (float) $tanggal
                )->format('Y');
            } catch (\Throwable) {
                return self::UNKNOWN_YEAR;
            }

            return self::tahunMasukAkal($tahun) ? $tahun : self::UNKNOWN_YEAR;
        }

        // Format 19xx / 20xx di dalam string
        if (preg_match('/(19|20)\d{2}/', $tanggal, $match)) {
            return $match[0];
        }

        // Format singkat '89 / '07
        if (preg_match("/'(\d{2})/", $tanggal, $shortYear)) {
            return ((int) $shortYear[1] <= 30 ? '20' : '19') . $shortYear[1];
        }

        return self::UNKNOWN_YEAR;
    }

    /**
     * Coba baca tanggal lengkap (bukan hanya tahun) menjadi instance Carbon.
     * Menerima serial Excel, format Indonesia (dd/mm/yyyy), dan format bebas
     * yang dipahami PHP. Mengembalikan null jika tidak bisa dibaca.
     */
    public static function parseDate(mixed $tanggal): ?Carbon
    {
        $tanggal = trim((string) ($tanggal ?? ''));

        if ($tanggal === '') {
            return null;
        }

        // Tanggal-nol ('0000', '0000-00-00') berarti "tidak ada tanggal".
        // Tanpa guard ini Carbon::parse menghasilkan tanggal tahun -1
        // yang ditolak MySQL untuk kolom DATE.
        if (preg_match('/^0{4}(?:[-\/.]0{1,2}){0,2}$/', $tanggal)) {
            return null;
        }

        // Serial number Excel (0 tidak valid — berarti tanpa tanggal)
        if (is_numeric($tanggal)) {
            if ((float) $tanggal <= 0) {
                return null;
            }

            try {
                return self::tanggalSah(
                    Carbon::instance(
                        ExcelDate::excelToDateTimeObject((float) $tanggal)
                    )
                );
            } catch (\Throwable) {
                return null;
            }
        }

        // Prioritas format Indonesia: dd/mm/yyyy dan dd-mm-yyyy
        foreach (['d/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            try {
                $carbon = Carbon::createFromFormat($format, $tanggal);
            } catch (\Throwable) {
                continue;
            }

            if ($carbon === false) {
                continue;
            }

            // Tolak parse yang menyisakan data tak terbaca (partial parse)
            $lastErrors = Carbon::getLastErrors();

            if (is_array($lastErrors) && ($lastErrors['error_count'] > 0 || $lastErrors['warning_count'] > 0)) {
                continue;
            }

            return self::tanggalSah($carbon);
        }

        // Format lain (ISO dsb.)
        try {
            return self::tanggalSah(Carbon::parse($tanggal));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Tahun yang masuk akal untuk dataset TPU (1900-2100).
     * Di luar rentang ini berarti data tanggal rusak.
     */
    private static function tahunMasukAkal(int|string $tahun): bool
    {
        return (int) $tahun >= 1900 && (int) $tahun <= 2100;
    }

    /**
     * Buang hasil parsing dengan tahun tidak masuk akal, misalnya
     * '0000-00-00' yang diubah PHP menjadi tahun -0001 (1 SM).
     */
    private static function tanggalSah(Carbon $carbon): ?Carbon
    {
        if (!self::tahunMasukAkal($carbon->year)) {
            return null;
        }

        return $carbon;
    }
}
