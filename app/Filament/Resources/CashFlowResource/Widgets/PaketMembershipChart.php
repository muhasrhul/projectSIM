<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\Member;
use Carbon\Carbon;

class PaketMembershipChart extends LineChartWidget
{
    protected static ?string $heading = 'Perbandingan Paket Membership';
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
        return '280px';
    }

    protected function getFilters(): ?array
    {
        return null;
    }

    protected function getData(): array
    {
        $query = Member::selectRaw('type, COUNT(*) as total')
            ->whereNotNull('type')
            ->groupBy('type')
            ->orderByDesc('total');

        // Parse filter
        if ($this->filter && $this->filter !== 'all') {
            if (str_starts_with($this->filter, 'year-')) {
                $year = str_replace('year-', '', $this->filter);
                $query->whereYear('created_at', $year);
            } elseif (str_starts_with($this->filter, 'month-')) {
                $month = str_replace('month-', '', $this->filter);
                $query->whereMonth('created_at', $month);
            } elseif (str_contains($this->filter, '-')) {
                [$month, $year] = explode('-', $this->filter);
                $query->whereMonth('created_at', $month)
                      ->whereYear('created_at', $year);
            }
        }

        $data  = $query->get();
        $count = $data->count();

        $colors = $data->map(function($item, $i) use ($count) {
            $opacity = round(0.9 - ($i * 0.6 / max($count - 1, 1)), 2);
            return "rgba(245, 158, 11, {$opacity})";
        })->toArray();

        return [
            'datasets' => [[
                'label'           => 'Jumlah Member',
                'data'            => $data->pluck('total')->map(fn($v) => (int)$v)->toArray(),
                'backgroundColor' => $colors,
                'borderRadius'    => 6,
                'borderWidth'     => 0,
            ]],
            'labels' => $data->pluck('type')->toArray(),
        ];
    }

    public function getType(): string
    {
        return 'bar';
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
                    'type' => 'logarithmic',
                    'ticks' => [
                        'callback' => 'function(value) { return Number.isInteger(Math.log10(value)) ? value : null; }',
                    ],
                ],
                'x' => [
                    'ticks' => ['font' => ['size' => 11]],
                ],
            ],
        ];
    }
}
