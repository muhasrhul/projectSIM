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

    public string $filterMonth = '';
    public string $filterYear = '';

    protected $listeners = ['filterUpdated' => 'updateFilter'];

    public function mount($record = null): void
    {
        parent::mount($record);
        // Default: semua waktu (kosong)
        $this->filterMonth = '';
        $this->filterYear  = '';
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

    protected function getTableHeading(): ?string
    {
        return 'Cash Flow';
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
                ->label(fn() => ($this->filterMonth && $this->filterYear) 
                    ? $this->getMonthOptions()[$this->filterMonth] . ' ' . $this->filterYear 
                    : 'Semua Waktu')
                ->icon('heroicon-o-calendar')
                ->color('secondary')
                ->modalWidth('sm')
                ->modalHeading('Filter Periode')
                ->modalSubheading('Pilih bulan dan tahun yang ingin ditampilkan')
                ->form([
                    \Filament\Forms\Components\Select::make('filterMonth')
                        ->label('Bulan')
                        ->options(['all' => 'Semua Waktu'] + $this->getMonthOptions())
                        ->default($this->filterMonth ?: 'all')
                        ->searchable()
                        ->required(),

                    \Filament\Forms\Components\Select::make('filterYear')
                        ->label('Tahun')
                        ->options(['all' => 'Semua Tahun'] + $this->getYearOptions())
                        ->default($this->filterYear ?: 'all')
                        ->searchable()
                        ->required(),
                ])
                ->modalButton('Terapkan Filter')
                ->action(function (array $data): void {
                    $this->filterMonth = $data['filterMonth'] === 'all' ? '' : $data['filterMonth'];
                    $this->filterYear  = $data['filterYear'] === 'all' ? '' : $data['filterYear'];
                    $this->emit('filterUpdated', $this->filterMonth, $this->filterYear);
                    $this->dispatchBrowserEvent('filter-updated', ['month' => $this->filterMonth, 'year' => $this->filterYear]);
                }),

            Actions\Action::make('export_pdf')
                ->label('Unduh Laporan')
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Select::make('format')
                        ->label('Format')
                        ->options([
                            'pdf' => 'PDF',
                            'csv' => 'CSV',
                        ])
                        ->default('pdf')
                        ->required(),

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
                    if ($data['format'] === 'csv') {
                        return redirect()->to(route('export.pembukuan', [
                            'period' => $data['period'],
                            'format' => 'csv',
                        ]));
                    }
                    return redirect()->to(route('export.pembukuan', ['period' => $data['period']]));
                })
                ->modalButton('Unduh'),
        ];
    }

    protected function getHeaderWidgetsColumns(): int | array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\CashFlowResource\Widgets\HeaderWidget::class,
            \App\Filament\Resources\CashFlowResource\Widgets\LaporanStats::class,
            \App\Filament\Resources\CashFlowResource\Widgets\LaporanChart::class,
            \App\Filament\Resources\CashFlowResource\Widgets\KategoriPengeluaranChart::class,
            \App\Filament\Resources\CashFlowResource\Widgets\ProdukChart::class,
            \App\Filament\Resources\CashFlowResource\Widgets\PaketMembershipChart::class,
            \App\Filament\Resources\CashFlowResource\Widgets\MemberPerBulanChart::class,
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getTableQuery()
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        if ($this->filterMonth && $this->filterYear) {
            $query->whereMonth('date', $this->filterMonth)
                  ->whereYear('date', $this->filterYear);
        } elseif ($this->filterYear) {
            $query->whereYear('date', $this->filterYear);
        }

        return $query;
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
