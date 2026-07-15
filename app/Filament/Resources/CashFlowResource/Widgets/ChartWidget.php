<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use Filament\Widgets\Widget;
use Carbon\Carbon;

class ChartWidget extends Widget
{
    protected static string $view = 'filament.resources.cash-flow-resource.widgets.chart-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;
}
