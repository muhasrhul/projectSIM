<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\CashFlow; // UBAH: Gunakan CashFlow sebagai sumber data
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    // Polling setiap 60 detik (lebih jarang = lebih cepat)
    protected static ?string $pollingInterval = '60s';
    
    // Lazy load widget - tidak langsung load saat halaman dibuka
    protected static bool $isLazy = true;
    
    // Set kolom menjadi 4 untuk layout yang lebih rapi
    protected int | string | array $columnSpan = 'full';
    
    // PERMISSION: Hanya Super Admin yang bisa lihat widget ini
    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }
    
    protected function getColumns(): int
    {
        return 4; // 4 kolom per baris (seperti semula)
    }
    
    protected function getCards(): array
    {
        $now = Carbon::now('Asia/Makassar');
        
        // Cache selama 30 detik untuk update lebih cepat
        $omsetHariIni = cache()->remember('stats_omset_hari_ini', 30, function () use ($now) {
            // Ambil dari CashFlow hari ini (pemasukan saja)
            return CashFlow::whereDate('date', $now->format('Y-m-d'))
                ->where('type', 'income')
                ->sum('amount');
        });

        $pengeluaranBulanIni = cache()->remember('stats_pengeluaran_bulan', 30, function () use ($now) {
            // Hitung total pengeluaran bulan berjalan
            return CashFlow::whereMonth('date', $now->month)
                ->whereYear('date', $now->year)
                ->where('type', 'expense')
                ->sum('amount');
        });

        $totalPendapatanBersihBulanIni = cache()->remember('stats_pendapatan_bersih_bulan', 30, function () use ($now) {
            // UBAH: Hitung pendapatan bersih bulan berjalan (pemasukan - pengeluaran)
            $totalPemasukan = CashFlow::whereMonth('date', $now->month)
                ->whereYear('date', $now->year)
                ->where('type', 'income')
                ->sum('amount');
                
            $totalPengeluaran = CashFlow::whereMonth('date', $now->month)
                ->whereYear('date', $now->year)
                ->where('type', 'expense')
                ->sum('amount');
                
            return $totalPemasukan - $totalPengeluaran;
        });

        $totalMember = cache()->remember('stats_total_member', 30, function () {
            return Member::where('is_active', true)
                ->whereDate('expiry_date', '>=', now()) 
                ->count();
        });

        $sedangLatihan = cache()->remember('stats_latihan_hari_ini', 30, function () {
            return Attendance::whereDate('created_at', now())->count();
        });

        $memberExpired = cache()->remember('stats_member_expired', 30, function () {
            return Member::where('is_active', false)
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<', now())
                ->count();
        });

        return [
            // KARTU 1: PENDAPATAN HARI INI
            Card::make('Pendapatan Hari Ini', 'Rp ' . number_format($omsetHariIni, 0, ',', '.'))
                ->description('Total uang masuk hari ini')
                ->descriptionIcon('heroicon-s-trending-up')
                ->color('success'),

            // KARTU 2: PENGELUARAN BULAN INI
            Card::make('Pengeluaran ' . $now->translatedFormat('F Y'), 'Rp ' . number_format($pengeluaranBulanIni, 0, ',', '.'))
                ->description('Total biaya operasional')
                ->descriptionIcon('heroicon-s-trending-down')
                ->color('danger'),

            // KARTU 3: PENDAPATAN BERSIH BULAN INI (pemasukan - pengeluaran)
            Card::make('Pendapatan Bersih ' . $now->translatedFormat('F Y'), 'Rp ' . number_format($totalPendapatanBersihBulanIni, 0, ',', '.'))
                ->description('Keuntungan bersih')
                ->descriptionIcon('heroicon-s-calculator')
                ->color($totalPendapatanBersihBulanIni >= 0 ? 'success' : 'danger'),

            // KARTU 4: TOTAL MEMBER AKTIF
            Card::make('Total Member Aktif', $totalMember . ' Orang')
                ->description('Member dengan status aktif')
                ->descriptionIcon('heroicon-s-user-group')
                ->color('primary'),
            
            // KARTU 5: TOTAL MEMBER EXPIRED
            Card::make('Total Member Expired', $memberExpired . ' Orang')
                ->description('Member yang sudah expired')
                ->descriptionIcon('heroicon-s-clock')
                ->color('danger'),
            
            // KARTU 6: LOG ABSENSI HARI INI
            Card::make('Absensi Hari Ini', $sedangLatihan . ' Check-in')
                ->description('Jumlah orang latihan hari ini')
                ->descriptionIcon('heroicon-s-clipboard-check')
                ->color('warning'),
        ];
    }
}