<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;
    
    // Set default pagination ke 25 per halaman
    protected function getTableRecordsPerPageSelectOptions(): array 
    {
        return [10, 25, 50, 100];
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Member')
                ->icon('heroicon-o-user-add'),
        ];
    }
}