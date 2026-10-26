@extends('layouts.app')

@section('title', 'Hasil Audit')

@section('content')

    {{-- Alert Sukses --}}
    @include('components.audit.partials.alert')

    {{-- Header Section --}}
    @include('components.audit.header')

    @php
        $displayData = $latestResult;
    @endphp

    @if ($displayData)
        {{-- Container Ringkasan & Chart --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div class="font-semibold text-slate-700 text-sm">
                    Ringkasan Audit: {{ $displayData->tpu->nama }}
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-xs text-slate-500 font-medium">
                        Terakhir diupdate: {{ date('d M Y, H:i', strtotime($displayData->updated_at)) }}
                    </div>

                    <a href="{{ url('/audit/export') }}" title="Unduh laporan audit lengkap sebagai berkas Excel"
                        class="h-8 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg flex items-center gap-1.5 transition shadow-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        Unduh Excel
                    </a>
                </div>
            </div>

            @include('components.audit.summary-cards')
            @include('components.audit.chart')
        </div>

        {{-- Container Rincian Tabel --}}
        @include('components.audit.details-grid')

    @else
        {{-- State jika belum ada data --}}
        @include('components.audit.empty-state')
    @endif

@endsection

@push('scripts')
    @include('components.audit.scripts')
@endpush