<?php

namespace App\Imports;

use App\Models\DataMakam;
use App\Services\CleaningService;
use App\Services\MatchingService;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DataMakamImport implements
    ToModel,
    WithChunkReading,
    WithBatchInserts,
    WithHeadingRow
{
    protected $tpuId;

    protected $importId;

    protected $sumber;

    public function __construct(
        $tpuId,
        $importId,
        $sumber
    ) {
        $this->tpuId = $tpuId;

        $this->importId = $importId;

        $this->sumber = $sumber;
    }

    public function model(array $row)
    {
        /*
        |--------------------------------------------------------------------------
        | AMBIL DATA BERDASARKAN SUMBER (judul kolom kedua file memang beda)
        |--------------------------------------------------------------------------
        */
        $nama =
            $row['nama']
            ?? $row['almarhum']
            ?? null;

        $tanggal =
            $row['tanggal_pemakaman']
            ?? $row['dimakamkan_tanggal']
            ?? null;

        /*
        |--------------------------------------------------------------------------
        | FALLBACK: TANGGAL MENINGGAL / WAFAT
        |--------------------------------------------------------------------------
        | Beberapa file pusat tidak memiliki tanggal pemakaman (kolom kosong),
        | tetapi memiliki tanggal meninggal. Tanpa fallback ini seluruh baris
        | akan bernilai id_match NAMA-0000 dan salah terhitung duplikat.
        */
        if (
            $tanggal === null
            || trim((string) $tanggal) === ''
        ) {
            $tanggal =
                $row['meninggal_tanggal']
                ?? $row['tanggal_kematian']
                ?? $row['tgl_meninggal']
                ?? $row['tgl_wafat']
                ?? null;
        }

        /*
        |--------------------------------------------------------------------------
        | SKIP BARIS KOSONG
        |--------------------------------------------------------------------------
        */
        if ($nama === null || trim((string) $nama) === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | CLEANING NAMA (via CleaningService)
        |--------------------------------------------------------------------------
        */
        $namaClean = CleaningService::cleanName((string) $nama);

        if ($namaClean === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | TANGGAL & ID MATCH (via MatchingService)
        |--------------------------------------------------------------------------
        */
        $tanggalClean = MatchingService::parseDate($tanggal);

        return new DataMakam([
            'tpu_id'        => $this->tpuId,
            'import_id'     => $this->importId,
            'sumber'        => $this->sumber,
            'nama'          => $nama,
            'tanggal_raw'   => $tanggal,
            'nama_clean'    => $namaClean,
            'tanggal_clean' => $tanggalClean,
            'id_match'      => MatchingService::generateIdMatch(
                $namaClean,
                $tanggal
            ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CHUNK & BATCH INSERT
    |--------------------------------------------------------------------------
    */

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return 1000;
    }
}
