<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\LineChartWidget;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JamTeramaiChart extends LineChartWidget
{
    protected static ?string $heading = 'Analisis Jam Teramai';
    protected static ?int $sort = 3;
    
    // Polling setiap 60 detik
    protected static ?string $pollingInterval = '60s';
    
    // Lazy load widget
    protected static bool $isLazy = true;
    
    // PERMISSION: Hanya Super Admin yang bisa lihat widget ini
    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }
    
    // 1. MEMBUAT GRAFIK FULL KE SAMPING
    protected int | string | array $columnSpan = 'full';
    
    // Filter untuk memilih periode
    public ?string $filter = 'month';

    // 2. MEMBATASI TINGGI GRAFIK AGAR TIDAK JAUH SCROLL (250px)
    protected function getMaxHeight(): ?string
    {
        return '250px';
    }

    protected function getData(): array
    {
        $filter = $this->filter;
        
        // Cache berdasarkan filter
        return cache()->remember('chart_jam_teramai_' . $filter, 300, function () use ($filter) {
            $now = Carbon::now('Asia/Makassar');
            
            // Query berdasarkan filter
            $query = Attendance::select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as total'));
            
            if ($filter === 'month') {
                // Data bulan ini saja
                $startOfMonth = $now->copy()->startOfMonth();
                $endOfMonth = $now->copy()->endOfMonth();
                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
                $label = 'Bulan ' . $now->translatedFormat('F Y');
            } elseif ($filter === 'year') {
                // Data tahun ini saja
                $query->whereYear('created_at', $now->year);
                $label = 'Tahun ' . $now->year;
            } else {
                // Semua data
                $label = 'Semua Waktu';
            }
            
            $data = $query->groupBy('hour')
                ->orderBy('hour')
                ->pluck('total', 'hour')
                ->all();

            // Menyusun label jam (00:00 - 23:00)
            $labels = [];
            $values = [];
            for ($i = 0; $i < 24; $i++) {
                $labels[] = sprintf('%02d:00', $i);
                $values[] = $data[$i] ?? 0;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Total Member Latihan (' . $label . ')',
                        'data' => $values,
                        'borderColor' => '#F59E0B',
                        'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                        'fill' => true,
                        'tension' => 0.4,
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

    // 3. PENGATURAN TAMBAHAN AGAR GRAFIK LEBIH CEPER
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'ticks' => [
                        'stepSize' => 1, // Agar skala angka di samping (1, 2, 3) lebih rapi
                    ],
                ],
            ],
        ];
    }
}