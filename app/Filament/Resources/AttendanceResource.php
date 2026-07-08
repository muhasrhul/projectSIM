<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    
    // Ganti Icon jadi Clipboard (untuk Absensi)
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-check';
    
    // Nama Menu di Samping
    protected static ?string $navigationLabel = 'Log Absensi';

    protected static ?string $slug = 'log-absensi';
    protected static ?string $pluralLabel = 'Log Absensi';
    protected static ?string $modelLabel = 'Absensi';
    
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
    
    // Eager loading untuk relasi member
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('member');
    } 

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                Forms\Components\Select::make('member_id')
                    ->label('Nama Member')
                    ->options(function () {
                        $today = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
                        return \App\Models\Member::all()->mapWithKeys(function ($member) use ($today) {
                            $expired = $member->expiry_date
                                ? \Carbon\Carbon::parse($member->expiry_date)->startOfDay()->lte($today)
                                : false;

                            if (!$member->is_active) {
                                return [$member->id => "{$member->name} ({$member->phone}) — Non-Aktif"];
                            }
                            if ($expired) {
                                return [$member->id => "{$member->name} ({$member->phone}) — Expired"];
                            }
                            return [$member->id => "{$member->name} ({$member->phone}) — Aktif"];
                        });
                    })
                    ->getSearchResultsUsing(function (string $search) {
                        $today = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
                        return \App\Models\Member::where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->get()
                            ->mapWithKeys(function ($member) use ($today) {
                                $expired = $member->expiry_date
                                    ? \Carbon\Carbon::parse($member->expiry_date)->startOfDay()->lte($today)
                                    : false;

                                if (!$member->is_active) {
                                    return [$member->id => "{$member->name} ({$member->phone}) — Non-Aktif"];
                                }
                                if ($expired) {
                                    return [$member->id => "{$member->name} ({$member->phone}) — Expired"];
                                }
                                return [$member->id => "{$member->name} ({$member->phone}) — Aktif"];
                            });
                    })
                    ->disableOptionWhen(function ($value) {
                        $member = \App\Models\Member::find($value);
                        if (!$member) return true;
                        $today = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
                        $expired = $member->expiry_date
                            ? \Carbon\Carbon::parse($member->expiry_date)->startOfDay()->lte($today)
                            : false;
                        return !$member->is_active || $expired;
                    })
                    ->searchable()
                    ->placeholder('Cari dan pilih nama member...')
                    ->required(),

                // Hanya kolom waktu check-in sesuai database baru
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Waktu Check-in')
                    ->default(now())
                    ->required(),
            ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('member_name')
                    ->label('Nama Member')
                    ->getStateUsing(fn ($record) => $record->member->name ?? 'Member Dihapus')
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
                            return $query->whereHas('member', function (Builder $query) use ($partialSearch) {
                                $query->where('name', 'like', "%{$partialSearch}%");
                            });
                        }
                        
                        // Cek apakah search dimulai dengan = untuk exact match
                        $isExactSearch = str_starts_with($search, '=');
                        
                        if ($isExactSearch) {
                            // Exact match - hilangkan tanda =
                            $exactSearch = ltrim($search, '=');
                            return $query->whereHas('member', function (Builder $query) use ($exactSearch) {
                                $query->where('name', '=', $exactSearch);
                            });
                        }
                        
                        // Default: Smart search - case-insensitive exact match dulu, lalu partial
                        return $query->whereHas('member', function (Builder $query) use ($search) {
                            $query->whereRaw('LOWER(name) = LOWER(?)', [$search])
                                ->orWhere(function ($subQuery) use ($search) {
                                    // Jika exact match tidak ada hasil, coba partial match
                                    $words = explode(' ', $search);
                                    foreach ($words as $word) {
                                        if (strlen(trim($word)) >= 2) {
                                            $subQuery->where('name', 'like', "%{$word}%");
                                        }
                                    }
                                });
                        });
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Jam Latihan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
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
                    ->action(function (array $data) {
                        $params = ['format' => 'excel'];
                        
                        if ($data['filter_type'] === 'single' && !empty($data['single_date'])) {
                            $params['filter_type'] = 'single';
                            $params['single_date'] = $data['single_date'];
                        } elseif ($data['filter_type'] === 'range' && !empty($data['start_date']) && !empty($data['end_date'])) {
                            $params['filter_type'] = 'range';
                            $params['start_date'] = $data['start_date'];
                            $params['end_date'] = $data['end_date'];
                        }
                        
                        $url = route('export-attendance', $params);
                        
                        // Show notification
                        \Filament\Notifications\Notification::make()
                            ->title('Excel berhasil dibuat')
                            ->success()
                            ->send();
                            
                        // Redirect to download URL
                        return redirect()->away($url);
                    })
                    ->modalHeading('Filter Export Excel')
                    ->modalSubheading('Pilih filter tanggal untuk data absensi yang akan di-export ke Excel')
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
                    ->action(function (array $data) {
                        $params = ['format' => 'pdf'];
                        
                        if ($data['filter_type'] === 'single' && !empty($data['single_date'])) {
                            $params['filter_type'] = 'single';
                            $params['single_date'] = $data['single_date'];
                        } elseif ($data['filter_type'] === 'range' && !empty($data['start_date']) && !empty($data['end_date'])) {
                            $params['filter_type'] = 'range';
                            $params['start_date'] = $data['start_date'];
                            $params['end_date'] = $data['end_date'];
                        }
                        
                        $url = route('export-attendance', $params);
                        
                        // Show notification
                        \Filament\Notifications\Notification::make()
                            ->title('PDF berhasil dibuat')
                            ->success()
                            ->send();
                            
                        // Redirect to PDF URL
                        return redirect()->away($url);
                    })
                    ->modalHeading('Filter Cetak PDF')
                    ->modalSubheading('Pilih filter tanggal untuk data absensi yang akan dicetak ke PDF')
                    ->modalButton('Cetak PDF'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}