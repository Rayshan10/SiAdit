<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Logika Tombol Audit
        const form = document.querySelector('form');
        const auditBtn = document.getElementById('audit-btn');
        const auditText = document.getElementById('audit-text');
        const auditIcon = document.getElementById('audit-icon');
        const loadingIcon = document.getElementById('loading-icon');

        if (form && auditBtn) {
            form.addEventListener('submit', () => {
                auditBtn.disabled = true;
                auditBtn.classList.add('opacity-80', 'cursor-not-allowed');
                auditText.textContent = 'Sedang Generate Audit...';
                auditIcon.classList.add('hidden');
                loadingIcon.classList.remove('hidden');
            });
        }

        // Pencarian sederhana per kolom di tampilan rincian audit
        document.querySelectorAll('.cari-daftar').forEach((input) => {
            const panel = input.closest('.daftar-audit');

            if (!panel) return;

            input.addEventListener('input', () => {
                const kata = input.value.trim().toUpperCase();

                panel.querySelectorAll('[data-nama]').forEach((el) => {
                    const nama = (el.dataset.nama || '').toUpperCase();
                    el.style.display = (!kata || nama.includes(kata)) ? '' : 'none';
                });
            });
        });

        // Logika Chart.js
        @if (isset($displayData))
            const ctx = document.getElementById('auditChart');

            const chartValues = [
                {{ $displayData->total_match ?? 0 }},
                {{ $displayData->total_tahun_beda ?? 0 }},
                {{ $displayData->total_fuzzy_match ?? 0 }},
                {{ $displayData->total_pusat_tidak_ada ?? 0 }},
                {{ $displayData->total_cabang_tidak_ada ?? 0 }},
                {{ $displayData->total_duplikat_pusat ?? 0 }},
                {{ $displayData->total_duplikat_cabang ?? 0 }}
            ];

            const totalNilai = chartValues.reduce((a, b) => a + b, 0);

            if (ctx && totalNilai > 0 && typeof Chart !== 'undefined') {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: [
                            'Match Full', 'Tahun Beda', 'Fuzzy Match',
                            'Pusat Tidak Ada', 'Cabang Tidak Ada',
                            'Duplikat Pusat', 'Duplikat Cabang'
                        ],
                        datasets: [{
                            data: chartValues,
                            backgroundColor: [
                                '#22c55e', '#f59e0b', '#a855f7',
                                '#f97316', '#ef4444', '#3b82f6', '#06b6d4'
                            ],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                    font: {
                                        family: "'Plus Jakarta Sans', sans-serif",
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        let value = context.raw || 0;
                                        let percentage = ((value / totalNilai) * 100).toFixed(1) +
                                            '%';
                                        return ` ${label}: ${value} data (${percentage})`;
                                    }
                                }
                            }
                        },
                        cutout: '70%'
                    }
                });
            }
        @endif
    });
</script>
