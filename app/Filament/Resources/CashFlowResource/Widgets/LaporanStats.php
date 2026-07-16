<?php

namespace App\Filament\Resources\CashFlowResource\Widgets;

use App\Models\Transaction;
use App\Models\CashFlow;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Carbon\Carbon;

class LaporanStats extends BaseWidget
{
    protected static bool $isLazy = true;
    protected int | string | array $columnSpan = 'full';

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

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getCards(): array
    {
        $month = $this->filterMonth;
        $year  = $this->filterYear;
        
        if ($month && $year) {
            $label = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        } elseif ($year) {
            $label = 'Tahun ' . $year;
        } elseif ($month) {
            $monthNames = [
                '1' => 'Januari', '2' => 'Februari', '3' => 'Maret',
                '4' => 'April', '5' => 'Mei', '6' => 'Juni',
                '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
                '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
            ];
            $label = 'Bulan ' . $monthNames[$month] . ' (Semua Tahun)';
        } else {
            $label = 'Semua Waktu';
        }

        $pemasukanQuery   = CashFlow::where('type', 'income');
        $pengeluaranQuery = CashFlow::where('type', 'expense');
        $transaksiQuery = CashFlow::query();

        if ($month && $year) {
            $pemasukanQuery->whereMonth('date', $month)->whereYear('date', $year);
            $pengeluaranQuery->whereMonth('date', $month)->whereYear('date', $year);
            $transaksiQuery->whereMonth('date', $month)->whereYear('date', $year);
        } elseif ($year) {
            $pemasukanQuery->whereYear('date', $year);
            $pengeluaranQuery->whereYear('date', $year);
            $transaksiQuery->whereYear('date', $year);
        } elseif ($month) {
            // Filter bulan saja (semua tahun)
            $pemasukanQuery->whereMonth('date', $month);
            $pengeluaranQuery->whereMonth('date', $month);
            $transaksiQuery->whereMonth('date', $month);
        }

        $totalPemasukan   = $pemasukanQuery->sum('amount');
        $totalPengeluaran = $pengeluaranQuery->sum('amount');
        $pendapatanBersih = $totalPemasukan - $totalPengeluaran;
        $jumlahTransaksi  = $transaksiQuery->count();
        $rataRata         = $jumlahTransaksi > 0 ? $pendapatanBersih / $jumlahTransaksi : 0;

        return [
            Card::make('Total Pendapatan ' . $label, 'Rp ' . number_format($pendapatanBersih, 0, ',', '.'))
                ->description('Pendapatan bersih')
                ->descriptionIcon('heroicon-s-calculator')
                ->color($pendapatanBersih >= 0 ? 'success' : 'danger'),

            Card::make('Total Pengeluaran ' . $label, 'Rp ' . number_format($totalPengeluaran, 0, ',', '.'))
                ->description('Total biaya operasional')
                ->descriptionIcon('heroicon-s-trending-down')
                ->color('danger'),

            Card::make('Jumlah Transaksi', $jumlahTransaksi . ' Transaksi')
                ->description('Total transaksi ' . $label)
                ->descriptionIcon('heroicon-s-clipboard-list')
                ->color('primary'),

            Card::make('Rata-rata / Transaksi', 'Rp ' . number_format($rataRata, 0, ',', '.'))
                ->description('Rata-rata nilai per transaksi')
                ->descriptionIcon('heroicon-s-trending-up')
                ->color('warning'),
        ];
    }
}
