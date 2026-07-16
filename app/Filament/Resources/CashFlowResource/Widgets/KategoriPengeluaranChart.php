<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\Expense;
use Carbon\Carbon;

class KategoriPengeluaranChart extends LineChartWidget
{
    protected static ?string $heading = 'Perbandingan Kategori Pengeluaran';
    protected static bool $isLazy = false;
    protected int | string | array $columnSpan = 1;
    protected static ?string $pollingInterval = null;

    public ?string $filter = null;

    protected $listeners = ['filterUpdated' => 'applyFilter'];

    public function applyFilter(string $month, string $year): void
    {
        $this->filter = ($month && $year) ? $month . '-' . $year : 'all';
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
        $query = Expense::selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total');

        // Parse filter
        if ($this->filter && $this->filter !== 'all') {
            if (str_contains($this->filter, '-')) {
                [$month, $year] = explode('-', $this->filter);
                $query->whereMonth('expense_date', $month)
                      ->whereYear('expense_date', $year);
            }
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

        // LineChartWidget expects line data format, we'll override getType
        return [
            'datasets' => [[
                'label'           => 'Total',
                'data'            => $data->pluck('total')->map(fn($v) => (float)$v)->toArray(),
                'backgroundColor' => array_slice($colors, 0, $data->count()),
                'borderWidth'     => 2,
                'borderColor'     => '#fff',
            ]],
            'labels' => $data->pluck('category')->toArray(),
        ];
    }

    // Override to use doughnut instead of line
    public function getType(): string
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
