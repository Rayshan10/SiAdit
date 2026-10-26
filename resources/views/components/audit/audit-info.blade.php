{{-- 
    Estimasi potensi selisih nilai (Rupiah) untuk satu hasil audit.
    Membutuhkan variabel $audit (model AuditResult); pemanggil wajib
    menyertakan komponen ini hanya saat hasil audit sudah tersedia.
--}}
@php
    // Estimasi nilai satu baris data makam (Rp) yang dijadikan pengali.
    $nilaiPerBaris = 475000;

    $kelasKartu = [
        ['label' => 'Pusat tidak ada di cabang', 'warnaBorder' => 'border-orange-100', 'warnaLabel' => 'text-orange-700', 'warnaAngka' => 'text-orange-600', 'kataData' => 'pusat', 'total' => $audit->total_pusat_tidak_ada],
        ['label' => 'Cabang tidak ada di pusat', 'warnaBorder' => 'border-red-100', 'warnaLabel' => 'text-red-700', 'warnaAngka' => 'text-red-600', 'kataData' => 'cabang', 'total' => $audit->total_cabang_tidak_ada],
    ];
@endphp

<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between gap-2 bg-slate-50/50">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-[#1A3A5C]/10 text-[#1A3A5C] flex items-center justify-center">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="6" width="20" height="12" rx="2" />
                    <circle cx="12" cy="12" r="2" />
                    <path d="M6 12h.01" />
                    <path d="M18 12h.01" />
                </svg>
            </div>
            <div class="font-semibold text-slate-800 text-[13px] uppercase tracking-wide">
                Estimasi Potensi Selisih
            </div>
        </div>
        <div class="text-[11px] text-slate-500 text-right">
            Baris tidak berpasangan × Rp {{ number_format($nilaiPerBaris, 0, ',', '.') }}
        </div>
    </div>

    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50/50">
        @foreach ($kelasKartu as $kartu)
            <div class="bg-white {{ $kartu['warnaBorder'] }} rounded-xl p-5">
                <div class="text-xs font-bold uppercase tracking-wider {{ $kartu['warnaLabel'] }}">
                    {{ $kartu['label'] }}
                </div>
                <div class="mt-2 text-2xl xl:text-3xl font-extrabold {{ $kartu['warnaAngka'] }}">
                    Rp {{ number_format($kartu['total'] * $nilaiPerBaris, 0, ',', '.') }}
                </div>
                <div class="mt-1.5 text-[11px] text-slate-500 font-medium">
                    {{ number_format($kartu['total'], 0, ',', '.') }} baris data {{ $kartu['kataData'] }} tidak berpasangan
                </div>
            </div>
        @endforeach
    </div>
</div>
