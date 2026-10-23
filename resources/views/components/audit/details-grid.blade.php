<div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between bg-slate-50 gap-2">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-[#1A3A5C]/10 text-[#1A3A5C] flex items-center justify-center">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                    <polyline points="14 2 14 8 20 8" />
                    <line x1="16" y1="13" x2="8" y2="13" />
                    <line x1="16" y1="17" x2="8" y2="17" />
                    <polyline points="10 9 9 9 8 9" />
                </svg>
            </div>
            <div class="font-semibold text-slate-800 text-[13px] uppercase tracking-wide">
                Rincian Data Audit TPU {{ $displayData->tpu->nama }}
            </div>
        </div>
        <div class="text-[11px] text-slate-500">
            Gunakan kotak pencarian untuk memfilter nama — ketik misalnya "AHMAD"
        </div>
    </div>

    <div class="p-6 bg-slate-50/50">
        <div class="flex gap-4 overflow-x-auto custom-scrollbar pb-2">

            @php
                // Batas tampilan diset di AuditController (folio ~100 baris/kategori)
                $infoModel = function ($dimuat, $total) {
                    return 'Tampil ' . number_format(min($dimuat, $total), 0, ',', '.')
                        . ' dari ' . number_format($total, 0, ',', '.');
                };
            @endphp

            {{-- 1. Match Full (Hijau) --}}
            <div class="daftar-audit bg-white border border-green-100 rounded-xl shadow-sm flex flex-col h-[420px] w-[240px] shrink-0 overflow-hidden hover:shadow-md transition-shadow"
                data-id="match_full">
                <div class="px-4 py-3 bg-green-50/80 border-b border-green-100 shrink-0">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-[12.5px] text-green-800">Match Full</span>
                        <span class="text-[10px] font-bold bg-white text-green-600 border border-green-200 px-2 py-0.5 rounded-full shadow-sm">{{ number_format(count($matchFull), 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-2 relative">
                        <input type="text" placeholder="Cari nama…"
                            class="cari-daftar w-full h-8 text-[11.5px] px-2.5 border border-green-100 rounded-md bg-white focus:outline-none focus:border-green-400">
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400">
                        {{ $infoModel(count($matchFull), $displayData->total_match) }}
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse text-[12.5px]" data-tag="match_full">
                        <tbody>
                            @forelse ($matchFull as $item)
                                <tr class="hover:bg-green-50/40 transition-colors" data-nama="{{ $item->dataMakam->nama }}">
                                    <td class="px-4 py-3 border-b border-slate-100 text-slate-600 font-medium truncate max-w-[1px] w-full" title="{{ $item->dataMakam->nama }} ({{ $item->dataMakam->id_match }})">
                                        {{ $item->dataMakam->nama }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-10 text-center text-slate-400 text-xs">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. Fuzzy Match (Ungu, dengan skor kemiripan) --}}
            <div class="daftar-audit bg-white border border-purple-200 rounded-xl shadow-sm flex flex-col h-[420px] w-[240px] shrink-0 overflow-hidden hover:shadow-md transition-shadow"
                data-id="fuzzy_match">
                <div class="px-4 py-3 bg-purple-50/80 border-b border-purple-200 shrink-0">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-[12.5px] text-purple-800">Fuzzy Match</span>
                        <span class="text-[10px] font-bold bg-white text-purple-600 border border-purple-300 px-2 py-0.5 rounded-full shadow-sm">{{ number_format(count($fuzzyMatch), 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-2">
                        <input type="text" placeholder="Cari nama…"
                            class="cari-daftar w-full h-8 text-[11.5px] px-2.5 border border-purple-200 rounded-md bg-white focus:outline-none focus:border-purple-400">
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400">
                        {{ $infoModel(count($fuzzyMatch), $displayData->total_fuzzy_match ?? 0) }}
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div class="w-full text-left text-[12.5px]" data-tag="fuzzy_match">
                        @forelse($fuzzyMatch as $item)
                            <div class="px-4 py-3 border-b border-slate-100 hover:bg-purple-50/40 transition-colors" data-nama="{{ $item->dataMakam->nama }}">
                                <div class="font-medium text-slate-800 truncate" title="{{ $item->dataMakam->nama }}">{{ $item->dataMakam->nama }}</div>
                                @if ($item->matchedData)
                                    <div class="text-[11px] text-purple-600 mt-0.5 truncate" title="Mirip: {{ $item->matchedData->nama }}">
                                        Mirip: {{ $item->matchedData->nama }}
                                    </div>
                                    <div class="mt-1 flex items-center gap-1.5">
                                        <span class="text-[10px] font-bold bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                                            {{ $item->similarity_score }}%
                                        </span>
                                        <span class="text-[10px] font-medium bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full">
                                            jarak {{ $item->levenshtein_distance }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="px-4 py-10 text-center text-slate-400 text-xs">Tidak ada data</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- 3. Tahun Beda (Amber) --}}
            <div class="daftar-audit bg-white border border-amber-100 rounded-xl shadow-sm flex flex-col h-[420px] w-[240px] shrink-0 overflow-hidden hover:shadow-md transition-shadow"
                data-id="tahun_beda">
                <div class="px-4 py-3 bg-amber-50/80 border-b border-amber-100 shrink-0">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-[12.5px] text-amber-800">Tahun Beda</span>
                        <span class="text-[10px] font-bold bg-white text-amber-600 border border-amber-200 px-2 py-0.5 rounded-full shadow-sm">{{ number_format(count($tahunBeda), 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-2">
                        <input type="text" placeholder="Cari nama…"
                            class="cari-daftar w-full h-8 text-[11.5px] px-2.5 border border-amber-100 rounded-md bg-white focus:outline-none focus:border-amber-400">
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400">
                        {{ $infoModel(count($tahunBeda), $displayData->total_tahun_beda) }}
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse text-[12.5px]" data-tag="tahun_beda">
                        <tbody>
                            @forelse ($tahunBeda as $item)
                                <tr class="hover:bg-amber-50/40 transition-colors" data-nama="{{ $item->dataMakam->nama }}">
                                    <td class="px-4 py-3 border-b border-slate-100 text-slate-600 font-medium truncate max-w-[1px] w-full" title="{{ $item->dataMakam->nama }}">
                                        {{ $item->dataMakam->nama }}
                                        @if ($item->matchedData)
                                            <div class="text-[10px] text-amber-600 font-normal">Dipasangkan dgn: {{ $item->matchedData->nama }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-10 text-center text-slate-400 text-xs">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. Duplikat (Cyan) --}}
            <div class="daftar-audit bg-white border border-cyan-100 rounded-xl shadow-sm flex flex-col h-[420px] w-[240px] shrink-0 overflow-hidden hover:shadow-md transition-shadow"
                data-id="duplikat">
                <div class="px-4 py-3 bg-cyan-50/80 border-b border-cyan-100 shrink-0">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-[12.5px] text-cyan-800">Duplikat</span>
                        <span class="text-[10px] font-bold bg-white text-cyan-600 border border-cyan-200 px-2 py-0.5 rounded-full shadow-sm">{{ number_format(count($duplikat), 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-2">
                        <input type="text" placeholder="Cari nama…"
                            class="cari-daftar w-full h-8 text-[11.5px] px-2.5 border border-cyan-100 rounded-md bg-white focus:outline-none focus:border-cyan-400">
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400">
                        {{ $infoModel(count($duplikat), $displayData->total_duplikat_pusat + $displayData->total_duplikat_cabang) }}
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div class="w-full text-left text-[12.5px]" data-tag="duplikat">
                        @forelse($duplikat as $item)
                            <div class="px-4 py-3 border-b border-slate-100 hover:bg-cyan-50/40 transition-colors" data-nama="{{ $item->dataMakam->nama }}">
                                <div class="font-medium text-slate-800 truncate" title="{{ $item->dataMakam->nama }}">{{ $item->dataMakam->nama }}</div>
                                <div class="text-[10px] text-cyan-700 mt-0.5 uppercase font-semibold tracking-wide">
                                    {{ $item->status === 'duplikat_pusat' ? 'Di data pusat' : 'Di data cabang' }}
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-10 text-center text-slate-400 text-xs">Tidak ada data</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- 5. Pusat Tidak Ada (Oranye) --}}
            <div class="daftar-audit bg-white border border-orange-100 rounded-xl shadow-sm flex flex-col h-[420px] w-[240px] shrink-0 overflow-hidden hover:shadow-md transition-shadow"
                data-id="pusat_tidak_ada">
                <div class="px-4 py-3 bg-orange-50/80 border-b border-orange-100 shrink-0">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-[12.5px] text-orange-800">Pusat Tdk Ada</span>
                        <span class="text-[10px] font-bold bg-white text-orange-600 border border-orange-200 px-2 py-0.5 rounded-full shadow-sm">{{ number_format(count($pusatTidakAda), 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-2">
                        <input type="text" placeholder="Cari nama…"
                            class="cari-daftar w-full h-8 text-[11.5px] px-2.5 border border-orange-100 rounded-md bg-white focus:outline-none focus:border-orange-400">
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400">
                        {{ $infoModel(count($pusatTidakAda), $displayData->total_pusat_tidak_ada ?? 0) }}
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse text-[12.5px]" data-tag="pusat_tidak_ada">
                        <tbody>
                            @forelse ($pusatTidakAda as $item)
                                <tr class="hover:bg-orange-50/40 transition-colors" data-nama="{{ $item->dataMakam->nama }}">
                                    <td class="px-4 py-3 border-b border-slate-100 text-slate-600 font-medium truncate max-w-[1px] w-full" title="{{ $item->dataMakam->nama }}">
                                        {{ $item->dataMakam->nama }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-10 text-center text-slate-400 text-xs">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 6. Cabang Tidak Ada (Merah) --}}
            <div class="daftar-audit bg-white border border-red-100 rounded-xl shadow-sm flex flex-col h-[420px] w-[240px] shrink-0 overflow-hidden hover:shadow-md transition-shadow"
                data-id="cabang_tidak_ada">
                <div class="px-4 py-3 bg-red-50/80 border-b border-red-100 shrink-0">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-[12.5px] text-red-800">Cabang Tdk Ada</span>
                        <span class="text-[10px] font-bold bg-white text-red-600 border border-red-200 px-2 py-0.5 rounded-full shadow-sm">{{ number_format(count($cabangTidakAda), 0, ',', '.') }}</span>
                    </div>
                    <div class="mt-2">
                        <input type="text" placeholder="Cari nama…"
                            class="cari-daftar w-full h-8 text-[11.5px] px-2.5 border border-red-100 rounded-md bg-white focus:outline-none focus:border-red-400">
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400">
                        {{ $infoModel(count($cabangTidakAda), $displayData->total_cabang_tidak_ada ?? 0) }}
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse text-[12.5px]" data-tag="cabang_tidak_ada">
                        <tbody>
                            @forelse ($cabangTidakAda as $item)
                                <tr class="hover:bg-red-50/40 transition-colors" data-nama="{{ $item->dataMakam->nama }}">
                                    <td class="px-4 py-3 border-b border-slate-100 text-slate-600 font-medium truncate max-w-[1px] w-full" title="{{ $item->dataMakam->nama }}">
                                        {{ $item->dataMakam->nama }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-10 text-center text-slate-400 text-xs">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
