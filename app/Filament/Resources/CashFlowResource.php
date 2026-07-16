<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashFlowResource\Pages;
use App\Filament\Resources\CashFlowResource\RelationManagers;
use App\Models\CashFlow;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CashFlowResource extends Resource
{
    protected static ?string $model = CashFlow::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Analisis Laporan';
    
    protected static ?string $modelLabel = 'Analisis Laporan';
    
    protected static ?string $pluralModelLabel = 'Analisis Laporan';
    
    protected static ?string $navigationGroup = 'Laporan';
    
    protected static ?int $navigationSort = 1;

    // PERMISSION: Hanya Super Admin yang bisa akses Pembukuan
    public static function canViewAny(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canCreate(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('date')
                    ->label('Tanggal')
                    ->required()
                    ->default(now())
                    ->displayFormat('d/m/Y H:i'),
                    
                Forms\Components\Hidden::make('type')
                    ->default('expense'),
                    
                Forms\Components\Hidden::make('source')
                    ->default('pengeluaran'),
                    
                Forms\Components\Placeholder::make('info')
                    ->label('Informasi')
                    ->content('Form ini khusus untuk mencatat pengeluaran manual. Pemasukan dari member dan kasir akan tercatat otomatis.')
                    ->visible(function ($context) {
                        return $context === 'create';
                    }),
                    
                Forms\Components\TextInput::make('reference_id')
                    ->label('ID Referensi')
                    ->numeric()
                    ->disabled()
                    ->helperText('ID referensi akan terisi otomatis untuk transaksi dari sistem')
                    ->visible(function ($context) {
                        return $context === 'edit';
                    }),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Keterangan')
                    ->required()
                    ->maxLength(65535)
                    ->placeholder('Contoh: Pembelian alat gym, Listrik bulan ini, Gaji karyawan, dll')
                    ->rows(3),
                    
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0.01)
                    ->step(0.01)
                    ->placeholder('0'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('source')
                    ->label('Sumber')
                    ->enum([
                        'member' => 'Member',
                        'kasir' => 'Kasir',
                        'pengeluaran' => 'Cat. Pengeluaran',
                    ])
                    ->colors([
                        'secondary' => ['member', 'kasir', 'pengeluaran'],
                    ]),
                    
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->enum([
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    ])
                    ->colors([
                        'success' => 'income',
                        'danger' => 'expense',
                    ]),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('amount')
                    ->label('Pemasukan')
                    ->getStateUsing(function ($record) {
                        return $record->type === 'income' ? $record->amount : null;
                    })
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Rp ' . number_format($state, 0, ',', '.') : null;
                    })
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('expense_amount')
                    ->label('Pengeluaran')
                    ->getStateUsing(function ($record) {
                        return $record->type === 'expense' ? $record->amount : null;
                    })
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Rp ' . number_format($state, 0, ',', '.') : null;
                    })
                    ->color('danger'),
                    
                Tables\Columns\TextColumn::make('running_balance')
                    ->label('Saldo')
                    ->getStateUsing(function ($record) {
                        // Hitung saldo berdasarkan URUTAN TANGGAL ASC dalam BULAN YANG SAMA
                        $balance = 0;
                        
                        // Ambil semua record dalam bulan yang sama dengan record ini
                        // yang tanggalnya <= record ini, urutkan ASC untuk perhitungan
                        $records = CashFlow::whereMonth('date', $record->date->month)
                            ->whereYear('date', $record->date->year)
                            ->where(function($query) use ($record) {
                                $query->where('date', '<', $record->date)
                                      ->orWhere(function($q) use ($record) {
                                          $q->where('date', '=', $record->date)
                                            ->where('id', '<=', $record->id);
                                      });
                            })
                            ->orderBy('date', 'asc')
                            ->orderBy('id', 'asc')
                            ->get(['type', 'amount']);
                            
                        // Hitung saldo kumulatif berdasarkan urutan chronological dalam bulan
                        foreach ($records as $r) {
                            if ($r->type === 'income') {
                                $balance += $r->amount;
                            } else {
                                $balance -= $r->amount;
                            }
                        }
                        
                        return $balance;
                    })
                    ->formatStateUsing(function ($state) {
                        return 'Rp ' . number_format($state, 0, ',', '.');
                    })
                    ->color('primary'),
            ])
            ->filters([
                Filter::make('month')
                    ->form([
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Pilih Bulan')
                            ->options(function () {
                                $months = [];
                                $now = \Carbon\Carbon::now('Asia/Makassar');
                                
                                // Generate 24 bulan (2 tahun terakhir)
                                for ($i = 0; $i < 24; $i++) {
                                    $date = $now->copy()->subMonths($i);
                                    $key = $date->format('Y-m'); // 2026-04
                                    $value = $date->translatedFormat('F Y'); // April 2026
                                    $months[$key] = $value;
                                }
                                
                                return $months;
                            })
                            ->searchable()
                            ->placeholder('Pilih bulan...')
                            ->default(now()->format('Y-m')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // Jika ada filter bulan yang dipilih, gunakan itu
                        if (!empty($data['month'])) {
                            $date = \Carbon\Carbon::parse($data['month'] . '-01');
                            return $query->whereMonth('date', $date->month)
                                        ->whereYear('date', $date->year);
                        }
                        
                        // Jika tidak ada filter, default ke bulan berjalan
                        $now = \Carbon\Carbon::now('Asia/Makassar');
                        return $query->whereMonth('date', $now->month)
                                    ->whereYear('date', $now->year);
                    })
                    ->default(),
                    
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    ]),
                    
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options([
                        'member' => 'Member',
                        'kasir' => 'Kasir Cepat',
                        'pengeluaran' => 'Cat. Pengeluaran',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_source')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->action(function ($record) {
                        // Redirect ke halaman edit berdasarkan sumber data
                        switch ($record->source) {
                            case 'member':
                                return redirect()->route('filament.resources.transactions.edit', $record->reference_id);
                                
                            case 'kasir':
                                return redirect()->route('filament.resources.quick-transactions.edit', $record->reference_id);
                                
                            case 'pengeluaran':
                                return redirect()->route('filament.resources.expenses.edit', $record->reference_id);
                        }
                    }),
            ])
            ->bulkActions([
                // Tidak ada bulk actions untuk view
            ])
            ->defaultSort('date', 'desc');
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
            'index' => Pages\ListCashFlows::route('/'),
            'create' => Pages\CreateCashFlow::route('/create'),
            'edit' => Pages\EditCashFlow::route('/{record}/edit'),
        ];
    }
}
