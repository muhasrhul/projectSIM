<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PerbandinganPaketChart extends ChartWidget
{
    protected static ?string $heading = 'Paket Paling Populer';
    protected static ?int $sort = 6; // Di bawah TanggalPerpanjanganChart (sort = 5)
    
    // Polling setiap 60 detik
    protected static ?string $pollingInterval = '60s';
    
    // Lazy load widget
    protected static bool $isLazy = true;
    
    // Setengah lebar (2 kolom)
    protected int | string | array $columnSpan = 1;
    
    protected function getMaxHeight(): ?string
    {
        return '350px';
    }

    protected function getData(): array
    {
        // Cache selama 5 menit
        return cache()->remember('chart_perbandingan_paket', 300, function () {
            // Daftar paket yang valid
            $validPackages = [
                'Member 1 Bulan',
                'Member 1 Bulan + PT',
                'Mingguan 7 Hari',
                'Visit Harian'
            ];
            
            // Ambil data jumlah member per paket (hanya paket yang valid)
            $data = Member::select('type', DB::raw('count(*) as total'))
                ->whereIn('type', $validPackages)
                ->groupBy('type')
                ->orderBy('total', 'desc')
                ->pluck('total', 'type')
                ->toArray();
            
            // Tambahkan data Visit Harian dari QuickTransactions (Visit Harian)
            $visitHarianFromQuick = \App\Models\QuickTransaction::where('type', 'Visit Harian')
                ->count();
            
            // Kombinasikan Visit Harian dari members + quick_transactions
            if (isset($data['Visit Harian'])) {
                $data['Visit Harian'] += $visitHarianFromQuick;
            } else {
                $data['Visit Harian'] = $visitHarianFromQuick;
            }
            
            // Sort ulang setelah kombinasi
            arsort($data);

            $labels = array_keys($data);
            $values = array_values($data);
            
            // Gradasi warna orange dengan transparansi (semakin kecil nilai semakin transparan)
            $colors = [
                'rgba(249, 115, 22, 1)',    // Orange solid 100% (nilai terbesar)
                'rgba(249, 115, 22, 0.75)',  // Orange 75%
                'rgba(249, 115, 22, 0.5)',   // Orange 50%
                'rgba(249, 115, 22, 0.3)',   // Orange 30% (nilai terkecil)
            ];

            return [
                'datasets' => [
                    [
                        'label' => 'Jumlah Member',
                        'data' => $values,
                        'backgroundColor' => array_slice($colors, 0, count($values)),
                        'borderColor' => array_slice($colors, 0, count($values)),
                        'borderWidth' => 1,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => true,
            'aspectRatio' => 1.5,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'y' => [
                    'type' => 'logarithmic',
                    'beginAtZero' => false,
                    'min' => 1,
                    'ticks' => [
                        'callback' => 'function(value) { return Number(value.toString()); }',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}
