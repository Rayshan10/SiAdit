{{-- Alert Sukses / Error --}}
@if (session('error'))
    <div class="mb-5 flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span class="font-medium text-sm">{{ session('error') }}</span>
    </div>
@elseif (session('success'))
    <div class="mb-5 flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-xl text-green-700">
        <div class="flex items-center gap-3">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <span class="font-medium text-sm">{{ session('success') }}</span>
        </div>
    </div>
@endif