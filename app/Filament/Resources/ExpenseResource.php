<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static ?string $navigationIcon = 'heroicon-o-pencil';
    protected static ?int $navigationSort = 3; // Urutan ketiga
    protected static ?string $navigationLabel = 'Catatan Pengeluaran';
    protected static ?string $pluralLabel = 'Catatan Pengeluaran';
    protected static ?string $navigationGroup = 'Transaksi';
    
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
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\DateTimePicker::make('expense_date')
                    ->label('Tanggal & Waktu Pengeluaran')
                    ->default(now())
                    ->required()
                    ->displayFormat('d/m/Y H:i'),

                Forms\Components\Select::make('category')
                    ->label('Kategori')
                    ->options([
                        'Operasional Harian' => 'Operasional Harian',
                        'Peralatan & Maintenance' => 'Peralatan & Maintenance',
                        'Utilitas (Listrik, Air, Internet)' => 'Utilitas (Listrik, Air, Internet)',
                        'Kebersihan & Sanitasi' => 'Kebersihan & Sanitasi',
                        'Marketing & Promosi' => 'Marketing & Promosi',
                        'Administrasi & Pajak' => 'Administrasi & Pajak',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->required()
                    ->searchable(),

                Forms\Components\TextInput::make('item')
                    ->label('Item/Barang')
                    ->required()
                    ->placeholder('Contoh: Sabun cuci tangan, Lampu LED, dll'),

                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(1),

                Forms\Components\TextInput::make('amount')
                    ->label('Total Harga (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(0),

                Forms\Components\TextInput::make('receipt_number')
                    ->label('Nomor Nota/Kwitansi')
                    ->placeholder('Opsional - untuk tracking')
                    ->helperText('Nomor nota pembelian atau kwitansi (jika ada)'),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->placeholder('Catatan tambahan (opsional)')
                    ->rows(3)
                    ->helperText('Keterangan tambahan tentang pengeluaran ini'),

                Forms\Components\Hidden::make('created_by')
                    ->default(auth()->id()),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Kategori')
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('item')
                    ->label('Item/Barang')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Total Harga')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('danger')
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('No. Nota')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dicatat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Input')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('expense_date', 'desc')
            ->filters([
                // Filter 1: Kategori
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'Operasional Harian' => 'Operasional Harian',
                        'Peralatan & Maintenance' => 'Peralatan & Maintenance',
                        'Utilitas (Listrik, Air, Internet)' => 'Utilitas (Listrik, Air, Internet)',
                        'Kebersihan & Sanitasi' => 'Kebersihan & Sanitasi',
                        'Marketing & Promosi' => 'Marketing & Promosi',
                        'Administrasi & Pajak' => 'Administrasi & Pajak',
                        'Lainnya' => 'Lainnya',
                    ])
                    ->placeholder('Semua Kategori'),

                // Filter 2: Pengeluaran Bulan Ini
                Tables\Filters\Filter::make('expense_this_month')
                    ->label('Pengeluaran Bulan Ini')
                    ->query(fn ($query) => $query->whereMonth('expense_date', \Carbon\Carbon::now()->month)
                        ->whereYear('expense_date', \Carbon\Carbon::now()->year))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
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
                        
                        // Tambahkan filter kategori jika ada
                        if (!empty($tableFilters['category']['value'])) {
                            $params['category'] = $tableFilters['category']['value'];
                        }
                        
                        // Tambahkan filter bulan ini jika aktif
                        if (!empty($tableFilters['expense_this_month']['isActive'])) {
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
                        
                        $url = route('cetak-laporan-pengeluaran', $params);
                        
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
                        
                        // Tambahkan filter kategori jika ada
                        if (!empty($tableFilters['category']['value'])) {
                            $params['category'] = $tableFilters['category']['value'];
                        }
                        
                        // Tambahkan filter bulan ini jika aktif
                        if (!empty($tableFilters['expense_this_month']['isActive'])) {
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
                        
                        $url = route('cetak-laporan-pengeluaran', $params);
                        
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
