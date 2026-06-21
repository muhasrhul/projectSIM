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
        $now = Carbon::now('Asia/Makassar');
        $today = $now->format('Y-m-d');
        $dataUang = [];
        $dataTanggal = [];

        if ($filter === 'month') {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
            $label = 'Bulan ' . $now->translatedFormat('F Y');

            // Cache data hari-hari yang sudah lewat (tidak akan berubah)
            $pastCashFlows = cache()->remember('chart_income_past_' . $today, 86400, function () use ($now, $today) {
                return CashFlow::selectRaw('DATE(date) as date, SUM(amount) as total')
                    ->whereMonth('date', $now->month)
                    ->whereYear('date', $now->year)
                    ->where('type', 'income')
                    ->whereDate('date', '<', $today)
                    ->groupBy('date')
                    ->pluck('total', 'date');
            });

            // Data hari ini selalu fresh (tidak di-cache)
            $todayTotal = CashFlow::whereDate('date', $today)
                ->where('type', 'income')
                ->sum('amount');

            $cashFlows = is_array($pastCashFlows) ? $pastCashFlows : $pastCashFlows->toArray();
            $cashFlows[$today] = $todayTotal;

            // Loop sampai akhir bulan, per hari
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dateKey = $currentDate->format('Y-m-d');
                $dataTanggal[] = $currentDate->format('d M');
                $dataUang[] = $currentDate->lte($now) ? ($cashFlows[$dateKey] ?? 0) : null;
                $currentDate->addDay();
            }

        } elseif ($filter === 'year') {
            $label = 'Tahun ' . $now->year;

            // Cache bulan-bulan yang sudah lewat
            $pastMonths = cache()->remember('chart_income_year_past_' . $now->format('Y-m'), 86400, function () use ($now) {
                return CashFlow::selectRaw('MONTH(date) as month, SUM(amount) as total')
                    ->whereYear('date', $now->year)
                    ->where('type', 'income')
                    ->where('date', '<', $now->copy()->startOfMonth())
                    ->groupBy('month')
                    ->pluck('total', 'month');
            });

            // Bulan ini selalu fresh
            $thisMonthTotal = CashFlow::whereYear('date', $now->year)
                ->whereMonth('date', $now->month)
                ->where('type', 'income')
                ->sum('amount');

            $cashFlows = is_array($pastMonths) ? $pastMonths : $pastMonths->toArray();
            $cashFlows[$now->month] = $thisMonthTotal;

            for ($i = 1; $i <= 12; $i++) {
                $dataTanggal[] = Carbon::create($now->year, $i, 1)->translatedFormat('M');
                // Bulan yang belum terjadi = null
                $dataUang[] = $i <= $now->month ? ($cashFlows[$i] ?? 0) : null;
            }

        } else {
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
            'plugins' => [
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}