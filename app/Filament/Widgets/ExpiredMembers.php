<?php

namespace App\Filament\Widgets;

use App\Models\Member;
use App\Models\Paket;
use App\Models\Transaction; 
use App\Models\User; 
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification; 
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class ExpiredMembers extends BaseWidget
{
    protected static ?string $heading = '⚠️ Member Habis Masa Aktif (Jatuh Tempo)';
    protected static ?int $sort = 5; 
    protected int | string | array $columnSpan = 'full';
    
    // Polling setiap 10 detik untuk update real-time
    protected static ?string $pollingInterval = '10s';
    
    // Lazy load widget
    protected static bool $isLazy = true;
    
    // Batasi jumlah data yang ditampilkan
    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50];
    }

    protected function getTableQuery(): Builder
    {
        // Query langsung tanpa cache agar real-time
        return Member::query()
            ->whereDate('expiry_date', '<=', Carbon::now('Asia/Makassar')->toDateString())
            ->where('is_active', false)
            ->limit(50);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Nama Member')
                ->weight('bold')
                ->searchable(),
            
            Tables\Columns\TextColumn::make('type')
                ->label('Paket'),

            Tables\Columns\TextColumn::make('expiry_date')
                ->label('Tanggal Habis')
                ->date('d M Y')
                ->color('danger')
                ->sortable(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('perpanjang')
                ->label('Perpanjang')
                ->color('success')
                ->icon('heroicon-o-refresh')
                ->url(fn ($record) => route('filament.resources.members.edit', ['record' => $record->id]))
                ->openUrlInNewTab(false),

            Action::make('whatsapp')
                ->label('Beri Tahu')
                ->color('primary')
                ->icon('heroicon-o-chat-alt')
                ->url(function ($record) {
                    if (!$record->phone) return null;
                    $nomor = preg_replace('/[^0-9]/', '', $record->phone);
                    if (str_starts_with($nomor, '0')) { $nomor = '62' . substr($nomor, 1); }
                    $pesan = "Halo {$record->name}, apa kabar? 😊 Sekadar menginfokan bahwa masa aktif member Anda di *ARIFAH Gym* sudah berakhir. Jangan lupa untuk segera memperpanjang ya agar latihan Anda tetap lancar dan maksimal. Kami tunggu kedatangannya di gym!";
                    return "https://wa.me/{$nomor}?text=" . urlencode($pesan);
                })
                ->openUrlInNewTab(),
        ];
    }
}