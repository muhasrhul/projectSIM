<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Member;
use Carbon\Carbon;

class PaketMembershipChart extends ChartWidget
{
    protected static ?string $heading = 'Perbandingan Paket Membership';
    protected static bool $isLazy = true;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $pollingInterval = null;

    public string $filterMonth = '';
    public string $filterYear = '';

    protected $listeners = ['filterUpdated' => 'updateFilter'];

    public function mount(): void
    {
        $this->filterMonth = '';
        $this->filterYear  = '';
    }

    public function updateFilter(string $month, string $year): void
    {
        $this->filterMonth = $month;
        $this->filterYear  = $year;
        $this->updateChartData();
    }

    protected function getMaxHeight(): ?string
    {
        return '280px';
    }

    protected function getData(): array
    {
        $query = Member::selectRaw('type, COUNT(*) as total')
            ->whereNotNull('type')
            ->groupBy('type')
            ->orderByDesc('total');

        if ($this->filterMonth && $this->filterYear) {
            $query->whereMonth('created_at', $this->filterMonth)
                  ->whereYear('created_at', $this->filterYear);
        } elseif ($this->filterYear) {
            $query->whereYear('created_at', $this->filterYear);
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

    protected function getType(): string
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
