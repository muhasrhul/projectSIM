<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Expense;
use Carbon\Carbon;

class KategoriPengeluaranChart extends ChartWidget
{
    protected static ?string $heading = 'Perbandingan Kategori Pengeluaran';
    protected static bool $isLazy = true;
    protected int | string | array $columnSpan = 1;
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
        return '300px';
    }

    protected function getData(): array
    {
        $query = Expense::selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total');

        if ($this->filterMonth && $this->filterYear) {
            $query->whereMonth('expense_date', $this->filterMonth)
                  ->whereYear('expense_date', $this->filterYear);
        } elseif ($this->filterYear) {
            $query->whereYear('expense_date', $this->filterYear);
        }

        $data = $query->get();

        $colors = [
            'rgba(245, 158, 11, 1)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(245, 158, 11, 0.65)',
            'rgba(245, 158, 11, 0.5)',
            'rgba(245, 158, 11, 0.38)',
            'rgba(245, 158, 11, 0.28)',
            'rgba(245, 158, 11, 0.18)',
        ];

        return [
            'datasets' => [[
                'data'            => $data->pluck('total')->map(fn($v) => (float)$v)->toArray(),
                'backgroundColor' => array_slice($colors, 0, $data->count()),
                'borderWidth'     => 2,
                'borderColor'     => '#fff',
            ]],
            'labels' => $data->pluck('category')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => true, 'position' => 'right'],
                'tooltip' => ['enabled' => true],
            ],
            'maintainAspectRatio' => true,
            'aspectRatio' => 1.5,
        ];
    }
}
