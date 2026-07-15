<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuickTransactionResource\Pages;
use App\Models\QuickTransaction;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class QuickTransactionResource extends Resource
{
    protected static ?string $model = QuickTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-cash';
    protected static ?int $navigationSort = 2; // Urutan kedua
    protected static ?string $navigationLabel = 'Transaksi Kasir Cepat';
    protected static ?string $pluralLabel = 'Transaksi Kasir Cepat';
    protected static ?string $navigationGroup = 'Transaksi';
    
    // PERMISSION: Hanya Super Admin yang bisa akses
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

    public static function canDeleteAny(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\TextInput::make('guest_name')
                    ->label('Nama Tamu')
                    ->required(),

                Forms\Components\Select::make('product_name')
                    ->label('Nama Produk')
                    ->options(fn () => \App\Models\Product::where('is_active', true)->orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('order_id')
                    ->label('ID Transaksi')
                    ->default('KASIR-' . uniqid())
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Nominal (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Forms\Components\Select::make('type')
                    ->label('Kategori')
                    ->options([
                        'Latihan Harian' => 'Latihan Harian',
                        'Minuman/Kantin' => 'Minuman/Kantin',
                        'Snack' => 'Snack',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->required(),

                Forms\Components\Select::make('payment_method')
                    ->label('Metode Bayar')
                    ->options([
                        'QRIS' => 'QRIS',
                        'Transfer Bank' => 'Transfer Bank',
                    ])
                    ->default('QRIS')
                    ->required(),

                Forms\Components\DateTimePicker::make('payment_date')
                    ->label('Tanggal & Jam Bayar')
                    ->default(now())
                    ->required(),
                    
                Forms\Components\Hidden::make('status')
                    ->default('paid')
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // Log untuk debug
                        file_put_contents('form_debug.log', 
                            date('Y-m-d H:i:s') . " - Hidden status field updated: " . $state . "\n",
                            FILE_APPEND
                        );
                    }),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('source')
                    ->label('Sumber')
                    ->getStateUsing(fn () => 'Kasir Cepat')
                    ->color('warning')
                    ->icon('heroicon-o-lightning-bolt'),

                Tables\Columns\TextColumn::make('guest_name')
                    ->label('Nama Tamu')
                    ->searchable(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Produk')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('success')
                    ->weight('bold'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->enum([
                        'paid' => 'Lunas',
                        'pending' => 'Belum Bayar',
                    ])
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'pending',
                    ]),
                
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode'),
                
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'paid' => 'Lunas',
                        'pending' => 'Belum Bayar',
                    ]),
                
                Tables\Filters\SelectFilter::make('product_type')
                    ->label('Jenis Produk')
                    ->options(function () {
                        return \App\Models\Product::where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $query, $value): Builder => $query->where('product_name', 'like', "%{$value}%")
                        );
                    })
                    ->placeholder('Semua Produk'),

                // Filter Transaksi Bulan Ini
                Tables\Filters\Filter::make('payment_this_month')
                    ->label('Transaksi Bulan Ini')
                    ->query(fn ($query) => $query->whereMonth('payment_date', \Carbon\Carbon::now()->month)
                        ->whereYear('payment_date', \Carbon\Carbon::now()->year))
                    ->toggle(),
            ])
            ->defaultSort('payment_date', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Export Excel')
                    ->hidden()
                    ->color('success')
                    ->icon('heroicon-o-document-download')
                    ->form([
                        Forms\Components\Card::make()->schema([
                            Forms\Components\Select::make('filter_type')
                                ->label('Jenis Filter Tanggal')
                                ->options([
                                    'all' => 'Semua Data (Tanpa Filter)',
                                    'single' => 'Tanggal Tunggal',
                                    'range' => 'Rentang Tanggal',
                                ])
                                ->default('all')
                                ->reactive()
                                ->required(),
                            
                            Forms\Components\DatePicker::make('single_date')
                                ->label('Pilih Tanggal')
                                ->visible(fn ($get) => $get('filter_type') === 'single')
                                ->required(fn ($get) => $get('filter_type') === 'single')
                                ->closeOnDateSelection(),
                            
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Tanggal Mulai')
                                ->visible(fn ($get) => $get('filter_type') === 'range')
                                ->required(fn ($get) => $get('filter_type') === 'range')
                                ->closeOnDateSelection(),
                            
                            Forms\Components\DatePicker::make('end_date')
                                ->label('Tanggal Akhir')
                                ->visible(fn ($get) => $get('filter_type') === 'range')
                                ->required(fn ($get) => $get('filter_type') === 'range')
                                ->afterOrEqual('start_date')
                                ->closeOnDateSelection(),
                        ])
                    ])
                    ->action(function (array $data, $livewire) {
                        $params = ['format' => 'excel'];
                        
                        // Ambil filter yang sedang aktif dari tabel
                        $tableFilters = $livewire->getTableFiltersForm()->getState();
                        
                        // Tambahkan filter status jika ada
                        if (!empty($tableFilters['status']['value'])) {
                            $params['status_filter'] = $tableFilters['status']['value'];
                        }
                        
                        // Tambahkan filter jenis produk jika ada
                        if (!empty($tableFilters['product_type']['value'])) {
                            $params['product_type'] = $tableFilters['product_type']['value'];
                        }
                        
                        // Tambahkan filter bulan ini jika aktif
                        if (!empty($tableFilters['payment_this_month']['isActive'])) {
                            $params['this_month'] = '1';
                        }
                        
                        // Tambahkan filter tanggal dari form
                        if ($data['filter_type'] === 'single' && !empty($data['single_date'])) {
                            $params['filter_type'] = 'single';
                            $params['single_date'] = $data['single_date'];
                        } elseif ($data['filter_type'] === 'range' && !empty($data['start_date']) && !empty($data['end_date'])) {
                            $params['filter_type'] = 'range';
                            $params['start_date'] = $data['start_date'];
                            $params['end_date'] = $data['end_date'];
                        }
                        
                        $url = route('cetak-laporan-kasir', $params);
                        
                        // Show notification
                        \Filament\Notifications\Notification::make()
                            ->title('Excel berhasil dibuat')
                            ->success()
                            ->send();
                            
                        // Redirect to download URL
                        return redirect()->away($url);
                    })
                    ->modalHeading('Filter Export Excel')
                    ->modalSubheading('Export akan menggunakan filter yang sedang aktif di tabel + filter tanggal tambahan')
                    ->modalButton('Export Excel'),

                Tables\Actions\Action::make('print_pdf')
                    ->label('Cetak PDF')
                    ->color('warning')
                    ->icon('heroicon-o-printer')
                    ->form([
                        Forms\Components\Card::make()->schema([
                            Forms\Components\Select::make('filter_type')
                                ->label('Jenis Filter Tanggal')
                                ->options([
                                    'all' => 'Semua Data (Tanpa Filter)',
                                    'single' => 'Tanggal Tunggal',
                                    'range' => 'Rentang Tanggal',
                                ])
                                ->default('all')
                                ->reactive()
                                ->required(),
                            
                            Forms\Components\DatePicker::make('single_date')
                                ->label('Pilih Tanggal')
                                ->visible(fn ($get) => $get('filter_type') === 'single')
                                ->required(fn ($get) => $get('filter_type') === 'single')
                                ->closeOnDateSelection(),
                            
                            Forms\Components\DatePicker::make('start_date')
                                ->label('Tanggal Mulai')
                                ->visible(fn ($get) => $get('filter_type') === 'range')
                                ->required(fn ($get) => $get('filter_type') === 'range')
                                ->closeOnDateSelection(),
                            
                            Forms\Components\DatePicker::make('end_date')
                                ->label('Tanggal Akhir')
                                ->visible(fn ($get) => $get('filter_type') === 'range')
                                ->required(fn ($get) => $get('filter_type') === 'range')
                                ->afterOrEqual('start_date')
                                ->closeOnDateSelection(),
                        ])
                    ])
                    ->action(function (array $data, $livewire) {
                        $params = ['format' => 'pdf'];
                        
                        // Ambil filter yang sedang aktif dari tabel
                        $tableFilters = $livewire->getTableFiltersForm()->getState();
                        
                        // Tambahkan filter status jika ada
                        if (!empty($tableFilters['status']['value'])) {
                            $params['status_filter'] = $tableFilters['status']['value'];
                        }
                        
                        // Tambahkan filter jenis produk jika ada
                        if (!empty($tableFilters['product_type']['value'])) {
                            $params['product_type'] = $tableFilters['product_type']['value'];
                        }
                        
                        // Tambahkan filter bulan ini jika aktif
                        if (!empty($tableFilters['payment_this_month']['isActive'])) {
                            $params['this_month'] = '1';
                        }
                        
                        // Tambahkan filter tanggal dari form
                        if ($data['filter_type'] === 'single' && !empty($data['single_date'])) {
                            $params['filter_type'] = 'single';
                            $params['single_date'] = $data['single_date'];
                        } elseif ($data['filter_type'] === 'range' && !empty($data['start_date']) && !empty($data['end_date'])) {
                            $params['filter_type'] = 'range';
                            $params['start_date'] = $data['start_date'];
                            $params['end_date'] = $data['end_date'];
                        }
                        
                        $url = route('cetak-laporan-kasir', $params);
                        
                        // Show notification
                        \Filament\Notifications\Notification::make()
                            ->title('PDF berhasil dibuat')
                            ->success()
                            ->send();
                            
                        // Redirect to PDF URL
                        return redirect()->away($url);
                    })
                    ->modalHeading('Filter Cetak PDF')
                    ->modalSubheading('Export akan menggunakan filter yang sedang aktif di tabel + filter tanggal tambahan')
                    ->modalButton('Cetak PDF'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuickTransactions::route('/'),
            'create' => Pages\CreateQuickTransaction::route('/create'),
            'edit' => Pages\EditQuickTransaction::route('/{record}/edit'),
        ];
    }
}
