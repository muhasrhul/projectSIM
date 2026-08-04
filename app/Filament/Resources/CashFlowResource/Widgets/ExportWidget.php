<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Form;

class ExportWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.resources.cash-flow-resource.widgets.export-widget';
    
    protected int | string | array $columnSpan = 'full';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'period' => 'today'
        ]);
    }

    public function form(Form $form): Form
    {
        // Generate opsi bulan untuk 12 bulan terakhir
        $monthOptions = [];
        $now = \Carbon\Carbon::now('Asia/Makassar');
        
        for ($i = 0; $i < 12; $i++) {
            $date = $now->copy()->subMonths($i);
            $key = $date->format('Y-m');
            $monthOptions[$key] = $date->translatedFormat('F Y');
        }
        
        return $form
            ->schema([
                Select::make('period')
                    ->label('Periode Laporan')
                    ->options(array_merge([
                        'today' => 'Hari Ini',
                        'week' => 'Minggu Ini (7 hari terakhir)',
                        'month' => 'Bulan Ini',
                    ], $monthOptions))
                    ->default('today')
                    ->required()
                    ->selectablePlaceholder(false)
                    ->native(false),
            ])
            ->statePath('data');
    }

    public function exportPDF(): void
    {
        $data = $this->form->getState();
        $period = $data['period'] ?? 'today';
        $url = route('export.arus-kas', ['period' => $period]);
        
        // Buka di tab baru menggunakan JavaScript
        $this->dispatch('open-url', url: $url);
    }
}