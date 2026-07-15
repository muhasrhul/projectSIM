<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\QuickTransaction;
use Carbon\Carbon;

class ProdukChart extends ChartWidget
{
    protected static ?string $heading = 'Perbandingan Produk Terlaris';
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
    }

    protected function getMaxHeight(): ?string
    {
        return '300px';
    }

    protected function getData(): array
    {
        $query = QuickTransaction::selectRaw("TRIM(REGEXP_REPLACE(product_name, ' \\\\(\\\\d+x\\\\)$', '')) as clean_name, SUM(1) as total")
            ->where('status', 'paid')
            ->whereNotNull('product_name')
            ->where('product_name', 'not like', 'Hutang:%')
            ->groupByRaw("TRIM(REGEXP_REPLACE(product_name, ' \\\\(\\\\d+x\\\\)$', ''))")
            ->orderByDesc('total')
            ->limit(10);

        if ($this->filterMonth && $this->filterYear) {
            $query->whereMonth('payment_date', $this->filterMonth)
                  ->whereYear('payment_date', $this->filterYear);
        } elseif ($this->filterYear) {
            $query->whereYear('payment_date', $this->filterYear);
        }

        $data = $query->get();
        $count = $data->count();

        $colors = $data->map(function($item, $i) use ($count) {
            $opacity = round(0.9 - ($i * 0.6 / max($count - 1, 1)), 2);
            return "rgba(245, 158, 11, {$opacity})";
        })->toArray();

        return [
            'datasets' => [[
                'label'           => 'Jumlah Terjual',
                'data'            => $data->pluck('total')->map(fn($v) => (int)$v)->toArray(),
                'backgroundColor' => $colors,
                'borderRadius'    => 6,
                'borderWidth'     => 0,
            ]],
            'labels' => $data->pluck('clean_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'maintainAspectRatio' => true,
            'aspectRatio' => 1.5,
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => ['enabled' => true],
            ],
            'scales' => [
                'x' => ['beginAtZero' => true],
                'y' => ['ticks' => ['font' => ['size' => 11]]],
            ],
        ];
    }
}
