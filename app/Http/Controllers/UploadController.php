<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImportMakam;
use App\Models\Tpu;
use App\Support\ProgresProses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            $tpus = Tpu::orderBy('nama')->get();
        } else {
            // Kepala TPU & user TPU lain hanya melihat TPU miliknya
            $tpus = Tpu::where('id', $user->tpu_id)
                ->orderBy('nama')
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS PROSES ANTREAN UNTUK HALAMAN INI
        |--------------------------------------------------------------------------
        | Proses impor dijalankan worker di latar belakang. Kumpulkan TPU
        | yang sedang diproses serta pesan hasil/gagal terakhir supaya
        | tampilan bisa menampilkan banner status & muat ulang otomatis.
        */
        $prosesImport = [];
        $pesanHasilImport = null;
        $pesanGagalImport = null;

        foreach ($tpus as $tpu) {
            $sejak = ProgresProses::berjalanSejak('import', $tpu->id);

            if ($sejak !== null) {
                $prosesImport[] = ['tpu' => $tpu->nama, 'sejak' => $sejak];
            }

            $pesanHasilImport ??= ProgresProses::ambilPesanHasil('import', $tpu->id);
            $pesanGagalImport ??= ProgresProses::ambilPesanGagal('import', $tpu->id);
        }

        return view(
            'upload.index',
            compact('tpus', 'prosesImport', 'pesanHasilImport', 'pesanGagalImport')
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

        // Semantik ganti-data membuat dua impor bersamaan berbahaya:
        // unggahan lama harus tamat dulu.
        if (!empty($tpuId) && ProgresProses::berjalan('import', (int) $tpuId)) {
            return redirect('/upload')->with(
                'error',
                'Unggahan sebelumnya untuk TPU ini masih diproses di antrean. Mohon tunggu sampai selesai.'
            );
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
        | SIMPAN BERKAS LALU KIRIM KE ANTREAN
        |--------------------------------------------------------------------------
        | Berkas sementara PHP terhapus begitu request selesai, sedangkan
        | job baru dieksekusi worker kemudian. Makanya berkas dipindah ke
        | disk lokal terlebih dahulu, lalu jalur absolutnya dikirim ke job.
        */
        $disk = Storage::disk('local');
        $awalan = now()->format('Ymd-His') . '-' . Str::lower(Str::random(3)) . '-';

        $namaPusat = $request->file('file_pusat')->getClientOriginalName();
        $namaCabang = $request->file('file_cabang')->getClientOriginalName();

        $pathPusat = $disk->putFileAs('uploads', $request->file('file_pusat'), $awalan . $namaPusat);
        $pathCabang = $disk->putFileAs('uploads', $request->file('file_cabang'), $awalan . $namaCabang);

        if ($pathPusat === false || $pathCabang === false) {
            // Bersihkan sisa berkas yang sempat tersimpan agar tidak jadi sampah
            foreach (array_values(array_filter([$pathPusat, $pathCabang])) as $sisa) {
                File::delete($disk->path((string) $sisa));
            }

            return redirect('/upload')->with(
                'error',
                'Gagal menyimpan berkas unggahan ke penyimpanan server. Coba lagi, bila masih gagal hubungi Admin.'
            );
        }

        ProgresProses::mulai('import', (int) $tpuId);

        ProcessImportMakam::dispatch(
            (int) $tpuId,
            $disk->path($pathPusat),
            $disk->path($pathCabang),
            $namaPusat,
            $namaCabang
        );

        return redirect('/upload')->with(
            'success',
            'Unggahan diterima! Pemrosesan dijalankan oleh antrean di latar belakang — halaman ini memperbarui otomatis sampai proses selesai.'
        );
    }
}
