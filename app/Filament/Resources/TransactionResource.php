<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-cash';
    protected static ?int $navigationSort = 1; // Urutan pertama
    protected static ?string $navigationLabel = 'Transaksi Membership';
    protected static ?string $pluralLabel = 'Transaksi Membership';
    protected static ?string $navigationGroup = 'Keuangan';
    
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
    
    // Filter otomatis: Hanya tampilkan transaksi member reguler (bukan kasir cepat)
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('member')
            // PERBAIKAN: Jangan filter berdasarkan member yang ada
            // Karena member bisa dihapus tapi transaksi harus tetap tampil
            ->where(function (Builder $query) {
                // Filter hanya berdasarkan guest_name, bukan relasi member
                $query->where('guest_name', '!=', 'Tamu Harian')
                      ->where('guest_name', '!=', 'Tamu Latihan Harian')
                      // ATAU jika member masih ada, filter berdasarkan member name
                      ->orWhereHas('member', function (Builder $subQuery) {
                          $subQuery->where('name', '!=', 'Tamu Harian')
                                   ->where('name', '!=', 'Tamu Latihan Harian');
                      });
            })
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                // 1. Pilih Member (Bisa dikosongkan jika tamu harian)
                Forms\Components\Select::make('member_id')
                    ->label('Pilih Member (Jika terdaftar)')
                    ->relationship('member', 'name')
                    ->searchable()
                    ->placeholder('Cari nama member...'),

                // 2. Input Nama Tamu (Jika tidak mau daftar member)
                Forms\Components\TextInput::make('guest_name')
                    ->label('Nama Tamu / Harian')
                    ->placeholder('Ketik nama jika bukan member terdaftar')
                    ->helperText('Isi ini jika Anda tidak memilih nama di kolom atas.'),

                Forms\Components\TextInput::make('order_id')
                    ->label('ID Transaksi / Order ID')
                    ->default(function () {
                        $today = now()->format('Ymd');
                        $prefix = 'TRX-' . $today . '-';
                        $last = \App\Models\Transaction::where('order_id', 'like', $prefix . '%')
                            ->orderBy('order_id', 'desc')
                            ->value('order_id');
                        $nextNumber = $last ? (intval(substr($last, -4)) + 1) : 1;
                        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                    })
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->label('Nominal (Rp)')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Forms\Components\Select::make('type')
                    ->label('Kategori')
                    ->options(function () {
                        $options = [];
                        
                        // Ambil semua paket aktif untuk membuat opsi pendaftaran dan perpanjangan
                        $pakets = \App\Models\Paket::where('is_active', true)->get();
                        
                        foreach ($pakets as $paket) {
                            $options["Pendaftaran Baru: {$paket->nama_paket}"] = "Pendaftaran Baru: {$paket->nama_paket}";
                            $options["Perpanjangan: {$paket->nama_paket}"] = "Perpanjangan: {$paket->nama_paket}";
                        }
                        
                        // Tambahkan kategori lainnya
                        $options['Harian (Insidentil)'] = 'Harian (Insidentil)';
                        $options['Minuman/Kantin'] = 'Minuman/Kantin';
                        
                        return $options;
                    })
                    ->default('Harian (Insidentil)')
                    ->required()
                    ->searchable(),

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
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // KOLOM PERTAMA: Sumber transaksi (selalu Member Reguler karena sudah difilter)
                Tables\Columns\BadgeColumn::make('source')
                    ->label('Sumber')
                    ->getStateUsing(fn () => 'Member Reguler')
                    ->color('primary')
                    ->icon('heroicon-o-user-group')
                    ->toggleable(isToggledHiddenByDefault: false),

                // KOLOM KEDUA: Nama customer
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Nama Customer')
                    ->getStateUsing(fn ($record) => $record->member ? $record->member->name : ($record->guest_name ?? 'Umum'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Cek apakah input adalah nomor telepon
                        $normalizedSearch = preg_replace('/[^0-9]/', '', $search);
                        $isPhoneSearch = !empty($normalizedSearch) && strlen($normalizedSearch) >= 8 && strlen($normalizedSearch) <= 15;
                        
                        if ($isPhoneSearch) {
                            // Phone search - exact match untuk nomor telepon
                            return $query->whereHas('member', function (Builder $query) use ($normalizedSearch) {
                                $searchPatterns = [];
                                
                                // Add original pattern
                                $searchPatterns[] = $normalizedSearch;
                                
                                // Convert 62xxx to 0xxx (handle all lengths >= 9)
                                if (str_starts_with($normalizedSearch, '62') && strlen($normalizedSearch) >= 9) {
                                    $searchPatterns[] = '0' . substr($normalizedSearch, 2);
                                }
                                
                                // Convert 0xxx to 62xxx
                                if (str_starts_with($normalizedSearch, '0') && strlen($normalizedSearch) >= 10) {
                                    $searchPatterns[] = '62' . substr($normalizedSearch, 1);
                                }
                                
                                // Handle 8xxx format
                                if (str_starts_with($normalizedSearch, '8') && strlen($normalizedSearch) >= 9) {
                                    $searchPatterns[] = '0' . $normalizedSearch;
                                    $searchPatterns[] = '62' . $normalizedSearch;
                                }
                                
                                // Use REPLACE for exact phone match
                                $query->where(function ($phoneQuery) use ($searchPatterns) {
                                    foreach ($searchPatterns as $pattern) {
                                        $phoneQuery->orWhereRaw(
                                            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') = ?", 
                                            [$pattern]
                                        );
                                    }
                                });
                            });
                        }
                        
                        // Cek apakah search dimulai dengan * untuk partial match
                        $isPartialSearch = str_starts_with($search, '*');
                        
                        if ($isPartialSearch) {
                            // Partial match - hilangkan tanda *
                            $partialSearch = ltrim($search, '*');
                            return $query
                                ->where('guest_name', 'like', "%{$partialSearch}%")
                                ->orWhereHas('member', function (Builder $query) use ($partialSearch) {
                                    $query->where('name', 'like', "%{$partialSearch}%");
                                });
                        }
                        
                        // Cek apakah search dimulai dengan = untuk exact match
                        $isExactSearch = str_starts_with($search, '=');
                        
                        if ($isExactSearch) {
                            // Exact match - hilangkan tanda =
                            $exactSearch = ltrim($search, '=');
                            return $query
                                ->where('guest_name', '=', $exactSearch)
                                ->orWhereHas('member', function (Builder $query) use ($exactSearch) {
                                    $query->where('name', '=', $exactSearch);
                                });
                        }
                        
                        // Default: Smart search - case-insensitive exact match dulu, lalu partial
                        return $query
                            ->where(function ($exactQuery) use ($search) {
                                $exactQuery->whereRaw('LOWER(guest_name) = LOWER(?)', [$search])
                                    ->orWhereHas('member', function (Builder $query) use ($search) {
                                        $query->whereRaw('LOWER(name) = LOWER(?)', [$search]);
                                    });
                            })
                            ->orWhere(function ($partialQuery) use ($search) {
                                // Jika exact match tidak ada hasil, coba partial match
                                $words = explode(' ', $search);
                                foreach ($words as $word) {
                                    if (strlen(trim($word)) >= 2) {
                                        $partialQuery->where('guest_name', 'like', "%{$word}%")
                                            ->orWhereHas('member', function (Builder $query) use ($word) {
                                                $query->where('name', 'like', "%{$word}%");
                                            });
                                    }
                                }
                            });
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('success')
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Kategori')
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'pending',
                        'danger' => 'failed',
                        'secondary' => 'refund',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'paid',
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-x-circle' => 'failed',
                        'heroicon-o-arrow-left' => 'refund',
                    ])
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                    
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('payment_date', 'desc')
            ->filters([
                // Filter 1: Tipe Paket (berdasarkan transaction.type)
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Paket')
                    ->options(function () {
                        // Hanya paket aktif dari database
                        return \App\Models\Paket::where('is_active', true)
                            ->pluck('nama_paket', 'nama_paket')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            function (Builder $query, $value): Builder {
                                if ($value === 'Member Harian') {
                                    return $query->where('type', 'like', '%Harian%');
                                } elseif ($value === 'Member 1 Bulan') {
                                    return $query->where('type', 'like', '%1 Bulan%');
                                } elseif ($value === 'Mingguan') {
                                    return $query->where('type', 'like', '%Mingguan%');
                                } else {
                                    // Untuk paket lain, gunakan LIKE dengan nama paket
                                    return $query->where('type', 'like', "%{$value}%");
                                }
                            }
                        );
                    })
                    ->placeholder('Semua Paket'),

                // Filter 2: Transaksi Bulan Ini
                Tables\Filters\Filter::make('payment_this_month')
                    ->label('Transaksi Bulan Ini')
                    ->query(fn ($query) => $query->whereMonth('payment_date', \Carbon\Carbon::now()->month)
                        ->whereYear('payment_date', \Carbon\Carbon::now()->year))
                    ->toggle(),

                // Filter 3: Data yang dihapus (seperti di MemberResource)
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\RestoreAction::make(),
                // ForceDeleteAction tidak digunakan untuk keamanan data
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
                        
                        // Tambahkan filter tipe paket jika ada
                        if (!empty($tableFilters['type']['value'])) {
                            $params['paket_type'] = $tableFilters['type']['value'];
                        }
                        
                        // Tambahkan filter bulan ini jika aktif
                        if (!empty($tableFilters['payment_this_month']['isActive'])) {
                            $params['this_month'] = '1';
                        }
                        
                        // Tambahkan filter trashed jika ada
                        if (!empty($tableFilters['trashed']['value'])) {
                            $params['trashed'] = $tableFilters['trashed']['value'];
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
                        
                        $url = route('cetak-laporan', $params);
                        
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
                        
                        // Tambahkan filter tipe paket jika ada
                        if (!empty($tableFilters['type']['value'])) {
                            $params['paket_type'] = $tableFilters['type']['value'];
                        }
                        
                        // Tambahkan filter bulan ini jika aktif
                        if (!empty($tableFilters['payment_this_month']['isActive'])) {
                            $params['this_month'] = '1';
                        }
                        
                        // Tambahkan filter trashed jika ada
                        if (!empty($tableFilters['trashed']['value'])) {
                            $params['trashed'] = $tableFilters['trashed']['value'];
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
                        
                        $url = route('cetak-laporan', $params);
                        
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}