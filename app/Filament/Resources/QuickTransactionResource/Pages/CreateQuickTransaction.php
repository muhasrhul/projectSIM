<?php

namespace App\Filament\Resources\QuickTransactionResource\Pages;

use App\Filament\Resources\QuickTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuickTransaction extends CreateRecord
{
    protected static string $resource = QuickTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}