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

        // Serial number Excel
        if (is_numeric($tanggal)) {
            try {
                return ExcelDate::excelToDateTimeObject(
                    (float) $tanggal
                )->format('Y');
            } catch (\Throwable) {
                return self::UNKNOWN_YEAR;
            }
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

        // Serial number Excel
        if (is_numeric($tanggal)) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $tanggal)
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

            return $carbon;
        }

        // Format lain (ISO dsb.)
        try {
            return Carbon::parse($tanggal);
        } catch (\Throwable) {
            return null;
        }
    }
}
