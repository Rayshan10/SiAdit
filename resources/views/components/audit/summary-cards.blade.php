{{-- Grid Rekap Metrik Total --}}
<div class="p-6 pb-2 grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="flex flex-col gap-2 p-4 rounded-xl border border-slate-200 bg-slate-50">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Data Pusat</div>
        <div class="text-3xl font-extrabold text-slate-800">{{ number_format($totalPusat) }}</div>
    </div>
    <div class="flex flex-col gap-2 p-4 rounded-xl border border-slate-200 bg-slate-50">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Data Cabang</div>
        <div class="text-3xl font-extrabold text-slate-800">{{ number_format($totalCabang) }}</div>
    </div>
    <div class="flex flex-col gap-2 p-4 rounded-xl border border-slate-200 bg-slate-50">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Data (Pusat + Cabang)</div>
        <div class="text-3xl font-extrabold text-slate-800">{{ number_format($totalPusat + $totalCabang) }}</div>
    </div>
</div>

{{-- Grid Status Audit --}}
<div class="p-6 pt-4 grid lg:grid-cols-3 xl:grid-cols-4 gap-6">
    {{-- Card Match Full --}}
    <div class="flex flex-col gap-2 p-4 rounded-xl border border-green-100 bg-green-50/50">
        <div class="flex items-center gap-2 text-green-700">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <span class="text-xs font-bold uppercase tracking-wider">Match Full</span>
        </div>
        <div class="text-3xl font-extrabold text-slate-800">{{ number_format($displayData->total_match, 0, ',', '.') }}</div>
        <div class="text-[11px] text-slate-500 font-medium">Data sama persis</div>
    </div>
    {{-- Card Tahun Beda --}}
    <div class="flex flex-col gap-2 p-4 rounded-xl border border-amber-100 bg-amber-50/50">
        <div class="flex items-center gap-2 text-amber-700">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span class="text-xs font-bold uppercase tracking-wider">Tahun Beda</span>
        </div>
        <div class="text-3xl font-extrabold text-slate-800">{{ number_format($displayData->total_tahun_beda, 0, ',', '.') }}</div>
        <div class="text-[11px] text-slate-500 font-medium">Nama sama, tahun berbeda</div>
    </div>
    {{-- Card Fuzzy Match --}}
    <div class="flex flex-col gap-2 p-4 rounded-xl border border-purple-100 bg-purple-50/50">
        <div class="flex items-center gap-2 text-purple-700">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <span class="text-xs font-bold uppercase tracking-wider">Fuzzy Match</span>
        </div>
        <div class="text-3xl font-extrabold text-slate-800">{{ number_format($displayData->total_fuzzy_match ?? 0, 0, ',', '.') }}</div>
        <div class="text-[11px] text-slate-500 font-medium">Nama mirip berdasarkan kemiripan</div>
    </div>
    {{-- Card Pusat Tidak Ada --}}
    <div class="flex flex-col gap-2 p-4 rounded-xl border border-orange-100 bg-orange-50/50">
        <div class="flex items-center gap-2 text-orange-700">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <span class="text-xs font-bold uppercase tracking-wider">Pusat Tdk Ada</span>
        </div>
        <div class="text-3xl font-extrabold text-slate-800">{{ number_format($displayData->total_pusat_tidak_ada ?? 0, 0, ',', '.') }}</div>
        <div class="text-[11px] text-slate-500 font-medium">Data pusat tidak di cabang</div>
    </div>
    {{-- Card Cabang Tidak Ada --}}
    <div class="flex flex-col gap-2 p-4 rounded-xl border border-red-100 bg-red-50/50">
        <div class="flex items-center gap-2 text-red-700">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
            <span class="text-xs font-bold uppercase tracking-wider">Cabang Tdk Ada</span>
        </div>
        <div class="text-3xl font-extrabold text-slate-800">{{ number_format($displayData->total_cabang_tidak_ada ?? 0, 0, ',', '.') }}</div>
        <div class="text-[11px] text-slate-500 font-medium">Data cabang tidak di pusat</div>
    </div>
    {{-- Card Duplikat --}}
    <div class="flex flex-col gap-2 p-4 rounded-xl border border-cyan-100 bg-cyan-50/50">
        <div class="flex items-center gap-2 text-cyan-700">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
            <span class="text-xs font-bold uppercase tracking-wider">Duplikat</span>
        </div>
        <div class="text-3xl font-extrabold text-slate-800">{{ number_format($displayData->total_duplikat_pusat + $displayData->total_duplikat_cabang, 0, ',', '.') }}</div>
        <div class="text-[11px] text-slate-500 font-medium">Pusat: {{ $displayData->total_duplikat_pusat }} • Cabang: {{ $displayData->total_duplikat_cabang }}</div>
    </div>
</div>
