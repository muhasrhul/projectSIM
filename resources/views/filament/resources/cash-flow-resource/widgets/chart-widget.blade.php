<x-filament::widget>
    <div wire:ignore>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-4"
            x-data="laporanChart()"
            x-init="init()"
            @filter-updated.window="updateChart($event.detail)">
            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3">Grafik Arus Kas</p>
            <div style="position: relative; height: 280px;">
                <canvas id="laporanArusKasChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        if (typeof Chart === 'undefined') {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
            s.onload = () => window.dispatchEvent(new Event('chartjs-ready'));
            document.head.appendChild(s);
        }

        function laporanChart() {
            return {
                chart: null,
                month: {{ \Carbon\Carbon::now('Asia/Makassar')->month }},
                year: {{ \Carbon\Carbon::now('Asia/Makassar')->year }},

                async init() {
                    if (typeof Chart === 'undefined') {
                        window.addEventListener('chartjs-ready', () => this.loadChart(), { once: true });
                    } else {
                        await this.loadChart();
                    }
                },

                async updateChart(detail) {
                    this.month = detail[0] ?? detail.month ?? this.month;
                    this.year  = detail[1] ?? detail.year  ?? this.year;
                    await this.loadChart();
                },

                async loadChart() {
                    const response = await fetch(`/admin/laporan-arus-kas-data?month=${this.month}&year=${this.year}`);
                    const data = await response.json();

                    if (this.chart) {
                        this.chart.data.labels = data.labels;
                        this.chart.data.datasets[0].data = data.pemasukan;
                        this.chart.data.datasets[1].data = data.pengeluaran;
                        this.chart.update();
                        return;
                    }

                    const ctx = document.getElementById('laporanArusKasChart').getContext('2d');
                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'Pemasukan',
                                    data: data.pemasukan,
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                                    fill: true, tension: 0.4, pointRadius: 3,
                                },
                                {
                                    label: 'Pengeluaran',
                                    data: data.pengeluaran,
                                    borderColor: '#ef4444',
                                    backgroundColor: 'rgba(239, 68, 68, 0.08)',
                                    fill: true, tension: 0.4, pointRadius: 3,
                                }
                            ]
                        },
                        options: {
                            maintainAspectRatio: false,
                            responsive: true,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: { display: true },
                                tooltip: {
                                    enabled: true,
                                    mode: 'index',
                                    intersect: false,
                                    callbacks: {
                                        label: (ctx) => ` ${ctx.dataset.label}: Rp ${(ctx.parsed.y || 0).toLocaleString('id-ID')}`
                                    }
                                }
                            },
                            scales: {
                                y: { ticks: { callback: (v) => 'Rp ' + v.toLocaleString('id-ID') } }
                            }
                        }
                    });
                }
            }
        }
    </script>
</x-filament::widget>
