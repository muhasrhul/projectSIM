<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Kelola User';
    protected static ?string $pluralLabel = 'Kelola User';
    protected static ?string $modelLabel = 'User';
    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 99;

    // PERMISSION: Hanya Super Admin yang bisa akses menu ini
    public static function canViewAny(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canCreate(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignorable: fn ($record) => $record)
                        ->maxLength(255),

                    Forms\Components\TextInput::make('phone')
                        ->label('Nomor WhatsApp')
                        ->tel()
                        ->maxLength(20),

                    Forms\Components\Select::make('role')
                        ->label('Role / Jabatan')
                        ->options([
                            'super_admin' => 'Super Admin (Akses Penuh)',
                            'admin' => 'Admin (Akses Penuh)',
                        ])
                        ->required()
                        ->default('admin'),

                    Forms\Components\TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->required(fn ($livewire) => $livewire instanceof Pages\CreateUser)
                        ->helperText(fn ($livewire) => $livewire instanceof Pages\EditUser 
                            ? 'Kosongkan jika tidak ingin mengubah password' 
                            : 'Minimal 8 karakter')
                        ->minLength(8),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-o-mail'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->default('-')
                    ->formatStateUsing(fn ($state) => $state ?? '-'),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'admin',
                    ])
                    ->icons([
                        'heroicon-o-shield-exclamation' => 'super_admin',
                        'heroicon-o-shield-check' => 'admin',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Filter Role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'admin' => 'Admin',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus User')
                    ->modalSubheading('Apakah Anda yakin ingin menghapus user ini?')
                    ->modalButton('Ya, Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->requiresConfirmation(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }    
}
