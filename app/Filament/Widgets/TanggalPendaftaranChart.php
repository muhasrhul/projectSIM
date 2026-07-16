<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TanggalPendaftaranChart extends ChartWidget
{
    protected static ?string $heading = 'Tanggal Pendaftaran Member Terbanyak';
    protected static ?int $sort = 4; // Di bawah JamTeramaiChart (sort = 3)
    
    // Polling setiap 60 detik
    protected static ?string $pollingInterval = '60s';
    
    // Lazy load widget
    protected static bool $isLazy = true;
    
    // PERMISSION: Hanya Super Admin yang bisa lihat widget ini
    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }
    
    // Grafik full ke samping
    protected int | string | array $columnSpan = 'full';

    // Filter untuk memilih periode
    public ?string $filter = 'all';

    protected function getData(): array
    {
        $filter = $this->filter;
        
        // Cache berdasarkan filter
        return cache()->remember('chart_tanggal_pendaftaran_' . $filter, 300, function () use ($filter) {
            $now = Carbon::now('Asia/Makassar');
            
            // Query berdasarkan filter - TAMBAHKAN whereNotNull untuk menghindari error
            $query = Member::select(DB::raw('DAY(join_date) as day'), DB::raw('count(*) as total'))
                ->whereNotNull('join_date'); // Pastikan join_date tidak NULL
            
            if ($filter === 'month') {
                // Data bulan ini saja
                $query->whereMonth('join_date', $now->month)
                      ->whereYear('join_date', $now->year);
                $label = 'Bulan ' . $now->translatedFormat('F Y');
            } elseif ($filter === 'year') {
                // Data tahun ini saja
                $query->whereYear('join_date', $now->year);
                $label = 'Tahun ' . $now->year;
            } else {
                // Semua data
                $label = 'Semua Waktu';
            }
            
            $data = $query->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->all();

            // Menyusun label tanggal (1-31)
            $labels = [];
            $values = [];
            for ($i = 1; $i <= 31; $i++) {
                $labels[] = (string) $i;
                $values[] = $data[$i] ?? 0;
            }

            return [
                'datasets' => [
                    [
                        'type' => 'bar',
                        'label' => 'Jumlah Member Mendaftar (' . $label . ')',
                        'data' => $values,
                        'backgroundColor' => '#f59e0b', // Warna orange
                        'borderColor' => '#d97706',
                        'borderWidth' => 1,
                        'order' => 2,
                    ],
                    [
                        'type' => 'line',
                        'label' => 'Tren Pendaftaran',
                        'data' => $values,
                        'borderColor' => '#ef4444', // Warna merah
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'borderWidth' => 2,
                        'fill' => false,
                        'tension' => 0.4, // Membuat garis melengkung
                        'pointRadius' => 3,
                        'pointHoverRadius' => 5,
                        'order' => 1,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }
    
    protected function getFilters(): ?array
    {
        return [
            'month' => 'Bulan Ini',
            'year' => 'Tahun Ini',
            'all' => 'Semua Waktu',
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Mixed chart (bar + line)
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 5,
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Tanggal',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }
}
