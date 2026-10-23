<?php

namespace App\Http\Controllers;

use App\Imports\DataMakamImport;
use App\Models\DataMakam;
use App\Models\Import;
use App\Models\Tpu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UploadController extends Controller
{
    public function index()
    {
        if (Auth::user()->role === 'admin') {
            $tpus = Tpu::orderBy('nama')->get();
        } else {
            // Kepala TPU & user TPU lain hanya melihat TPU miliknya
            $tpus = Tpu::where('id', Auth::user()->tpu_id)
                ->orderBy('nama')
                ->get();
        }

        return view(
            'upload.index',
            compact('tpus')
        );
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | HAK AKSES & TPU YANG DIPAKAI
        |--------------------------------------------------------------------------
        */
        if ($user->role !== 'admin') {
            // Kepala TPU hanya boleh mengupload file untuk TPU miliknya
            if (empty($user->tpu_id)) {
                return back()->with(
                    'error',
                    'Akun Anda belum terhubung ke TPU. Hubungi Admin untuk pengaturan akun.'
                );
            }

            $tpuId = $user->tpu_id;
        } else {
            $tpuId = $request->input('tpu_id');
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI INPUT
        |--------------------------------------------------------------------------
        */
        $request->validate(
            [
                'tpu_id'      => ['required', 'exists:tpus,id'],
                'file_pusat'  => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:10240'],
                'file_cabang' => ['required', 'file', 'mimes:csv,xls,xlsx', 'max:10240'],
            ],
            [
                'tpu_id.required'      => 'TPU wajib dipilih.',
                'tpu_id.exists'        => 'TPU tidak ditemukan di database.',
                'file_pusat.required'  => 'File Pusat (Dinas) wajib diunggah.',
                'file_cabang.required' => 'File Cabang (TPU) wajib diunggah.',
                'file_pusat.max'       => 'Ukuran File Pusat maksimal 10 MB.',
                'file_cabang.max'      => 'Ukuran File Cabang maksimal 10 MB.',
                '*.mimes'              => 'File harus berformat XLS, XLSX, atau CSV.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | TRANSAKSI: SEMUA ATAU TIDAK SAMA-SEKALI
        |--------------------------------------------------------------------------
        | Import pusat + cabang dibungkus satu transaksi; jika salah satu
        | gagal, seluruh data import dibatalkan sehingga tidak ada data yatim.
        */
        try {
            $jumlahBaris = DB::transaction(
                function () use ($tpuId, $request) {
                    $importPusat = Import::create([
                        'tpu_id'   => $tpuId,
                        'sumber'   => 'pusat',
                        'filename' => $request->file('file_pusat')->getClientOriginalName(),
                    ]);

                    Excel::import(
                        new DataMakamImport($tpuId, $importPusat->id, 'pusat'),
                        $request->file('file_pusat')
                    );

                    $importCabang = Import::create([
                        'tpu_id'   => $tpuId,
                        'sumber'   => 'cabang',
                        'filename' => $request->file('file_cabang')->getClientOriginalName(),
                    ]);

                    Excel::import(
                        new DataMakamImport($tpuId, $importCabang->id, 'cabang'),
                        $request->file('file_cabang')
                    );

                    // Catat jumlah baris yang berhasil diimport
                    $totalPusat  = DataMakam::where('import_id', $importPusat->id)->count();
                    $totalCabang = DataMakam::where('import_id', $importCabang->id)->count();

                    $importPusat->update(['total_data' => $totalPusat]);
                    $importCabang->update(['total_data' => $totalCabang]);

                    return $totalPusat + $totalCabang;
                }
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'Import gagal: ' . $e->getMessage()
            );
        }

        return redirect('/upload')->with(
            'success',
            "File berhasil diupload — {$jumlahBaris} baris data makam terbaca. " .
            'Silakan buka menu Hasil Audit untuk menjalankan sinkronisasi.'
        );
    }
}
