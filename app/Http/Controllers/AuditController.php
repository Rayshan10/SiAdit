<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\AuditResult;
use App\Models\DataMakam;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditController extends Controller
{
    /** Batas jumlah baris yang dimuat per kategori pada tampilan rincian. */
    private const BATAS_TAMPILAN = 100;

    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'kepala_tpu') {
            $latestResult = AuditResult::with('tpu')
                ->where('tpu_id', $user->tpu_id)
                ->latest()
                ->first();
        } else {
            $latestResult = AuditResult::with('tpu')
                ->latest()
                ->first();
        }

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

        return view('audit.index', compact(
            'latestResult',
            'matchFull',
            'tahunBeda',
            'fuzzyMatch',
            'pusatTidakAda',
            'cabangTidakAda',
            'duplikat',

            'totalPusat',
            'totalCabang',
            'persentaseSinkronisasi'
        ));
    }

    public function generate(Request $request, AuditService $auditService)
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

        // Dataset besar: jalankan lewat CLI agar tidak kena timeout browser.
        $jumlahData = DataMakam::where('tpu_id', $tpuId)->count();

        if ($jumlahData > 50_000) {
            return back()->with(
                'error',
                "Data di TPU ini besar (" . number_format($jumlahData) . ' baris). '
                . "Jalankan audit lewat terminal agar aman dari timeout: "
                . "php artisan siadit:generate-audit {$tpuId}"
            );
        }

        $result = $auditService->generate($tpuId);

        return redirect('/audit')->with(
            'success',
            'Sinkronisasi data makam Pusat dan TPU berhasil diselesaikan!'
        );
    }
}
