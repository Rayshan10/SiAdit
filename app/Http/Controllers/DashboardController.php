<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tpu;
use App\Models\DataMakam;
use App\Models\AuditResult;
use App\Models\Import;

class DashboardController extends Controller
{
    public function index()
    {
        $role = auth()->guard()->user()->role;

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */

        if ($role == 'admin') {

            $totalPusat = DataMakam::where(
                'sumber',
                'pusat'
            )->count();

            $totalCabang = DataMakam::where(
                'sumber',
                'cabang'
            )->count();

            $auditTerakhir = AuditResult::latest()
                ->first();

            return view(
                'dashboard.admin',
                [
                    'totalUser' => User::count(),
                    'totalTpu' => Tpu::count(),
                    'totalMakam' => DataMakam::count(),
                    'totalAudit' => AuditResult::count(),

                    'totalPusat' => $totalPusat,
                    'totalCabang' => $totalCabang,
                    'auditTerakhir' => $auditTerakhir,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | KEPALA TPU
        |--------------------------------------------------------------------------
        */

        if ($role == 'kepala_tpu') {

            $user = auth()->guard()->user();

            // Akun kepala TPU yang belum dihubungkan ke TPU oleh Admin
            if (empty($user->tpu_id)) {
                return view(
                    'dashboard.kepala_tpu',
                    ['totalMakam' => 0, 'belumTerhubung' => true]
                );
            }

            $tpuId = $user->tpu_id;

            $totalMakam = DataMakam::where(
                'tpu_id',
                $tpuId
            )->count();

            return view(
                'dashboard.kepala_tpu',
                compact(
                    'totalMakam'
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | KEPALA UPTD
        |--------------------------------------------------------------------------
        */

        if ($role == 'kepala_uptd') {

            $tpus = Tpu::withCount('dataMakam')
                ->with([
                    'auditResults' => function ($query) {
                        $query->latest();
                    }
                ])
                ->get();

            return view(
                'dashboard.kepala_uptd',
                compact('tpus')
            );
        }

        // Role tidak dikenali: arahkan ke profil dengan pesan agar tidak layar kosong
        return redirect()
            ->route('profile.edit')
            ->with(
                'error',
                'Role akun Anda belum tersedia di sistem. Hubungi Admin untuk penyesuaian.'
            );
    }
}
