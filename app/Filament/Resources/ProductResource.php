<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Produk Kantin';
    
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
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Air Mineral / Daily Pass'),

                        Forms\Components\TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        Forms\Components\TextInput::make('stock')
                            ->label('Stock / Stok')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Jumlah produk yang tersedia. 0 = Habis'),

                        // Forms\Components\TextInput::make('icon')
                        //     ->label('Ikon FontAwesome')
                        //     ->default('fa-solid fa-cart-shopping')
                        //     ->placeholder('Contoh: fa-solid fa-bottle-water')
                        //     ->helperText('Gunakan kode dari FontAwesome (e.g., fa-solid fa-dumbbell)'),

                        // UPDATE: Hanya pilihan Orange dan Black agar sesuai tampilan Premium
                        Forms\Components\Select::make('color')
                            ->label('Tema Warna')
                            ->options([
                                'orange' => 'Orange (Latihan Harian)',
                                'black'  => 'Hitam (Produk Kantin)',
                            ])
                            ->default('black')
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('idr', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->suffix(' pcs'),

                // Tables\Columns\TextColumn::make('color')
                //     ->label('Warna')
                //     ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Hanya Produk Aktif'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }    
}