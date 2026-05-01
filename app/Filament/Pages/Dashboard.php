<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';
    
    protected static ?string $title = 'Dashboard';
    
    // PERMISSION: Super Admin dan Admin bisa akses Dashboard
    public static function canAccess(): bool
    {
        return auth()->user()->isSuperAdmin() || auth()->user()->isAdmin();
    }
    
    protected function getHeading(): string
    {
        return 'Dashboard';
    }
}
