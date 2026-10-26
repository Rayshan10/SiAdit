@extends('layouts.app')

@section('title', 'Hasil Audit')

@section('content')

    {{-- Alert Sukses --}}
    @include('components.audit.partials.alert')

    {{-- Status antrean: audit berjalan / gagal di worker --}}
    @if ($auditBerjalanSejak)
        <div data-muat-ulang-otomatis
            class="mb-5 flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800">
            <svg class="shrink-0 mt-1 animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <div class="text-sm leading-relaxed">
                <span class="font-semibold">Audit sedang berjalan di antrean latar belakang</span>
                — dimulai {{ date('H:i', $auditBerjalanSejak) }} dan halaman ini memperbarui otomatis tiap 5 detik.
                @if (time() - $auditBerjalanSejak > 300)
                    <span class="block text-xs mt-1 opacity-80">
                        Sudah lama tidak berubah? Pastikan pemroses antrean (queue worker) masih berjalan di server.
                    </span>
                @endif
            </div>
        </div>
    @endif

    @if ($auditPesanGagal)
        <div class="mb-5 flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
            <svg class="shrink-0 mt-0.5" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div class="text-sm leading-relaxed">
                <span class="font-semibold">Proses audit terakhir gagal.</span>
                <span class="block mt-1 text-xs">{{ $auditPesanGagal }}</span>
                <form action="{{ url('/audit/generate') }}" method="POST" class="mt-2">
                    @csrf
                    <input type="hidden" name="tpu_id" value="{{ $latestResult?->tpu_id ?? $tpuAuditPertama }}">
                    <button type="submit"
                        class="h-8 px-3 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition">
                        Coba Jalankan Audit Lagi
                    </button>
                </form>
            </div>
        </div>
    @endif

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

    @elseif (empty($auditBerjalanSejak) && empty($auditPesanGagal))
        {{-- State jika belum ada data --}}
        @include('components.audit.empty-state')
    @endif

@endsection

@push('scripts')
    @include('components.audit.scripts')

    @if ($auditBerjalanSejak)
        <script>
            // Audit diproses worker di latar belakang: muat ulang halaman
            // periodik sampai hasil audit baru tersaji.
            setTimeout(() => location.reload(), 5000);
        </script>
    @endif
@endpush
