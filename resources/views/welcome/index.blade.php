<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang — SiAdit</title>
    <meta name="description"
        content="SiAdit — Sistem Informasi Audit Data Pemakaman TPU Pandu, Dinas Cipta Karya &amp; Tata Ruang Kota Bandung.">

    {{-- CSS hasil kompilasi Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

    {{-- Header ringkas --}}
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 bg-blue-600 rounded-md flex items-center justify-center shrink-0">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                    </svg>
                </div>
                <div>
                    <span class="text-[#1A3A5C] font-semibold text-[15px]">SiAdit</span>
                    <span class="block text-slate-400 text-[10px] leading-tight">Sistem Informasi Audit Data</span>
                </div>
            </div>

            <a href="{{ route('login') }}"
                class="h-9 px-4 bg-[#1A3A5C] hover:bg-[#162F4A] text-white text-sm font-semibold rounded-lg flex items-center gap-2 transition">
                Masuk
            </a>
        </div>
    </header>

    <main class="flex-1">
        {{-- Hero --}}
        <section class="max-w-6xl mx-auto px-6 pt-12 pb-10 grid lg:grid-cols-2 gap-10 items-center">
            <div>
                <span
                    class="inline-flex items-center h-7 px-3 bg-blue-50 border border-blue-100 text-[#1A3A5C] text-xs font-semibold rounded-full">
                    Dinas Cipta Karya &amp; Tata Ruang (BTKR) Kota Bandung
                </span>

                <h1 class="text-3xl sm:text-4xl font-bold text-[#1A3A5C] leading-tight mt-4">
                    Audit &amp; Rekonsiliasi Data Pemakaman TPU Pandu
                </h1>

                <p class="text-slate-600 leading-relaxed mt-4">
                    Menggantikan pencocokan manual 47 ribu baris di Excel: SiAdit membandingkan
                    data pusat (Dinas) dengan data pengelolaan TPU (cabang) secara otomatis —
                    termasuk nama yang mirip, deteksi duplikat, hingga laporan siap unduh.
                </p>

                <div class="mt-6">
                    <a href="{{ route('login') }}"
                        class="h-11 px-6 bg-[#1A3A5C] hover:bg-[#162F4A] active:scale-[0.99] text-white text-sm font-semibold rounded-lg inline-flex items-center gap-2 transition shadow-sm">
                        Masuk ke Aplikasi
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                    </a>

                    <p class="text-xs text-slate-400 mt-3">
                        Akun dibuat oleh Admin melalui menu Kelola User — tidak ada pendaftaran mandiri.
                    </p>
                </div>

                {{-- Statistik ringkas dari hasil audit data nyata --}}
                <div class="grid grid-cols-3 gap-3 mt-8">
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <div class="text-lg font-bold text-[#1A3A5C]">23.662</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">baris data pusat diaudit</div>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <div class="text-lg font-bold text-emerald-600">88,5%</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">tingkat sinkronisasi</div>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <div class="text-lg font-bold text-[#1A3A5C]">±20 dtk</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">audit 47 ribu baris</div>
                    </div>
                </div>
            </div>

            <div>
                <img src="{{ asset('images/hasil-audit.png') }}" alt="Tampilan hasil audit SiAdit"
                    class="w-full rounded-xl shadow-lg border border-slate-200">
            </div>
        </section>

        {{-- Fitur --}}
        <section class="max-w-6xl mx-auto px-6 pb-14">
            <div class="grid sm:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center mb-3">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1A3A5C"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-800 text-sm">Impor berantrean yang aman</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mt-1.5">
                        Dua berkas Excel diproses worker antrean dalam satu transaksi
                        (semua-atau-tidak-sama-sekali) dan menggantikan data lama per sumber.
                    </p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center mb-3">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1A3A5C"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-800 text-sm">Mesin audit tiga tahap</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mt-1.5">
                        Match persis, lalu nama mirip (Levenshtein ≤ 3), dengan penjaga agar
                        satu baris cabang tidak pernah dipakai dua kali.
                    </p>
                </div>

                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center mb-3">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1A3A5C"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-slate-800 text-sm">Laporan siap sebar</h3>
                    <p class="text-xs text-slate-500 leading-relaxed mt-1.5">
                        Ringkasan, chart donut, rincian per kategori dengan pencarian, serta
                        unduhan Excel multi-sheet untuk lampiran rapat.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="max-w-6xl mx-auto px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-1">
            <p class="text-xs text-slate-500">
                SiAdit — dibangun selama program magang di Dinas Cipta Karya &amp; Tata Ruang (BTKR) Kota Bandung.
            </p>
            <p class="text-xs text-slate-400">TPU Pandu · 2026</p>
        </div>
    </footer>

</body>

</html>
