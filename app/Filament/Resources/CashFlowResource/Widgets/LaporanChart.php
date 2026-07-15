<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\CashFlow;
use Carbon\Carbon;

class LaporanChart extends LineChartWidget
{
    protected static ?string $heading = 'Tren Pemasukan dan Pengeluaran';
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;

    public ?string $filter = null;

    protected $listeners = ['filterUpdated' => 'applyFilter'];

    protected function getMaxHeight(): ?string
    {
        return '280px';
    }

    protected function getFilters(): ?array
    {
        return null;
    }

    public function applyFilter(string $month, string $year): void
    {
        $this->filter = ($month && $year) ? $month . '-' . $year : 'all';
        $this->updateChartData();
    }

    protected function getData(): array
    {
        $now = Carbon::now('Asia/Makassar');

        // Mode semua waktu — tampilkan per bulan
        if (!$this->filter || $this->filter === 'all') {
            $pemasukan = CashFlow::selectRaw('DATE_FORMAT(date, "%Y-%m") as bulan, SUM(amount) as total')
                ->where('type', 'income')->groupByRaw('DATE_FORMAT(date, "%Y-%m")')
                ->orderBy('bulan')->pluck('total', 'bulan');

            $pengeluaran = CashFlow::selectRaw('DATE_FORMAT(date, "%Y-%m") as bulan, SUM(amount) as total')
                ->where('type', 'expense')->groupByRaw('DATE_FORMAT(date, "%Y-%m")')
                ->orderBy('bulan')->pluck('total', 'bulan');

            $allKeys = collect($pemasukan->keys())->merge($pengeluaran->keys())->unique()->sort()->values();
            $labels = []; $dataPemasukan = []; $dataPengeluaran = [];
            foreach ($allKeys as $key) {
                $labels[]          = Carbon::parse($key . '-01')->translatedFormat('M Y');
                $dataPemasukan[]   = (float)($pemasukan[$key] ?? 0);
                $dataPengeluaran[] = (float)($pengeluaran[$key] ?? 0);
            }
        } else {
            if (str_contains($this->filter, '-')) {
                [$month, $year] = explode('-', $this->filter);
            } else {
                $month = $now->month;
                $year  = $now->year;
            }

            $month = (int) $month;
            $year  = (int) $year;
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $pemasukan   = CashFlow::selectRaw('DATE(date) as tgl, SUM(amount) as total')
                ->whereMonth('date', $month)->whereYear('date', $year)->where('type', 'income')
                ->groupByRaw('DATE(date)')->pluck('total', 'tgl');

            $pengeluaran = CashFlow::selectRaw('DATE(date) as tgl, SUM(amount) as total')
                ->whereMonth('date', $month)->whereYear('date', $year)->where('type', 'expense')
                ->groupByRaw('DATE(date)')->pluck('total', 'tgl');

            $labels = []; $dataPemasukan = []; $dataPengeluaran = [];
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $key = $current->format('Y-m-d');
                $isFuture = $current->gt($now);
                $labels[]          = $current->format('d');
                $dataPemasukan[]   = $isFuture ? null : (float)($pemasukan[$key] ?? 0);
                $dataPengeluaran[] = $isFuture ? null : (float)($pengeluaran[$key] ?? 0);
                $current->addDay();
            }
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Pemasukan',
                    'data'            => $dataPemasukan,
                    'borderColor'     => 'rgba(245, 158, 11, 1)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 3,
                ],
                [
                    'label'           => 'Pengeluaran',
                    'data'            => $dataPengeluaran,
                    'borderColor'     => 'rgba(245, 158, 11, 0.65)',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.08)',
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'tooltip' => [
                    'enabled'   => true,
                    'mode'      => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}
