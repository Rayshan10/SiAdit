@extends('layouts.app')

@section('title', 'Upload File')

@push('styles')
    <style>
        /* Menyembunyikan input file bawaan browser agar bisa diganti dengan zona drag & drop */
        .file-zone input[type=file] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        /* Kustomisasi icon panah pada tag select bawaan */
        select.custom-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }
    </style>
@endpush

@section('content')
    <div class="flex justify-center w-full">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            {{-- Header Form --}}
            <div class="bg-[#1A3A5C] px-8 py-6">
                <h2 class="text-white text-lg font-semibold tracking-tight">Upload & Audit Data Makam</h2>
                <p class="text-[#A8C4E0] text-sm mt-1">Unggah file pusat (Dinas) dan cabang (TPU) untuk memulai sinkronisasi
                </p>
            </div>

            {{-- ALERT ERROR VALIDASI --}}
            @if ($errors->any())
                <div class="mx-8 mt-6 mb-2 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
                    <div class="text-sm font-semibold mb-1">Periksa kembali formulir:</div>
                    <ul class="list-disc list-inside text-sm space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ALERT ERROR PROSES --}}
            @if (session('error'))
                <div
                    class="mx-8 mt-6 mb-2 flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
                    <svg class="shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div class="text-sm font-medium leading-relaxed">{{ session('error') }}</div>
                </div>
            @endif

            @if (session('success'))
                <div
                    class="mx-8 mt-6 mb-2 flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">

                    <svg class="shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">

                        <path d="M20 6L9 17l-5-5"></path>

                    </svg>

                    <div class="text-sm font-medium leading-relaxed">

                        {{ session('success') }}

                    </div>
                </div>
            @endif

            {{-- Status pemrosesan antrean --}}
            @foreach ($prosesImport as $proses)
                <div data-muat-ulang-otomatis
                    class="mx-8 mt-6 mb-2 flex items-start gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800">
                    <svg class="shrink-0 mt-1 animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <div class="text-sm leading-relaxed">
                        <span class="font-semibold">Impor {{ $proses['tpu'] }} sedang diproses di antrean latar belakang.</span>
                        Dimulai {{ date('H:i', $proses['sejak']) }}; halaman memperbarui otomatis tiap 5 detik.
                        @if (time() - $proses['sejak'] > 300)
                            <span class="block text-xs mt-1 opacity-80">
                                Sudah lama tidak berubah? Pastikan pemroses antrean (queue worker) masih berjalan di server.
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($pesanGagalImport)
                <div class="mx-8 mt-6 mb-2 flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
                    <svg class="shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div class="text-sm font-medium leading-relaxed">
                        <span class="font-semibold">Impor terakhir gagal dijalankan.</span>
                        <span class="block text-xs mt-1">{{ $pesanGagalImport }}</span>
                        <span class="block text-xs mt-1">Coba unggah ulang dua berkas; bila berulang, minta Admin menjalankan impor lewat perintah <code>siadit:import-excel</code>.</span>
                    </div>
                </div>
            @endif

            @if ($pesanHasilImport)
                <div class="mx-8 mt-6 mb-2 flex items-start gap-3 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
                    <svg class="shrink-0 mt-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6L9 17l-5-5"></path>
                    </svg>
                    <div class="text-sm leading-relaxed">
                        <span class="font-semibold">{{ $pesanHasilImport }}</span>
                        <span class="block text-xs mt-1">Lanjut ke menu Hasil Audit lalu klik Jalankan Audit — prosesnya juga berjalan di antrean latar belakang.</span>
                    </div>
                </div>
            @endif

            {{-- Form Body --}}
            <form action="/upload" method="POST" enctype="multipart/form-data" class="px-8 py-7 flex flex-col gap-5">
                @csrf

                {{-- TPU --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-slate-700">
                        TPU <span class="text-red-500">*</span>
                    </label>
                    @if ($tpus->count() === 1 && auth()->user()->role !== 'admin')
                        {{-- Kepala TPU dengan satu TPU: tampilan info, bukan dropdown --}}
                        <input type="hidden" name="tpu_id" value="{{ $tpus->first()->id }}">
                        <div class="w-full h-10 border border-slate-200 rounded-lg px-3 text-sm bg-slate-100 flex items-center text-slate-700 font-medium">
                            {{ $tpus->first()->nama }}
                            <span class="ml-auto text-[10px] uppercase tracking-wide text-slate-400 border border-slate-200 rounded px-1.5 py-0.5">Tetap</span>
                        </div>
                    @else
                        <select name="tpu_id"
                            class="custom-select w-full h-10 border border-slate-200 rounded-lg px-3 text-sm text-slate-800 bg-slate-50 focus:outline-none focus:border-[#1A3A5C] focus:ring-1 focus:ring-[#1A3A5C] transition">

                            <option value="">— Pilih TPU —</option>

                            @foreach ($tpus as $tpu)
                                <option value="{{ $tpu->id }}" @selected(old('tpu_id') == $tpu->id)>{{ $tpu->nama }}</option>
                            @endforeach

                        </select>
                    @endif
                    @error('tpu_id')
                        <span class="text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <div class="border-t border-slate-100"></div>

                {{-- File Pusat --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-slate-700">
                        File Pusat <span class="text-red-500">*</span>
                    </label>
                    <div id="zone-pusat"
                        class="file-zone relative border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 flex flex-col items-center gap-2 py-5 px-4 hover:border-[#1A3A5C] hover:bg-blue-50/50 transition">
                        <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="text-[#1A3A5C]" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                                <line x1="12" y1="18" x2="12" y2="12" />
                                <line x1="9" y1="15" x2="15" y2="15" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-slate-700">Klik atau seret file ke sini</span>
                        <span id="sub-pusat" class="text-xs text-slate-400">XLS, XLSX, CSV — maks. 10 MB</span>
                        <span id="name-pusat" class="text-xs text-green-600 font-medium hidden"></span>
                        <input type="file" name="file_pusat" accept=".xls,.xlsx,.csv" id="input-pusat" required>
                    </div>
                    @error('file_pusat')
                        <span class="text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- File Cabang --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-slate-700">
                        File Cabang <span class="text-red-500">*</span>
                    </label>
                    <div id="zone-cabang"
                        class="file-zone relative border-2 border-dashed border-slate-200 rounded-xl bg-slate-50 flex flex-col items-center gap-2 py-5 px-4 hover:border-[#1A3A5C] hover:bg-blue-50/50 transition">
                        <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="text-[#1A3A5C]" width="18" height="18" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <polyline points="14 2 14 8 20 8" />
                                <line x1="12" y1="18" x2="12" y2="12" />
                                <line x1="9" y1="15" x2="15" y2="15" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-slate-700">Klik atau seret file ke sini</span>
                        <span id="sub-cabang" class="text-xs text-slate-400">XLS, XLSX, CSV — maks. 10 MB</span>
                        <span id="name-cabang" class="text-xs text-green-600 font-medium hidden"></span>
                        <input type="file" name="file_cabang" accept=".xls,.xlsx,.csv" id="input-cabang" required>
                    </div>
                    @error('file_cabang')
                        <span class="text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Submit Button --}}
                <button id="submit-btn" type="submit"
                    class="w-full h-11 bg-[#1A3A5C] hover:bg-[#162F4A] active:scale-[0.99] text-white text-sm font-semibold rounded-lg flex items-center justify-center gap-2 transition mt-2">

                    <svg id="submit-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">

                        <polyline points="16 16 12 12 8 16" />
                        <line x1="12" y1="12" x2="12" y2="21" />
                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3" />

                    </svg>

                    <svg id="loading-icon" class="hidden animate-spin" width="16" height="16"
                        viewBox="0 0 24 24" fill="none">

                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4">
                        </circle>

                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                        </path>

                    </svg>

                    <span id="submit-text">
                        Upload
                    </span>
                </button>
                <p class="text-center text-xs text-slate-400 -mt-2">Pastikan semua field telah diisi sebelum mengunggah</p>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            function setupZone(inputId, zoneId, nameId, subId) {
                const input = document.getElementById(inputId);
                const zone = document.getElementById(zoneId);
                const nameEl = document.getElementById(nameId);
                const subEl = document.getElementById(subId);

                if (!input || !zone) return;

                function setFile(file) {
                    nameEl.textContent = file.name;
                    nameEl.classList.remove('hidden');
                    subEl.classList.add('hidden');

                    // Mengubah warna border dan background saat file berhasil dipilih
                    zone.classList.remove('border-slate-200', 'bg-slate-50');
                    zone.classList.add('border-green-400', 'bg-green-50/50', 'border-solid');
                }

                input.addEventListener('change', () => {
                    if (input.files[0]) setFile(input.files[0]);
                });

                // Efek visual saat file diseret di atas area (Drag Over)
                zone.addEventListener('dragover', e => {
                    e.preventDefault();
                    zone.classList.add('border-[#1A3A5C]', 'bg-blue-50/50');
                });

                // Mengembalikan efek visual jika file batal diseret (Drag Leave)
                zone.addEventListener('dragleave', () => {
                    zone.classList.remove('border-[#1A3A5C]', 'bg-blue-50/50');
                });

                // Menangkap file yang dijatuhkan (Drop)
                zone.addEventListener('drop', e => {
                    e.preventDefault();
                    zone.classList.remove('border-[#1A3A5C]', 'bg-blue-50/50');
                    if (e.dataTransfer.files[0]) {
                        input.files = e.dataTransfer.files;
                        setFile(e.dataTransfer.files[0]);
                    }
                });
            }

            setupZone('input-pusat', 'zone-pusat', 'name-pusat', 'sub-pusat');
            setupZone('input-cabang', 'zone-cabang', 'name-cabang', 'sub-cabang');
        });

        const form = document.querySelector('form');

        const submitBtn =
            document.getElementById('submit-btn');

        const submitText =
            document.getElementById('submit-text');

        const submitIcon =
            document.getElementById('submit-icon');

        const loadingIcon =
            document.getElementById('loading-icon');

        form.addEventListener('submit', () => {

            submitBtn.disabled = true;

            submitBtn.classList.add(
                'opacity-80',
                'cursor-not-allowed'
            );

            submitText.textContent =
                'Mengupload...';

            submitIcon.classList.add('hidden');

            loadingIcon.classList.remove('hidden');
        });
    </script>

    @if (count($prosesImport) > 0)
        <script>
            // Impor diproses worker di latar belakang: muat ulang halaman
            // periodik sampai proses selesai.
            setTimeout(() => location.reload(), 5000);
        </script>
    @endif
@endpush
