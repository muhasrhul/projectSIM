<?php

namespace App\Filament\Resources\QuickTransactionResource\Pages;

use App\Filament\Resources\QuickTransactionResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuickTransactions extends ListRecords
{
    protected static string $resource = QuickTransactionResource::class;
    
    // Set default pagination ke 25 per halaman
    protected function getTableRecordsPerPageSelectOptions(): array 
    {
        return [10, 25, 50, 100];
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Transaksi')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}