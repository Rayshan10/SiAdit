<?php

namespace App\Services;

use App\Imports\DataMakamImport;
use App\Models\AuditResult;
use App\Models\DataMakam;
use App\Models\Import;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import Excel data makam dengan semantik "GANTI DATA":
 *
 * Satu TPU hanya boleh memiliki SATU versi file untuk tiap sumber
 * (pusat & cabang). Re-upload file baru WAJIB menghapus versi lama
 * agar baris tidak bertumpuk beberapa kali (yang membuat semua baris
 * salah terdeteksi duplikat).
 */
class ImportMakamService
{
    /**
     * Hapus seluruh data lama untuk (tpu, sumber) lalu import file baru.
     *
     * @param  mixed  $file  UploadedFile atau path file Excel
     * @return int  jumlah baris yang berhasil diimport
     */
    public function gantiDataDanImport(int $tpuId, string $sumber, mixed $file, bool $useTransaction = true): int
    {
        $proses = function () use ($tpuId, $sumber, $file) {
            $this->purgeDataLama($tpuId, $sumber);

            $import = Import::create([
                'tpu_id'   => $tpuId,
                'sumber'   => $sumber,
                'filename' => $this->namaFile($file),
            ]);

            Excel::import(
                new DataMakamImport($tpuId, $import->id, $sumber),
                $file
            );

            $total = DataMakam::where('import_id', $import->id)->count();

            $import->update(['total_data' => $total]);

            return $total;
        };

        return $useTransaction ? DB::transaction($proses) : $proses();
    }

    /**
     * Bersihkan data makam, riwayat import, dan hasil audit lama
     * milik (tpu, sumber). Hasil audit TPU lain tetap aman.
     */
    private function purgeDataLama(int $tpuId, string $sumber): void
    {
        // Hasil audit untuk TPU ini menjadi basi karena datanya diganti.
        // Baris pada tabel `audits` ikut terhapus lewat FK ON DELETE CASCADE.
        AuditResult::where('tpu_id', $tpuId)->delete();

        DataMakam::where('tpu_id', $tpuId)
            ->where('sumber', $sumber)
            ->delete();

        Import::where('tpu_id', $tpuId)
            ->where('sumber', $sumber)
            ->delete();
    }

    /**
     * Nama asli file, mendukung UploadedFile maupun path string (CLI).
     */
    private function namaFile(mixed $file): string
    {
        if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
            return $file->getClientOriginalName();
        }

        return basename((string) $file);
    }
}
