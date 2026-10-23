<?php

namespace App\Http\Controllers;

use App\Models\Tpu;
use App\Services\ImportMakamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UploadController extends Controller
{
    public function __construct(
        private ImportMakamService $importMakam
    ) {
    }

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
        | TRANSAKSI: GANTI DATA (Bukan menambah baris berulang!)
        |--------------------------------------------------------------------------
        | Mengunggah file baru menggantikan data lama untuk sumber yang sama
        | di TPU tersebut. Kedua file diproses dalam satu transaksi sehingga
        | tidak ada keadaan campuran (pusat baru + cabang tua).
        */
        $jumlahPusat = 0;
        $jumlahCabang = 0;

        try {
            DB::transaction(
                function () use ($tpuId, $request, &$jumlahPusat, &$jumlahCabang) {
                    $jumlahPusat = $this->importMakam->gantiDataDanImport(
                        (int) $tpuId,
                        'pusat',
                        $request->file('file_pusat'),
                        useTransaction: false
                    );

                    $jumlahCabang = $this->importMakam->gantiDataDanImport(
                        (int) $tpuId,
                        'cabang',
                        $request->file('file_cabang'),
                        useTransaction: false
                    );
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
            "Import berhasil — File Pusat: " . number_format($jumlahPusat) . ' baris, '
            . "File Cabang: " . number_format($jumlahCabang) . ' baris. '
            . 'Data lama per sumber telah diganti dan hasil audit direset. '
            . 'Silakan buka menu Hasil Audit untuk menjalankan sinkronisasi.'
        );
    }
}
