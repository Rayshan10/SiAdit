<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SiAdit</title>

    {{-- CSS hasil kompilasi Vite (ganti Tailwind CDN agar siap produksi) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Scrollbar tipis untuk daftar pada tampilan rincian audit */
        .custom-scrollbar {
            scrollbar-width: thin;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(100, 116, 139, .35);
            border-radius: 9999px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
    </style>

    @stack('styles')
</head>

<body class="bg-slate-50 text-slate-800 flex flex-col h-screen overflow-hidden">

    <div class="flex flex-1 h-full overflow-hidden relative">

        {{-- Backdrop halaman mobile saat sidebar terbuka --}}
        <div id="sidebar-backdrop"
            class="hidden fixed inset-0 bg-slate-900/50 z-20 lg:hidden"
            onclick="toggleSidebar()"></div>

        <aside id="sidebar"
            class="w-[220px] bg-[#1A3A5C] flex flex-col shrink-0 transition-[width] duration-300 overflow-hidden z-30">
            @php
                $namaLengkap = auth()->user()->name ?? '';
            @endphp

            <div class="flex items-center gap-2.5 px-4 pt-5 pb-4 border-b border-white/10">
                <div class="w-7 h-7 bg-blue-600 rounded-md flex items-center justify-center shrink-0">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                    </svg>
                </div>
                <div class="whitespace-nowrap">
                    <span class="text-white font-semibold text-[15px]">SiAdit</span>
                    <span class="block text-white/50 text-[10px] leading-tight">Sistem Informasi Audit Data</span>
                </div>
            </div>

            <nav class="flex-1 px-2 pt-4 flex flex-col gap-1 overflow-y-auto custom-scrollbar">

    <div
        class="text-[10px] font-semibold text-white/40 tracking-widest uppercase px-2.5 pb-1 mt-1">
        Utama
    </div>

    {{-- DASHBOARD (SEMUA ROLE) --}}
    <a href="{{ route('dashboard') }}"
        class="flex items-center gap-2.5 px-2.5 py-2 rounded-md text-[13px] whitespace-nowrap transition-colors {{ request()->is('dashboard') ? 'bg-white/15 text-white font-medium' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">

        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="1.8"
            stroke-linecap="round" stroke-linejoin="round">

            <rect x="3" y="3" width="7" height="7" />
            <rect x="14" y="3" width="7" height="7" />
            <rect x="14" y="14" width="7" height="7" />
            <rect x="3" y="14" width="7" height="7" />

        </svg>

        Dashboard

    </a>

    {{-- KHUSUS ADMIN --}}
    @if(auth()->user()->role == 'admin')

        <a href="{{ route('users.index') }}"
            class="flex items-center gap-2.5 px-2.5 py-2 rounded-md text-[13px] whitespace-nowrap transition-colors {{ request()->is('users*') ? 'bg-white/15 text-white font-medium' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">

            <svg width="16" height="16"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                viewBox="0 0 24 24">

                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>

            </svg>

            Kelola User

        </a>

        <a href="{{ route('tpus.index') }}"
            class="flex items-center gap-2.5 px-2.5 py-2 rounded-md text-[13px] whitespace-nowrap transition-colors {{ request()->is('tpus*') ? 'bg-white/15 text-white font-medium' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">

            <svg width="16" height="16"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                viewBox="0 0 24 24">

                <path d="M3 21h18"/>
                <path d="M5 21V7l8-4 8 4v14"/>
                <path d="M9 9h.01"/>
                <path d="M9 13h.01"/>
                <path d="M9 17h.01"/>
                <path d="M15 9h.01"/>
                <path d="M15 13h.01"/>
                <path d="M15 17h.01"/>

            </svg>

            Kelola TPU

        </a>

    @endif

    {{-- ADMIN + KEPALA TPU --}}
    @if(in_array(auth()->user()->role, ['admin', 'kepala_tpu']))

        <a href="{{ url('/upload') }}"
            class="flex items-center gap-2.5 px-2.5 py-2 rounded-md text-[13px] whitespace-nowrap transition-colors {{ request()->is('upload') ? 'bg-white/15 text-white font-medium' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">

            <svg width="16" height="16" viewBox="0 0 24 24"
                fill="none" stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <polyline points="16 16 12 12 8 16" />
                <line x1="12" y1="12" x2="12" y2="21" />
                <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3" />

            </svg>

            Upload File

        </a>

    @endif

    {{-- SEMUA ROLE --}}
    <a href="{{ url('/audit') }}"
        class="flex items-center gap-2.5 px-2.5 py-2 rounded-md text-[13px] whitespace-nowrap transition-colors {{ request()->is('audit*') ? 'bg-white/15 text-white font-medium' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">

        <svg width="16" height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round">

            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
            <polyline points="14 2 14 8 20 8" />
            <line x1="16" y1="13" x2="8" y2="13" />
            <line x1="16" y1="17" x2="8" y2="17" />
            <polyline points="10 9 9 9 8 9" />

        </svg>

        Hasil Audit

    </a>

    {{-- LOGOUT --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf

        <button type="submit"
            class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-md text-[13px] whitespace-nowrap transition-colors text-white/65 hover:bg-red-500/20 hover:text-red-300">

            <svg width="16" height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round">

                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" y1="12" x2="9" y2="12" />

            </svg>

            Logout

        </button>

    </form>

</nav>

            @if(auth()->check())
                <div class="px-4 py-3 border-b border-white/10 text-white">
                    <div class="text-sm font-semibold truncate" title="{{ $namaLengkap }}">
                        {{ $namaLengkap }}
                    </div>
                    <div class="text-xs text-slate-300 truncate" title="{{ auth()->user()->email }}">
                        {{ auth()->user()->email }}
                    </div>
                </div>
            @endif
        </aside>

        <div class="flex-1 flex flex-col min-w-0 bg-slate-100">

            <header
                class="h-[52px] bg-white border-b border-slate-200 flex items-center justify-between px-5 shrink-0">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" aria-label="Buka/tutup menu"
                        class="p-1.5 rounded-md hover:bg-slate-100 transition-colors focus:outline-none text-slate-600">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <h1 class="font-semibold text-[15px]">@yield('title', 'Dashboard')</h1>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-full bg-[#1A3A5C] text-white flex items-center justify-center text-xs font-semibold cursor-default shadow-sm select-none">
                        {{-- Inisial pengguna aktif, bukan hardcode --}}
                        {{ collect(explode(' ', trim($namaLengkap)))
                            ->filter()
                            ->map(fn ($kata) => mb_substr(mb_strtoupper($kata), 0, 1))
                            ->take(2)
                            ->implode('') ?: 'SD' }}
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            const isMobile = window.innerWidth < 1024;
            const stateStore = isMobile ? 'siadit-sidebar-mobile' : 'siadit-sidebar-desktop';

            sidebar.classList.toggle('w-0');
            sidebar.classList.toggle('w-[220px]');

            const tertutup = sidebar.classList.contains('w-0');

            if (backdrop) {
                backdrop.classList.toggle('hidden', tertutup);
            }

            try {
                sessionStorage.setItem(stateStore, tertutup ? 'tutup' : 'buka');
            } catch (e) { /* abaikan bila penyimpanan diblok */ }
        }

        // Pulihkan keadaan sidebar saat halaman dimuat (per ukuran layar)
        (function () {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            let keadaan = null;

            try {
                keadaan = sessionStorage.getItem(window.innerWidth < 1024 ? 'siadit-sidebar-mobile' : 'siadit-sidebar-desktop');
            } catch (e) { /* abaikan */ }

            if (keadaan === 'tutup') {
                sidebar.classList.add('w-0');
                sidebar.classList.remove('w-[220px]');
            } else if (keadaan === 'buka' && window.innerWidth < 1024) {
                // Di layar kecil, sidebar terbuka menampilkan backdrop
                backdrop.classList.remove('hidden');
            }
        })();
    </script>

    @stack('scripts')
</body>

</html>
