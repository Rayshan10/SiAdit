<?php

namespace App\Http\Controllers;

use App\Exports\AuditResultExport;
use App\Jobs\ProcessAudit;
use App\Models\Audit;
use App\Models\AuditResult;
use App\Models\DataMakam;
use App\Support\ProgresProses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class AuditController extends Controller
{
    /** Batas jumlah baris yang dimuat per kategori pada tampilan rincian. */
    private const BATAS_TAMPILAN = 100;

    public function index()
    {
        $user = Auth::user();

        $latestResult = $this->hasilTerbaru($user);

        $matchFull      = collect();
        $tahunBeda      = collect();
        $pusatTidakAda  = collect();
        $cabangTidakAda = collect();
        $fuzzyMatch     = collect();
        $duplikat       = collect();

        $totalPusat = 0;
        $totalCabang = 0;
        $persentaseSinkronisasi = 0;

        if ($latestResult) {
            $auditQuery = fn (string $status) => Audit::with(['dataMakam', 'matchedData'])
                ->where('audit_result_id', $latestResult->id)
                ->where('status', $status)
                ->limit(self::BATAS_TAMPILAN)
                ->get();

            $matchFull      = $auditQuery('match_full');
            $tahunBeda      = $auditQuery('tahun_beda');
            $fuzzyMatch     = Audit::with(['dataMakam', 'matchedData'])
                ->where('audit_result_id', $latestResult->id)
                ->where('status', 'fuzzy_match')
                ->limit(self::BATAS_TAMPILAN)
                ->get();
            $pusatTidakAda  = $auditQuery('pusat_tidak_ada');
            $cabangTidakAda = $auditQuery('cabang_tidak_ada');
            $duplikat       = Audit::with('dataMakam')
                ->where('audit_result_id', $latestResult->id)
                ->whereIn('status', ['duplikat_pusat', 'duplikat_cabang'])
                ->limit(self::BATAS_TAMPILAN)
                ->get();

            $totalPusat = DataMakam::where('tpu_id', $latestResult->tpu_id)
                ->where('sumber', 'pusat')
                ->count();

            $totalCabang = DataMakam::where('tpu_id', $latestResult->tpu_id)
                ->where('sumber', 'cabang')
                ->count();

            if ($totalPusat > 0) {
                $persentaseSinkronisasi =
                    ($latestResult->total_match / $totalPusat) * 100;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TPU KANDIDAT "JALANKAN AUDIT PERTAMA"
        |--------------------------------------------------------------------------
        | Saat belum ada hasil audit tersimpan — audit pertama kali, atau
        | habis di-reset oleh import baru — tombol Jalankan Audit tetap
        | dihidupkan bila data lengkap sudah tersedia. TPU kandidatnya
        | diambil dari TPU pengguna (kepala_tpu) atau dari data paling
        | baru yang diimpor (admin), selaras dengan aturan generate().
        */
        $tpuAuditPertama = null;

        if (!$latestResult && in_array($user->role, ['admin', 'kepala_tpu'])) {
            $kandidatTpuId = $user->tpu_id
                ?: DataMakam::query()->latest('id')->value('tpu_id');

            if ($kandidatTpuId) {
                $sumberTersedia = DataMakam::where('tpu_id', $kandidatTpuId)
                    ->distinct()
                    ->pluck('sumber');

                if ($sumberTersedia->contains('pusat') && $sumberTersedia->contains('cabang')) {
                    $tpuAuditPertama = (int) $kandidatTpuId;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS ANTREAN AUDIT
        |--------------------------------------------------------------------------
        | Saat audit dijalankan via web, worker memprosesnya di latar belakang.
        | Status disimpan sebagai penanda cache berdurasi dan dibaca dari TPU
        | yang tepat: hasil terbaru bila ada, kalau kosong TPU kandidat audit.
        */
        $tpuAktif = $latestResult?->tpu_id ?: $tpuAuditPertama;

        $auditBerjalanSejak = $tpuAktif
            ? ProgresProses::berjalanSejak('audit', $tpuAktif)
            : null;

        $auditPesanGagal = $tpuAktif
            ? ProgresProses::ambilPesanGagal('audit', $tpuAktif)
            : null;

        return view('audit.index', compact(
            'latestResult',
            'tpuAuditPertama',
            'matchFull',
            'tahunBeda',
            'fuzzyMatch',
            'pusatTidakAda',
            'cabangTidakAda',
            'duplikat',

            'totalPusat',
            'totalCabang',
            'persentaseSinkronisasi',

            'auditBerjalanSejak',
            'auditPesanGagal'
        ));
    }

    public function generate(Request $request)
    {
        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | VALIDASI TPU
        |--------------------------------------------------------------------------
        */
        if ($user->role === 'admin') {
            $request->validate(
                ['tpu_id' => ['required', 'exists:tpus,id']],
                ['tpu_id.required' => 'TPU belum tersedia. Jalankan audit dari data yang telah diupload.']
            );

            $tpuId = (int) $request->input('tpu_id');
        } else {
            $tpuId = $user->tpu_id
                ?? abort(403, 'Akun Anda belum terhubung ke TPU. Hubungi Admin.');
        }

        // Pastikan kedua sumber sudah diunggah sebelum audit dijalankan
        $hasDataPusat  = DataMakam::where('tpu_id', $tpuId)->where('sumber', 'pusat')->exists();
        $hasDataCabang = DataMakam::where('tpu_id', $tpuId)->where('sumber', 'cabang')->exists();

        if (!$hasDataPusat || !$hasDataCabang) {
            return redirect('/upload')->with(
                'error',
                'Data makam belum lengkap! Pastikan Anda sudah mengunggah '
                . 'File Pusat (Dinas) dan File Cabang (TPU) sebelum melakukan sinkronisasi.'
            );
        }

        // Satu audit per TPU dalam satu waktu: jangan dua kali masuk antrean.
        if (ProgresProses::berjalan('audit', $tpuId)) {
            return redirect('/audit')->with(
                'error',
                'Audit untuk TPU ini masih berjalan di antrean. Halaman Hasil Audit memperbarui otomatis sampai proses selesai.'
            );
        }

        ProgresProses::mulai('audit', $tpuId);

        ProcessAudit::dispatch($tpuId);

        return redirect('/audit')->with(
            'success',
            'Audit masuk antrean dan dijalankan di latar belakang. Halaman Hasil Audit akan memperbarui otomatis sampai proses selesai.'
        );
    }

    /**
     * Unduh hasil audit terbaru sebagai berkas Excel multi-sheet:
     * satu lembar ringkasan + lembar detail per kategori.
     */
    public function export()
    {
        $hasil = $this->hasilTerbaru(Auth::user());

        if (!$hasil) {
            return redirect('/audit')->with(
                'error',
                'Belum ada hasil audit untuk diekspor. Jalankan audit terlebih dahulu.'
            );
        }

        if (ProgresProses::berjalan('audit', $hasil->tpu_id)) {
            return redirect('/audit')->with(
                'error',
                'Audit sedang berjalan di antrean; unduh Excel setelah proses audit selesai.'
            );
        }

        // Ekspor menulis puluhan ribu baris ke Excel; angkat batas eksekusi
        // agar tidak terhenti max_execution_time di tengah unduhan.
        set_time_limit(300);

        $namaFile = 'hasil-audit-' . Str::slug($hasil->tpu->nama, '-')
            . '-' . $hasil->id . '.xlsx';

        return Excel::download(new AuditResultExport($hasil), $namaFile);
    }

    /**
     * Hasil audit terbaru yang berlaku bagi pengguna:
     * kepala_tpu hanya pada TPU miliknya, selain itu yang terbaru.
     */
    private function hasilTerbaru($user): ?AuditResult
    {
        if ($user->role === 'kepala_tpu') {
            return AuditResult::with('tpu')
                ->where('tpu_id', $user->tpu_id)
                ->latest()
                ->first();
        }

        return AuditResult::with('tpu')
            ->latest()
            ->first();
    }
}
