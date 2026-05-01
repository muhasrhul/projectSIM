<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MetodePembayaranChart extends ChartWidget
{
    protected static ?string $heading = 'Metode Pembayaran Terfavorit';
    protected static ?int $sort = 7; // Setelah PerbandinganPaketChart (sort = 6)
    
    // Polling setiap 60 detik
    protected static ?string $pollingInterval = '60s';
    
    // Lazy load widget
    protected static bool $isLazy = true;
    
    // PERMISSION: Hanya Super Admin yang bisa lihat widget ini
    public static function canView(): bool
    {
        return auth()->user()->isSuperAdmin();
    }
    
    // Setengah lebar (2 kolom) - sejajar dengan PerbandinganPaketChart
    protected int | string | array $columnSpan = 1;
    
    protected function getMaxHeight(): ?string
    {
        return '350px';
    }

    protected function getData(): array
    {
        // Cache selama 5 menit
        return cache()->remember('chart_metode_pembayaran', 300, function () {
            // 1. Ambil data dari tabel transactions
            $dataTransactions = Transaction::select('payment_method', DB::raw('count(*) as total'))
                ->whereIn('payment_method', ['cash', 'Cash', 'QRIS', 'transfer_bank', 'Transfer Bank'])
                ->groupBy('payment_method')
                ->pluck('total', 'payment_method')
                ->toArray();
            
            // 2. Ambil data dari tabel quick_transactions
            $dataQuickTransactions = \App\Models\QuickTransaction::select('payment_method', DB::raw('count(*) as total'))
                ->whereIn('payment_method', ['cash', 'Cash', 'QRIS', 'transfer_bank', 'Transfer Bank'])
                ->groupBy('payment_method')
                ->pluck('total', 'payment_method')
                ->toArray();

            // 3. Gabungkan kedua data
            $combinedData = [];
            
            // Gabungkan dari transactions
            foreach ($dataTransactions as $method => $total) {
                if (isset($combinedData[$method])) {
                    $combinedData[$method] += $total;
                } else {
                    $combinedData[$method] = $total;
                }
            }
            
            // Gabungkan dari quick_transactions
            foreach ($dataQuickTransactions as $method => $total) {
                if (isset($combinedData[$method])) {
                    $combinedData[$method] += $total;
                } else {
                    $combinedData[$method] = $total;
                }
            }

            // 4. Normalisasi label (Cash dan cash jadi QRIS, transfer_bank jadi Transfer Bank)
            $normalizedData = [];
            foreach ($combinedData as $method => $total) {
                // Ubah "Cash" jadi "QRIS" untuk konsistensi
                if (strtolower($method) === 'cash') {
                    $method = 'QRIS';
                }
                // Ubah "transfer_bank" jadi "Transfer Bank"
                if ($method === 'transfer_bank') {
                    $method = 'Transfer Bank';
                }
                
                if (isset($normalizedData[$method])) {
                    $normalizedData[$method] += $total;
                } else {
                    $normalizedData[$method] = $total;
                }
            }
            
            // Sort ulang setelah normalisasi
            arsort($normalizedData);

            $labels = array_keys($normalizedData);
            $values = array_values($normalizedData);
            
            // Gradasi warna orange dengan transparansi (semakin kecil nilai semakin transparan)
            $colors = [
                'rgba(249, 115, 22, 1)',    // Orange solid 100% (nilai terbesar)
                'rgba(249, 115, 22, 0.6)',  // Orange 60% (nilai terkecil)
            ];

            return [
                'datasets' => [
                    [
                        'label' => 'Jumlah Transaksi',
                        'data' => $values,
                        'backgroundColor' => $colors,
                        'borderWidth' => 2,
                        'borderColor' => '#ffffff',
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 1.5,
            'interaction' => [
                'mode' => 'point',
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'point',
                ],
            ],
        ];
    }
}
