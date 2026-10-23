@extends('layouts.app')

@section('title', 'Dashboard TPU')

@section('content')

<div class="p-6">

    <h1 class="text-3xl font-bold mb-6">

        Dashboard Kepala TPU

    </h1>

    @if (!empty($belumTerhubung))
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6 text-amber-800 text-sm flex items-start gap-3">
            <svg class="shrink-0 mt-0.5" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div>
                <div class="font-semibold">Akun belum terhubung ke TPU</div>
                Hubungi Admin melalui menu <span class="font-medium">Kelola User</span> agar
                akun terhubung ke TPU dan tampilan data total selanjutnya dapat muncul.
            </div>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">

        <div class="text-slate-500 text-sm">

            TPU

        </div>

        <div class="text-xl font-semibold">

            {{ auth()->user()->tpu->nama ?? '-' }}

        </div>

        <div class="mt-5">

            Total Data Makam

        </div>

        <div class="text-3xl font-bold text-blue-700">

            {{ number_format($totalMakam) }}

        </div>

    </div>

</div>

@endsection