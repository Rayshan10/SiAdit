<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">Laporan Sinkronisasi Data</h2>
        <p class="text-sm text-slate-500 mt-1">Hasil perbandingan data makam Pusat dan Cabang.</p>
    </div>

    <form action="{{ url('/audit/generate') }}" method="POST" onsubmit="return bolehJalankanAudit(event)">
        @csrf
        @php
            // Belum ada hasil tersimpan (audit pertama, atau habis di-reset
            // oleh import baru): pakai TPU kandidat dari data yang sudah
            // diunggah agar tombol tetap bisa dipakai.
            $tpuTerpilih = $latestResult?->tpu_id ?? $tpuAuditPertama ?? null;
            $bisaJalankan = !empty($tpuTerpilih);
        @endphp
        <input type="hidden" name="tpu_id" value="{{ $tpuTerpilih }}">
        <button id="audit-btn" type="submit"
            @unless ($bisaJalankan) disabled @endunless
            @unless ($bisaJalankan) title="Unggah data pusat dan cabang melalui menu Upload File terlebih dahulu" @endunless
            class="h-10 px-4 bg-[#1A3A5C] hover:bg-[#162F4A] text-white text-sm font-semibold rounded-lg flex items-center gap-2 transition shadow-sm disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:bg-[#1A3A5C]">
            {{-- ICON NORMAL --}}
            <svg id="audit-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10" />
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
            </svg>
            {{-- ICON LOADING --}}
            <svg id="loading-icon" class="hidden animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span id="audit-text">{{ $latestResult ? 'Jalankan Audit Ulang' : 'Jalankan Audit' }}</span>
        </button>
    </form>
</div>

<script>
    const auditBisaJalankan = @js($bisaJalankan);

    function bolehJalankanAudit(event) {
        if (!auditBisaJalankan) {
            alert('Unggah data pusat dan cabang melalui menu Upload File terlebih dahulu.');
            event.preventDefault();
            return false;
        }
        return true;
    }
</script>
