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
        if ($month && $year) {
            $this->filter = $month . '-' . $year;
        } elseif ($year) {
            $this->filter = 'year-' . $year;
        } elseif ($month) {
            $this->filter = 'month-' . $month;
        } else {
            $this->filter = 'all';
        }
        $this->updateChartData();
    }

    protected function getData(): array
    {
        $now = Carbon::now('Asia/Makassar');
        $labels = []; $dataPemasukan = []; $dataPengeluaran = [];

        // Case 1: Filter bulan + tahun spesifik (tampilkan per hari)
        if ($this->filter && str_contains($this->filter, '-') && !str_starts_with($this->filter, 'year-') && !str_starts_with($this->filter, 'month-')) {
            [$month, $year] = explode('-', $this->filter);
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
        // Case 2: Filter tahun saja (tampilkan per bulan dalam tahun tersebut)
        elseif ($this->filter && str_starts_with($this->filter, 'year-')) {
            $year = (int) str_replace('year-', '', $this->filter);
            
            $pemasukan = CashFlow::selectRaw('MONTH(date) as bulan, SUM(amount) as total')
                ->whereYear('date', $year)->where('type', 'income')
                ->groupByRaw('MONTH(date)')->pluck('total', 'bulan');

            $pengeluaran = CashFlow::selectRaw('MONTH(date) as bulan, SUM(amount) as total')
                ->whereYear('date', $year)->where('type', 'expense')
                ->groupByRaw('MONTH(date)')->pluck('total', 'bulan');

            for ($i = 1; $i <= 12; $i++) {
                $labels[] = Carbon::create($year, $i, 1)->translatedFormat('M');
                $isFuture = Carbon::create($year, $i, 1)->gt($now);
                $dataPemasukan[]   = $isFuture ? null : (float)($pemasukan[$i] ?? 0);
                $dataPengeluaran[] = $isFuture ? null : (float)($pengeluaran[$i] ?? 0);
            }
        }
        // Case 3: Filter bulan saja (tampilkan bulan tersebut dari semua tahun)
        elseif ($this->filter && str_starts_with($this->filter, 'month-')) {
            $month = (int) str_replace('month-', '', $this->filter);
            
            $pemasukan = CashFlow::selectRaw('YEAR(date) as tahun, SUM(amount) as total')
                ->whereMonth('date', $month)->where('type', 'income')
                ->groupByRaw('YEAR(date)')->orderBy('tahun')->pluck('total', 'tahun');

            $pengeluaran = CashFlow::selectRaw('YEAR(date) as tahun, SUM(amount) as total')
                ->whereMonth('date', $month)->where('type', 'expense')
                ->groupByRaw('YEAR(date)')->orderBy('tahun')->pluck('total', 'tahun');

            $allYears = collect($pemasukan->keys())->merge($pengeluaran->keys())->unique()->sort()->values();
            foreach ($allYears as $year) {
                $labels[] = (string) $year;
                $dataPemasukan[]   = (float)($pemasukan[$year] ?? 0);
                $dataPengeluaran[] = (float)($pengeluaran[$year] ?? 0);
            }
        }
        // Case 4: Semua waktu (tampilkan per bulan)
        else {
            $pemasukan = CashFlow::selectRaw('DATE_FORMAT(date, "%Y-%m") as bulan, SUM(amount) as total')
                ->where('type', 'income')->groupByRaw('DATE_FORMAT(date, "%Y-%m")')
                ->orderBy('bulan')->pluck('total', 'bulan');

            $pengeluaran = CashFlow::selectRaw('DATE_FORMAT(date, "%Y-%m") as bulan, SUM(amount) as total')
                ->where('type', 'expense')->groupByRaw('DATE_FORMAT(date, "%Y-%m")')
                ->orderBy('bulan')->pluck('total', 'bulan');

            $allKeys = collect($pemasukan->keys())->merge($pengeluaran->keys())->unique()->sort()->values();
            foreach ($allKeys as $key) {
                $labels[]          = Carbon::parse($key . '-01')->translatedFormat('M Y');
                $dataPemasukan[]   = (float)($pemasukan[$key] ?? 0);
                $dataPengeluaran[] = (float)($pengeluaran[$key] ?? 0);
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
