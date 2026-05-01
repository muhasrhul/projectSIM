<?php

namespace App\Filament\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\CashFlow; 
use Carbon\Carbon;

class IncomeChart extends LineChartWidget
{
    protected static ?string $heading = 'Grafik Pendapatan';
    protected static ?int $sort = 2;
    
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
        return cache()->remember('chart_income_' . $filter, 600, function () use ($filter) {
            $dataUang = [];
            $dataTanggal = [];
            
            $now = Carbon::now('Asia/Makassar');
            
            if ($filter === 'month') {
                // Data bulan ini
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                $label = 'Bulan ' . $now->translatedFormat('F Y');
                
                // Ambil data CashFlow untuk bulan ini, group by tanggal
                $cashFlows = CashFlow::selectRaw('DATE(date) as date, SUM(amount) as total')
                    ->whereMonth('date', $now->month)
                    ->whereYear('date', $now->year)
                    ->where('type', 'income')
                    ->groupBy('date')
                    ->pluck('total', 'date');

                // Loop setiap hari dalam bulan ini
                $currentDate = $startDate->copy();
                while ($currentDate <= $endDate) {
                    $dateKey = $currentDate->format('Y-m-d');
                    $dataTanggal[] = $currentDate->format('d M');
                    $dataUang[] = $cashFlows[$dateKey] ?? 0;
                    $currentDate->addDay();
                }
                
            } elseif ($filter === 'year') {
                // Data tahun ini per bulan
                $label = 'Tahun ' . $now->year;
                
                $cashFlows = CashFlow::selectRaw('MONTH(date) as month, SUM(amount) as total')
                    ->whereYear('date', $now->year)
                    ->where('type', 'income')
                    ->groupBy('month')
                    ->pluck('total', 'month');
                
                // Loop 12 bulan
                for ($i = 1; $i <= 12; $i++) {
                    $dataTanggal[] = Carbon::create($now->year, $i, 1)->translatedFormat('M');
                    $dataUang[] = $cashFlows[$i] ?? 0;
                }
                
            } else {
                // Semua waktu per bulan
                $label = 'Semua Waktu';
                
                $cashFlows = CashFlow::selectRaw('DATE_FORMAT(date, "%Y-%m") as month, SUM(amount) as total')
                    ->where('type', 'income')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->pluck('total', 'month');
                
                foreach ($cashFlows as $month => $total) {
                    $dataTanggal[] = Carbon::parse($month . '-01')->translatedFormat('M Y');
                    $dataUang[] = $total;
                }
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Pendapatan (' . $label . ')',
                        'data' => $dataUang,
                        'borderColor' => '#F59E0B',
                        'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                        'fill' => true,
                        'tension' => 0.4, 
                    ],
                ],
                'labels' => $dataTanggal,
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

    // 3. PENGATURAN TAMBAHAN AGAR GRAFIK LEBIH CEPER/PENDEK
    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'ticks' => [
                        'display' => true,
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return "Pendapatan: Rp " + context.parsed.y.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
        ];
    }
}