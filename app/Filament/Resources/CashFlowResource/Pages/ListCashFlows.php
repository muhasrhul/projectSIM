<?php

namespace App\Filament\Resources\CashFlowResource\Pages;

use App\Filament\Resources\CashFlowResource;
use App\Models\CashFlow;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Carbon\Carbon;

class ListCashFlows extends ListRecords
{
    protected static string $resource = CashFlowResource::class;

    public string $filterMonth;
    public string $filterYear;

    protected $listeners = ['filterUpdated' => 'updateFilter'];

    public function mount($record = null): void
    {
        parent::mount($record);
        $now = Carbon::now('Asia/Makassar');
        $this->filterMonth = (string) $now->month;
        $this->filterYear  = (string) $now->year;
    }

    public function updatedFilterMonth(): void
    {
        $this->emit('filterUpdated', $this->filterMonth, $this->filterYear);
        $this->dispatchBrowserEvent('filter-updated', ['month' => $this->filterMonth, 'year' => $this->filterYear]);
    }

    public function updatedFilterYear(): void
    {
        $this->emit('filterUpdated', $this->filterMonth, $this->filterYear);
        $this->dispatchBrowserEvent('filter-updated', ['month' => $this->filterMonth, 'year' => $this->filterYear]);
    }

    public function updateFilter(string $month, string $year): void
    {
        $this->filterMonth = $month;
        $this->filterYear  = $year;
        $this->dispatchBrowserEvent('filter-updated', ['month' => $month, 'year' => $year]);
    }

    public function getMonthOptions(): array
    {
        return [
            '1' => 'Januari', '2' => 'Februari', '3' => 'Maret',
            '4' => 'April', '5' => 'Mei', '6' => 'Juni',
            '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
        ];
    }

    public function getYearOptions(): array
    {
        $years = [];
        $currentYear = Carbon::now()->year;
        for ($y = $currentYear; $y >= 2025; $y--) {
            $years[(string)$y] = (string)$y;
        }
        return $years;
    }

    public function getBreadcrumb(): ?string
    {
        return null;
    }

    protected function getTitle(): string
    {
        return '';
    }

    protected function getHeading(): string
    {
        return ' ';
    }

    public function getHeaderFilterHtml(): string
    {
        return '';
    }

    protected function getActions(): array
    {
        $now = Carbon::now('Asia/Makassar');
        
        return [
            Actions\Action::make('filter_periode')
                ->label($this->getMonthOptions()[$this->filterMonth] . ' ' . $this->filterYear)
                ->icon('heroicon-o-calendar')
                ->color('secondary')
                ->modalWidth('sm')
                ->modalHeading('Filter Periode')
                ->modalSubheading('Pilih bulan dan tahun yang ingin ditampilkan')
                ->form([
                    \Filament\Forms\Components\Select::make('filterMonth')
                        ->label('Bulan')
                        ->options($this->getMonthOptions())
                        ->default($this->filterMonth)
                        ->searchable()
                        ->required(),

                    \Filament\Forms\Components\Select::make('filterYear')
                        ->label('Tahun')
                        ->options($this->getYearOptions())
                        ->default($this->filterYear)
                        ->required(),
                ])
                ->modalButton('Terapkan Filter')
                ->action(function (array $data): void {
                    $this->filterMonth = $data['filterMonth'];
                    $this->filterYear  = $data['filterYear'];
                    $this->emit('filterUpdated', $this->filterMonth, $this->filterYear);
                }),

            Actions\Action::make('export_pdf')
                ->label('Unduh Laporan')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Select::make('period')
                        ->label('Pilih Periode Laporan')
                        ->options(function () {
                            $options = ['today' => 'Hari Ini'];
                            $now = \Carbon\Carbon::now('Asia/Makassar');
                            for ($i = 0; $i < 12; $i++) {
                                $date = $now->copy()->subMonths($i);
                                $options[$date->format('Y-m')] = $date->translatedFormat('F Y');
                            }
                            return $options;
                        })
                        ->searchable()
                        ->default('today')
                        ->required(),
                ])
                ->action(function (array $data) {
                    return redirect()->to(route('export.pembukuan', ['period' => $data['period']]));
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\CashFlowResource\Widgets\HeaderWidget::class,
            \App\Filament\Resources\CashFlowResource\Widgets\LaporanStats::class,
            \App\Filament\Resources\CashFlowResource\Widgets\LaporanChart::class,
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()
            ->whereMonth('date', $this->filterMonth)
            ->whereYear('date', $this->filterYear)
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');
    }

    protected function getTableFilters(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        return [
            'filterMonth'   => $this->filterMonth,
            'filterYear'    => $this->filterYear,
            'monthOptions'  => $this->getMonthOptions(),
            'yearOptions'   => $this->getYearOptions(),
        ];
    }
}
