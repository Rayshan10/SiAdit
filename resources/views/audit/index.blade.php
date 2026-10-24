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
                <div class="text-xs text-slate-500 font-medium">
                    Terakhir diupdate: {{ date('d M Y, H:i', strtotime($displayData->updated_at)) }}
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