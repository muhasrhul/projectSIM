<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaketResource\Pages;
use App\Filament\Resources\PaketResource\RelationManagers;
use App\Models\Paket;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaketResource extends Resource
{
    protected static ?string $model = Paket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket'; 
    
    protected static ?string $navigationGroup = 'Master Data';
    
    // PERMISSION: Staff hanya bisa lihat, tidak bisa create/edit/delete
    public static function canCreate(): bool
    {
        return !auth()->user()->isStaff(); // Super Admin & Admin bisa
    }

    public static function canEdit($record): bool
    {
        return !auth()->user()->isStaff(); // Super Admin & Admin bisa
    }

    public static function canDelete($record): bool
    {
        return !auth()->user()->isStaff(); // Super Admin & Admin bisa
    }

    public static function canDeleteAny(): bool
    {
        return !auth()->user()->isStaff(); // Super Admin & Admin bisa
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make(2) 
                            ->schema([
                                Forms\Components\TextInput::make('nama_paket')
                                    ->label('Nama Paket')
                                    ->placeholder('Contoh: Member Gold 1 Bulan')
                                    ->required()
                                    ->maxLength(255),

                                // --- UPDATE BARU: Input Label Promo ---
                                // Forms\Components\TextInput::make('label_promo')
                                //     ->label('Label Promo (Opsional)')
                                //     ->placeholder('Contoh: BEST SELLER / DISKON 50%')
                                //     ->maxLength(50)
                                //     ->helperText('Isi text ini untuk memunculkan badge merah (Promo) di website.'),
                                // ---------------------------------------

                                Forms\Components\TextInput::make('harga')
                                    ->label('Harga Paket (Membership)')
                                    ->numeric()
                                    ->placeholder('200.000')
                                    ->required()
                                    ->mask(fn (Forms\Components\TextInput\Mask $mask) => $mask->money(prefix: 'Rp ', thousandsSeparator: '.', decimalPlaces: 0))
                                    ->helperText('Harga membership bulanan/tahunan'),

                                Forms\Components\TextInput::make('registration_fee')
                                    ->label('Biaya Registrasi (Fee)')
                                    ->numeric()
                                    ->placeholder('0')
                                    ->nullable()
                                    ->mask(fn (Forms\Components\TextInput\Mask $mask) => $mask->money(prefix: 'Rp ', thousandsSeparator: '.', decimalPlaces: 0))
                                    ->helperText('Biaya registrasi untuk pendaftar baru (kosongkan jika tidak ada)'),

                                Forms\Components\TextInput::make('durasi_hari')
                                    ->label('Durasi (Hari)')
                                    ->numeric()
                                    ->helperText('Contoh: 30 untuk 1 bulan aktif')
                                    ->required(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Status Aktif')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Nonaktifkan jika paket tidak ingin dijual sementara'),
                            ]),

                        // Forms\Components\Textarea::make('fasilitas')
                        //     ->label('Fasilitas Paket')
                        //     ->placeholder('Contoh: Akses Alat Lengkap, WiFi, Locker, Shower')
                        //     ->rows(3)
                        //     ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_paket')
                    ->label('Nama Paket')
                    ->sortable()
                    ->searchable(),

                // Opsional: Menampilkan Label Promo di Tabel Admin biar gampang dicek
                // Tables\Columns\TextColumn::make('label_promo')
                //     ->label('Badge')
                //     ->sortable()
                //     ->color('danger'),

                Tables\Columns\TextColumn::make('harga')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')) 
                    ->sortable(),

                Tables\Columns\TextColumn::make('registration_fee')
                    ->label('Fee Registrasi')
                    ->formatStateUsing(fn ($state) => ($state && $state > 0) ? 'Rp ' . number_format($state, 0, ',', '.') : '-') 
                    ->sortable(),

                Tables\Columns\TextColumn::make('durasi_hari')
                    ->label('Durasi')
                    ->suffix(' Hari')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), 
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListPakets::route('/'),
            'create' => Pages\CreatePaket::route('/create'),
            'edit' => Pages\EditPaket::route('/{record}/edit'),
        ];
    }    
}