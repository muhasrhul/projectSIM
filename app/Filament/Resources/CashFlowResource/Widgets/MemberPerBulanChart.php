<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\Member;
use Carbon\Carbon;

class MemberPerBulanChart extends LineChartWidget
{
    protected static ?string $heading = 'Perbandingan Member Berdasarkan Bulan';
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;

    public ?string $filter = null;

    protected $listeners = ['filterUpdated' => 'applyFilter'];

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

    protected function getMaxHeight(): ?string
    {
        return '300px';
    }

    protected function getFilters(): ?array
    {
        return null;
    }

    protected function getData(): array
    {
        $query = Member::selectRaw('MONTH(join_date) as month, COUNT(*) as total')
            ->whereNotNull('join_date')
            ->groupByRaw('MONTH(join_date)')
            ->orderByRaw('MONTH(join_date)');

        // Parse filter
        if ($this->filter && $this->filter !== 'all') {
            if (str_starts_with($this->filter, 'year-')) {
                $year = str_replace('year-', '', $this->filter);
                $query->whereYear('join_date', $year);
            } elseif (str_starts_with($this->filter, 'month-')) {
                $month = str_replace('month-', '', $this->filter);
                $query->whereMonth('join_date', $month);
            } elseif (str_contains($this->filter, '-')) {
                [$month, $year] = explode('-', $this->filter);
                $query->whereYear('join_date', $year);
            }
        }

        $data = $query->get();

        // Buat array untuk semua bulan (1-12)
        $monthlyData = array_fill(1, 12, 0);
        
        foreach ($data as $item) {
            $monthlyData[$item->month] = (int)$item->total;
        }

        $labels = [
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'
        ];

        $values = array_values($monthlyData);

        return [
            'datasets' => [[
                'label'           => 'Jumlah Member',
                'data'            => $values,
                'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
                'borderColor'     => '#F59E0B',
                'borderWidth'     => 2,
                'fill'            => true,
                'tension'         => 0.4,
                'pointRadius'     => 4,
                'pointHoverRadius' => 6,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => ['enabled' => true],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }
}
