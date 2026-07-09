<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\BadgeColumn;
use App\Filament\Resources\MemberResource\Pages;
use App\Models\Member;
use App\Models\Paket;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Carbon\Carbon;
// Import untuk fitur Actions
use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Database\Eloquent\SoftDeletingScope; // Tidak perlu lagi karena soft delete dihapus
// use Filament\Tables\Actions\RestoreAction; // Tidak perlu lagi karena soft delete dihapus
// use Filament\Tables\Actions\ForceDeleteAction; // DIHAPUS: Tidak digunakan lagi
// use Filament\Tables\Filters\TrashedFilter; // Tidak perlu lagi karena soft delete dihapus

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Daftar Member';
    protected static ?string $pluralLabel = 'Daftar Member';
    protected static ?string $modelLabel = 'Member';

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

    // Mendukung pembacaan data yang sudah di-soft delete
    // Soft delete sudah dihapus, tidak perlu withoutGlobalScopes lagi
    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()
    //         ->withoutGlobalScopes([
    //             SoftDeletingScope::class,
    //         ]);
    // }

    protected static function getNavigationBadge(): ?string
    {
        // Menghitung member yang tidak aktif DAN belum punya tanggal expired (Pendaftar Baru)
        $count = static::getModel()::where('is_active', false)
            ->whereNull('expiry_date')
            ->count();
        return $count > 0 ? (string) $count : null;
    }


    protected static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Lengkap')
                        ->placeholder('Masukkan nama lengkap member')
                        ->required()
                        ->minLength(2)
                        ->maxLength(100)
                        ->rules([
                            'min:2',
                            'max:100',
                            'regex:/^[a-zA-Z][a-zA-Z\s\'.\-]*$/', // harus diawali huruf, hanya huruf, spasi, petik, titik, hubung
                            'not_regex:/^\s+$/',                   // tidak boleh hanya spasi
                        ]),

                    // Forms\Components\TextInput::make('nik')
                    //     ->label('NIK KTP')
                    //     ->maxLength(16)
                    //     ->minLength(16)
                    //     ->numeric()
                    //     ->placeholder('Masukkan 16 digit NIK KTP')
                    //     ->unique(ignorable: fn ($record) => $record),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->label('Email')
                            ->placeholder('Masukkan alamat email')
                            ->maxLength(254)
                            ->rules(['max:254', 'regex:/^\S+$/', 'regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/'])
                            ->unique(ignorable: fn ($record) => $record),

                        Forms\Components\TextInput::make('phone')
                            ->label('WhatsApp')
                            ->numeric()
                            ->placeholder('Masukkan nomor WhatsApp')
                            ->required()
                            ->rules([
                                'regex:/^\+?628[0-9]{7,12}$|^08[0-9]{7,12}$|^8[0-9]{8,13}$/',
                            ])
                            ->unique(ignorable: fn ($record) => $record),
                    ]),

                    // Forms\Components\TextInput::make('fingerprint_id')
                    //     ->label('Fingerprint ID')
                    //     ->maxLength(50)
                    //     ->unique(ignorable: fn ($record) => $record)
                    //     ->placeholder('Input ID Fingerprint')
                    //     ->helperText('Di input oleh admin'),
                    
                    Forms\Components\Section::make('Informasi Membership')->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipe Member')
                            ->options(Paket::where('is_active', true)->pluck('nama_paket', 'nama_paket'))
                            ->placeholder('Pilih tipe member')
                            ->reactive() 
                            ->required()
                            ->disabled(fn ($record) => $record && $record->is_active)
                            ->afterStateUpdated(function ($state, $set, $get, $record) {
                                // 1. Cari Paket di Database
                                $paket = Paket::where('nama_paket', $state)->first();
                                $harga = $paket ? (int)$paket->harga : 0;
                                $registrationFee = $paket ? (int)$paket->registration_fee : 0;

                                // 2. TIDAK ada update expiry_date otomatis!
                                // Admin harus input manual

                                // 3. Update Breakdown Biaya
                                // Jika member sudah pernah punya expiry_date (perpanjangan), fee = 0
                                // Jika belum pernah punya expiry_date (pendaftar baru), tampilkan fee
                                // TAMBAHAN: Jika paket harian (durasi < 30), fee = 0
                                // TAMBAHAN: Jika member sudah aktif, fee = 0 (tidak boleh charge lagi)
                                $isPerpanjangan = $record && $record->expiry_date;
                                $isMemberAktif = $record && $record->is_active;
                                
                                // Cek apakah paket harian
                                $isPaketHarian = $paket && $paket->durasi_hari < 30;
                                
                                $set('biaya_paket_info', $harga);
                                
                                // Set biaya registrasi: 0 jika perpanjangan ATAU paket harian ATAU member sudah aktif
                                if ($isPerpanjangan || $isPaketHarian || $isMemberAktif) {
                                    $set('biaya_registrasi_info', 0);
                                    $set('harga_paket_info', $harga);
                                    $set('total_tagihan_hidden', $harga);
                                } else {
                                    $set('biaya_registrasi_info', $registrationFee);
                                    $set('harga_paket_info', $harga + $registrationFee);
                                    $set('total_tagihan_hidden', $harga + $registrationFee);
                                }
                            }),

                        // --- FIELD METODE PEMBAYARAN ---
                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => 'QRIS',
                                'transfer_bank' => 'Transfer Bank',
                            ])
                            ->placeholder('Pilih metode pembayaran')
                            ->required(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\DatePicker::make('join_date')
                                ->label('Tanggal Mulai')
                                ->placeholder('Pilih tanggal mulai membership')
                                ->helperText('Tanggal mulai tidak berubah saat perpanjangan')
                                ->default(now())
                                ->required()
                                ->reactive()
                                ->closeOnDateSelection()
                                ->rule('required', 'Tanggal mulai membership wajib diisi'),

                            Forms\Components\DatePicker::make('expiry_date')
                                ->label('Tanggal Berakhir')
                                ->reactive()
                                ->required(fn ($get) => $get('is_active') === true)
                                ->closeOnDateSelection()
                                ->placeholder(function ($get) {
                                    $joinDate = $get('join_date');
                                    $paketType = $get('type');
                                    
                                    if ($joinDate && $paketType) {
                                        $paket = \App\Models\Paket::where('nama_paket', $paketType)->first();
                                        if ($paket) {
                                            $durasi = $paket->durasi_hari;
                                            $tanggalMulai = \Carbon\Carbon::parse($joinDate);
                                            
                                            if ($durasi >= 30) {
                                                // Paket bulanan: hitung bulan dari durasi_hari
                                                $bulan = round($durasi / 30);
                                                $rekomendasiExpiry = $tanggalMulai->copy()->addMonths($bulan);
                                            } else {
                                                // Paket harian: expired di hari yang sama (durasi = 1) atau sesuai durasi
                                                if ($durasi == 1) {
                                                    // Member harian expired di hari yang sama
                                                    $rekomendasiExpiry = $tanggalMulai->copy();
                                                } else {
                                                    // Paket beberapa hari (misal 3 hari, 7 hari)
                                                    $rekomendasiExpiry = $tanggalMulai->copy()->addDays($durasi - 1);
                                                }
                                            }
                                            
                                            return 'Rekomendasi: ' . $rekomendasiExpiry->format('d/m/Y');
                                        }
                                    }
                                    
                                    return 'Pilih tanggal mulai dan paket dulu';
                                })
                                ->helperText(function ($record, $get) {
                                    if (!$record) {
                                        $joinDate = $get('join_date');
                                        $paketType = $get('type');
                                        
                                        if ($joinDate && $paketType) {
                                            $paket = \App\Models\Paket::where('nama_paket', $paketType)->first();
                                            if ($paket) {
                                                $durasi = $paket->durasi_hari;
                                                $tanggalMulai = \Carbon\Carbon::parse($joinDate);
                                                
                                                if ($durasi >= 30) {
                                                    $bulan = round($durasi / 30);
                                                    $rekomendasiExpiry = $tanggalMulai->copy()->addMonths($bulan);
                                                    return "💡 Rekomendasi: {$rekomendasiExpiry->format('d/m/Y')} (dari tanggal mulai + {$bulan} bulan).";
                                                } else {
                                                    if ($durasi == 1) {
                                                        $rekomendasiExpiry = $tanggalMulai->copy();
                                                        return "💡 Rekomendasi: {$rekomendasiExpiry->format('d/m/Y')} (member harian expired di hari yang sama).";
                                                    } else {
                                                        $rekomendasiExpiry = $tanggalMulai->copy()->addDays($durasi - 1);
                                                        return "💡 Rekomendasi otomatis: {$rekomendasiExpiry->format('d/m/Y')} (dari tanggal mulai + {$durasi} hari). WAJIB diisi jika toggle Status Aktif dinyalakan.";
                                                    }
                                                }
                                            }
                                        }
                                        
                                        return null;
                                    }
                                    
                                    if ($record->expiry_date) {
                                        $expiredDate = \Carbon\Carbon::parse($record->expiry_date)->format('d/m/Y');
                                        
                                        // Jika member expired (tidak aktif tapi punya expiry_date)
                                        if (!$record->is_active) {
                                            return "Member sudah expired pada: {$expiredDate}. Ubah tanggal berakhir yang baru untuk perpanjangan.";
                                        }
                                        
                                        return "Tanggal berakhir saat ini: {$expiredDate}";
                                    }
                                    
                                    return 'WAJIB diisi jika toggle Status Aktif dinyalakan.';
                                }),
                        ]),

                        // --- KOLOM TAGIHAN KASIR (BREAKDOWN) ---
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('biaya_paket_info')
                                ->label('Biaya Paket')
                                ->placeholder('')
                                ->numeric()
                                ->prefix('Rp')
                                ->reactive()
                                ->dehydrated(fn ($record) => !($record && $record->is_active)) // Kirim ke backend hanya saat belum aktif
                                ->disabled(fn ($record) => $record && $record->is_active) // Disable jika sudah aktif
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    // Update total saat biaya paket diubah manual
                                    $biayaPaket = (int)($state ?? 0);
                                    $biayaRegistrasi = (int)($get('biaya_registrasi_info') ?? 0);
                                    $total = $biayaPaket + $biayaRegistrasi;
                                    $set('harga_paket_info', $total);
                                    $set('total_tagihan_hidden', $total);
                                })
                                ->afterStateHydrated(function ($set, $get, $record) {
                                    if ($record && $record->type) {
                                        $paket = Paket::where('nama_paket', $record->type)->first();
                                        $hargaPaket = $paket ? (int)$paket->harga : 0;
                                        
                                        if ($record->is_active) {
                                            // Ambil transaksi terakhir (pendaftaran atau perpanjangan)
                                            $transaksi = \App\Models\Transaction::where('member_id', $record->id)
                                                ->where(function($query) {
                                                    $query->where('type', 'like', 'Pendaftaran Baru%')
                                                          ->orWhere('type', 'like', 'Perpanjangan%')
                                                          ->orWhere('type', 'like', 'Perpanjang Member%');
                                                })
                                                ->latest('payment_date')
                                                ->first();
                                            
                                            if ($transaksi) {
                                                $isPerpanjangan = str_contains($transaksi->type, 'Perpanjangan') || str_contains($transaksi->type, 'Perpanjang Member');
                                                
                                                if ($isPerpanjangan) {
                                                    // Perpanjangan: tidak ada fee, biaya paket = amount langsung
                                                    $set('biaya_paket_info', (int)$transaksi->amount);
                                                } else {
                                                    // Pendaftaran baru: biaya paket = amount - registration_fee
                                                    $registrationFee = $paket ? (int)$paket->registration_fee : 0;
                                                    $biayaPaket = max(0, (int)$transaksi->amount - $registrationFee);
                                                    $set('biaya_paket_info', $biayaPaket);
                                                }
                                            } else {
                                                $set('biaya_paket_info', $hargaPaket);
                                            }
                                        } elseif ($record->expiry_date) {
                                            // Member expired: set 0 (akan terisi otomatis saat ganti paket via afterStateUpdated)
                                            $set('biaya_paket_info', 0);
                                        } else {
                                            // Pendaftar baru: tampilkan harga paket
                                            $set('biaya_paket_info', $hargaPaket);
                                        }
                                    }
                                })
                                ->extraInputAttributes(['style' => 'font-weight: 700; color: #059669; background-color: #f0fdf4;']),

                            Forms\Components\TextInput::make('biaya_registrasi_info')
                                ->label('Biaya Admin')
                                ->placeholder('')
                                ->numeric()
                                ->prefix('Rp')
                                ->reactive()
                                ->dehydrated(false)
                                ->disabled(function ($record, $get) {
                                    // Disable jika sudah aktif ATAU sudah pernah punya expiry_date
                                    if ($record && ($record->is_active || $record->expiry_date)) {
                                        return true;
                                    }
                                    
                                    // Disable jika paket harian (durasi < 30 hari)
                                    $paketName = $get('type');
                                    if ($paketName) {
                                        $paket = Paket::where('nama_paket', $paketName)->first();
                                        if ($paket && $paket->durasi_hari < 30) {
                                            return true; // Disable untuk paket harian
                                        }
                                    }
                                    
                                    return false;
                                })
                                ->afterStateUpdated(function ($state, $set, $get) {
                                    // Update total saat biaya registrasi diubah manual
                                    $biayaPaket = (int)($get('biaya_paket_info') ?? 0);
                                    $biayaRegistrasi = (int)($state ?? 0);
                                    $total = $biayaPaket + $biayaRegistrasi;
                                    $set('harga_paket_info', $total);
                                    $set('total_tagihan_hidden', $total);
                                })
                                ->afterStateHydrated(function ($set, $get, $record) {
                                    if ($record && $record->type) {
                                        $paket = Paket::where('nama_paket', $record->type)->first();
                                        $registrationFee = $paket ? (int)$paket->registration_fee : 0;
                                        $hargaPaket = $paket ? (int)$paket->harga : 0;
                                        
                                        // Paksa set 0 untuk paket harian DAN update total
                                        if ($paket && $paket->durasi_hari < 30) {
                                            $set('biaya_registrasi_info', 0);
                                            // Untuk paket harian, total akan di-handle oleh afterStateHydrated harga_paket_info
                                            return;
                                        }
                                        
                                        // LOGIKA PINTAR:
                                        // Cek apakah member ini sudah pernah perpanjangan (berarti sudah pernah expired)
                                        $sudahPernahPerpanjangan = \App\Models\Transaction::where('member_id', $record->id)
                                            ->where(function($query) {
                                                $query->where('type', 'like', 'Perpanjangan:%')
                                                      ->orWhere('type', 'like', 'Perpanjang Member:%');
                                            })
                                            ->exists();
                                        
                                        // 1. Jika member AKTIF dan BELUM pernah perpanjangan → Tampilkan fee sebagai referensi (member baru pertama kali aktif)
                                        // 2. Jika member AKTIF dan SUDAH pernah perpanjangan → Fee = 0 (member lama, tidak perlu referensi lagi)
                                        // 3. Jika member EXPIRED → Fee = 0 (perpanjangan tidak kena fee)
                                        // 4. Jika member PENDAFTAR BARU → Tampilkan fee
                                        
                                        if ($record->is_active && !$sudahPernahPerpanjangan) {
                                            // Biaya admin selalu dari database (tidak pernah di-override)
                                            $set('biaya_registrasi_info', $registrationFee);
                                        } elseif ($record->is_active && $sudahPernahPerpanjangan) {
                                            // Sudah pernah perpanjangan: fee = 0
                                            $set('biaya_registrasi_info', 0);
                                        } elseif ($record->expiry_date) {
                                            // Member expired (perpanjangan): fee = 0
                                            $set('biaya_registrasi_info', 0);
                                        } else {
                                            // Pendaftar baru: tampilkan fee
                                            $set('biaya_registrasi_info', $registrationFee);
                                        }
                                    }
                                })
                                ->helperText(function ($record, $get) {
                                    if (!$record) {
                                        // Untuk create member baru
                                        $paketName = $get('type');
                                        if ($paketName) {
                                            $paket = Paket::where('nama_paket', $paketName)->first();
                                            if ($paket && $paket->durasi_hari < 30) {
                                                return 'Tamu harian tidak dikenakan biaya admin';
                                            }
                                        }
                                        return 'Hanya untuk pendaftar baru';
                                    }
                                    
                                    // Cek apakah paket yang dipilih adalah paket harian
                                    $paketName = $get('type') ?? $record->type;
                                    if ($paketName) {
                                        $paket = Paket::where('nama_paket', $paketName)->first();
                                        if ($paket && $paket->durasi_hari < 30) {
                                            return 'Tamu harian tidak dikenakan biaya admin';
                                        }
                                    }
                                    
                                    // Cek apakah member sudah pernah perpanjangan
                                    $sudahPernahPerpanjangan = \App\Models\Transaction::where('member_id', $record->id)
                                        ->where(function($query) {
                                            $query->where('type', 'like', 'Perpanjangan:%')
                                                  ->orWhere('type', 'like', 'Perpanjang Member:%');
                                        })
                                        ->exists();
                                    
                                    if ($record->is_active && !$sudahPernahPerpanjangan) {
                                        return null;
                                    } elseif ($record->is_active && $sudahPernahPerpanjangan) {
                                        return 'Member lama tidak dikenakan biaya admin';
                                    } elseif ($record->expiry_date) {
                                        return 'Perpanjangan membership bebas biaya admin';
                                    }
                                    
                                    return 'Hanya untuk pendaftar baru';
                                })
                                ->extraInputAttributes(['style' => 'font-weight: 700; color: #ea580c; background-color: #fff7ed;']),

                            Forms\Components\TextInput::make('harga_paket_info')
                                ->label('TOTAL TAGIHAN')
                                ->placeholder('')
                                ->numeric()
                                ->prefix('Rp')
                                ->reactive()
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateUpdated(function ($state, $set) {
                                    $set('total_tagihan_hidden', $state);
                                }) // Kirim ke backend hanya saat belum aktif
                                ->afterStateHydrated(function ($set, $get, $record) {
                                    if ($record && $record->type) {
                                        $paket = Paket::where('nama_paket', $record->type)->first();
                                        $harga = $paket ? (int)$paket->harga : 0;
                                        $registrationFee = $paket ? (int)$paket->registration_fee : 0;
                                        
                                        if (!$record->is_active && $record->expiry_date) {
                                            $set('harga_paket_info', 0);
                                            $set('total_tagihan_hidden', 0);
                                            return;
                                        }

                                        if ($record->is_active) {
                                            // Ambil dari transaksi terakhir (pendaftaran atau perpanjangan)
                                            $transaksi = \App\Models\Transaction::where('member_id', $record->id)
                                                ->where(function($query) {
                                                    $query->where('type', 'like', 'Pendaftaran Baru%')
                                                          ->orWhere('type', 'like', 'Perpanjangan%')
                                                          ->orWhere('type', 'like', 'Perpanjang Member%');
                                                })
                                                ->latest('payment_date')
                                                ->first();
                                            
                                            $totalTagihan = $transaksi ? (int)$transaksi->amount : ($harga + $registrationFee);
                                        } elseif ($paket && $paket->durasi_hari < 30) {
                                            // Paket harian belum aktif: pakai harga paket saja
                                            $totalTagihan = $harga;
                                        } else {
                                            // Pendaftar baru belum aktif: harga + fee sebagai estimasi
                                            $totalTagihan = $harga + $registrationFee;
                                        }
                                        
                                        $set('harga_paket_info', $totalTagihan);
                                        $set('total_tagihan_hidden', $totalTagihan);
                                    }
                                })
                                ->helperText(function ($record) {
                                    if (!$record) return null;
                                    if ($record->is_active) return 'Total yang sudah dibayar';
                                    if ($record->expiry_date) return 'Total untuk perpanjangan';
                                    return null;
                                })
                                ->extraInputAttributes(['style' => 'font-weight: 900; color: #000000; font-size: 1.5rem; background-color: #fef3c7;']),
                        ]),

                        Forms\Components\Hidden::make('total_tagihan_hidden')
                            ->dehydrated(true),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->reactive()
                            ->helperText(function ($record) {
                                if (!$record) return '**Nyalakan hanya jika member sudah membayar lunas.**';
                                
                                // Jika member sedang aktif dan sudah pernah punya transaksi
                                if ($record->is_active) {
                                    $sudahPernahAktif = \App\Models\Transaction::where('member_id', $record->id)
                                        ->where('type', 'like', 'Pendaftaran Baru%')
                                        ->exists();
                                    
                                    if ($sudahPernahAktif) {
                                        return null; // Hilangkan helper text untuk member aktif
                                    }
                                }
                                
                                // Jika member expired (tidak aktif tapi punya expiry_date)
                                if (!$record->is_active && $record->expiry_date) {
                                    return 'Member expired. Nyalakan untuk perpanjangan membership.';
                                }
                                
                                return '**Nyalakan hanya jika member sudah membayar lunas.**';
                            })
                            ->disabled(function ($record) {
                                if (!$record) return false;
                                
                                // Hanya disable jika member SEDANG AKTIF dan sudah pernah punya transaksi
                                // Jika member expired (is_active = false), toggle TIDAK disabled agar bisa diperpanjang
                                if ($record->is_active) {
                                    $sudahPernahAktif = \App\Models\Transaction::where('member_id', $record->id)
                                        ->where('type', 'like', 'Pendaftaran Baru%')
                                        ->exists();
                                    
                                    return $sudahPernahAktif;
                                }
                                
                                return false; // Jika tidak aktif, toggle bisa dinyalakan
                            })
                            ->default(false),
                    ]),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama ⇅')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // Cek apakah input adalah nomor telepon
                        $normalizedSearch = preg_replace('/[^0-9]/', '', $search);
                        $isPhoneSearch = !empty($normalizedSearch) && strlen($normalizedSearch) >= 8 && strlen($normalizedSearch) <= 15;
                        
                        if ($isPhoneSearch) {
                            // Phone search - exact match untuk nomor telepon
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
                            return $query->where(function ($phoneQuery) use ($searchPatterns) {
                                foreach ($searchPatterns as $pattern) {
                                    $phoneQuery->orWhereRaw(
                                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') = ?", 
                                        [$pattern]
                                    );
                                }
                            });
                        }
                        
                        // Cek apakah search dimulai dengan * untuk partial match
                        $isPartialSearch = str_starts_with($search, '*');
                        
                        if ($isPartialSearch) {
                            // Partial match - hilangkan tanda *
                            $partialSearch = ltrim($search, '*');
                            return $query->where('name', 'like', "%{$partialSearch}%");
                        }
                        
                        // Cek apakah search dimulai dengan = untuk exact match
                        $isExactSearch = str_starts_with($search, '=');
                        
                        if ($isExactSearch) {
                            // Exact match - hilangkan tanda =
                            $exactSearch = ltrim($search, '=');
                            return $query->where('name', '=', $exactSearch);
                        }
                        
                        // Default: Smart search - coba exact dulu, kalau tidak ada hasil coba partial
                        // Untuk nama dengan spasi, gunakan case-insensitive exact match
                        return $query->whereRaw('LOWER(name) = LOWER(?)', [$search])
                            ->orWhere(function ($subQuery) use ($search) {
                                // Jika exact match tidak ada hasil, coba partial match
                                $words = explode(' ', $search);
                                foreach ($words as $word) {
                                    if (strlen(trim($word)) >= 2) {
                                        $subQuery->where('name', 'like', "%{$word}%");
                                    }
                                }
                            });
                    })
                    ->sortable()
                    ->weight('bold')
                    // ->description(fn (Member $record): string => $record->trashed() ? 'DATA DIHAPUS' : '') // Hapus karena soft delete dihapus
                    // ->color(fn (Member $record): string => $record->trashed() ? 'danger' : 'default') // Hapus karena soft delete dihapus
                    ->toggleable(isToggledHiddenByDefault: false),
                
                // Tables\Columns\TextColumn::make('nik')
                //     ->label('NIK KTP')
                //     ->searchable()
                //     ->default('-')
                //     ->toggleable(isToggledHiddenByDefault: true),
                
                // Tables\Columns\TextColumn::make('fingerprint_id')
                //     ->label('Fingerprint ID ⇅')
                //     ->searchable()
                //     ->sortable()
                //     ->default('-')
                //     ->color('primary')
                //     ->weight('medium')
                //     ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: false),
                
                Tables\Columns\TextColumn::make('join_date')
                    ->label('Masuk ⇅')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y') : '-')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Berakhir ⇅')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('d/m/Y') : '-')
                    ->color(function ($record) {
                        if (!$record->expiry_date) return null;
                        return Carbon::parse($record->expiry_date)->startOfDay()->isPast() && !Carbon::parse($record->expiry_date)->isToday() ? 'danger' : 'success';
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('phone')
                    ->label('WA')
                    ->icon('heroicon-o-chat-alt')
                    ->color('success')
                    ->formatStateUsing(function ($state) {
                        $nomor = preg_replace('/[^0-9]/', '', $state);
                        
                        // Format display: tambahkan 0 di depan jika perlu
                        if (str_starts_with($nomor, '8')) {
                            return '0' . $nomor;
                        }
                        elseif (str_starts_with($nomor, '62')) {
                            return '0' . substr($nomor, 2);
                        }
                        
                        return $nomor;
                    })
                    ->url(function ($record) {
                        $nomor = preg_replace('/[^0-9]/', '', $record->phone);
                        
                        // Jika nomor dimulai dengan 8 (tanpa 0), tambahkan 62
                        if (str_starts_with($nomor, '8')) {
                            $nomor = '62' . $nomor;
                        }
                        // Jika nomor dimulai dengan 0, ganti dengan 62
                        elseif (str_starts_with($nomor, '0')) {
                            $nomor = '62' . substr($nomor, 1);
                        }
                        // Jika sudah dimulai dengan 62, biarkan
                        elseif (!str_starts_with($nomor, '62')) {
                            $nomor = '62' . $nomor;
                        }
                        
                        return "https://wa.me/{$nomor}";
                    })
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: false),

                // Tables\Columns\ViewColumn::make('signature_preview')
                //     ->label('TTD Digital')
                //     ->view('filament.tables.columns.signature-preview')
                //     ->toggleable(isToggledHiddenByDefault: true),

                // Tables\Columns\TextColumn::make('signature_timestamp')
                //     ->label('Waktu TTD')
                //     ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->setTimezone('Asia/Makassar')->format('d/m/Y H:i') : '-')
                //     ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('kartu_member')
                    ->label('Kartu')
                    ->getStateUsing(fn () => 'Lihat Kartu')
                    ->url(fn ($record) => route('member.card.download', $record->id))
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                // --- KOLOM STATUS YANG SUDAH DIPERBARUI LOGIKANYA ---
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $today = Carbon::now('Asia/Makassar')->startOfDay();
                        
                        // 1. Jika BELUM BAYAR (is_active mati & tgl expired kosong)
                        if (!$record->is_active && !$record->expiry_date) {
                            return 'Pendaftar Baru';
                        }

                        // 2. Jika SUDAH EXPIRED (is_active mati & tgl expired sudah lewat)
                        if (!$record->is_active && $record->expiry_date) {
                            $expiry = Carbon::parse($record->expiry_date)->startOfDay();
                            if ($today->gt($expiry)) {
                                return 'Masa Aktif Habis'; 
                            }
                        }

                        // 3. Jika AKTIF (is_active menyala)
                        if ($record->is_active) {
                            return 'Aktif';
                        }

                        return 'Non-Aktif';
                    })
                    ->colors([
                        'warning' => 'Pendaftar Baru',   // Warna Kuning
                        'danger' => 'Masa Aktif Habis',  // Warna Merah
                        'success' => 'Aktif',            // Warna Hijau
                        'secondary' => 'Non-Aktif',      // Warna Abu-abu
                    ])
                    ->icons([
                        'heroicon-o-user-add' => 'Pendaftar Baru',
                        'heroicon-o-clock' => 'Masa Aktif Habis',
                        'heroicon-o-check-circle' => 'Aktif',
                        'heroicon-o-minus-circle' => 'Non-Aktif',
                    ])
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Filter 1: Status Member
                Tables\Filters\Filter::make('status_member')
                    ->label('Status Member')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'aktif' => 'Aktif',
                                'expired' => 'Masa Aktif Habis',
                                'pendaftar_baru' => 'Pendaftar Baru',
                                'non_aktif' => 'Non-Aktif',
                            ])
                            ->placeholder('Semua Status'),
                    ])
                    ->query(function ($query, array $data) {
                        $hariIni = Carbon::now('Asia/Makassar')->startOfDay();
                        return $query->when($data['status'], function ($query, $status) use ($hariIni) {
                            if ($status === 'aktif') {
                                return $query->where('is_active', true);
                            }
                            if ($status === 'expired') {
                                return $query->where('is_active', false)
                                    ->whereDate('expiry_date', '<', $hariIni);
                            }
                            if ($status === 'pendaftar_baru') {
                                return $query->where('is_active', false)
                                    ->whereNull('expiry_date');
                            }
                            if ($status === 'non_aktif') {
                                return $query->where('is_active', false)
                                    ->whereDate('expiry_date', '>=', $hariIni);
                            }
                        });
                    }),

                // Filter 2: Tipe Paket
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Paket')
                    ->options(function () {
                        return \App\Models\Paket::where('is_active', true)
                            ->pluck('nama_paket', 'nama_paket')
                            ->toArray();
                    })
                    ->placeholder('Semua Paket'),

                // Filter 3: Berakhir Bulan Ini
                Tables\Filters\Filter::make('expiry_this_month')
                    ->label('Berakhir Bulan Ini')
                    ->query(fn ($query) => $query->whereMonth('expiry_date', Carbon::now()->month)
                        ->whereYear('expiry_date', Carbon::now()->year))
                    ->toggle(),

                // Filter 4: Berakhir Hari Ini
                Tables\Filters\Filter::make('expiry_today')
                    ->label('Berakhir Hari Ini')
                    ->query(fn ($query) => $query->whereDate('expiry_date', Carbon::now('Asia/Makassar')->toDateString()))
                    ->toggle(),



                // Filter 5: Data yang dihapus - Tidak perlu lagi karena soft delete dihapus
                // TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('sort_options')
                    ->label('Urutkan')
                    ->icon('heroicon-o-sort-ascending')
                    ->color('primary')
                    ->button()
                    ->form([
                        Forms\Components\Select::make('sort')
                            ->label('Urutkan Berdasarkan')
                            ->options([
                                'name-asc' => 'Nama (A-Z)',
                                'name-desc' => 'Nama (Z-A)',
                                'join_date-asc' => 'Tanggal Masuk (Terlama)',
                                'join_date-desc' => 'Tanggal Masuk (Terbaru)',
                                'expiry_date-asc' => 'Tanggal Berakhir (Terlama)',
                                'expiry_date-desc' => 'Tanggal Berakhir (Terbaru)',
                                'created_at-desc' => 'Default (Terbaru Daftar)',
                            ])
                            ->default('created_at-desc')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $sort = explode('-', $data['sort']);
                        $url = url()->current() . '?tableSort=' . $sort[0] . '&tableSortDirection=' . $sort[1];
                        return redirect($url);
                    }),
                    
                Tables\Actions\Action::make('reset_sort')
                    ->label('Reset Urutan')
                    ->icon('heroicon-o-refresh')
                    ->color('secondary')
                    ->button()
                    ->action(function () {
                        return redirect(url()->current());
                    })
                    ->visible(fn () => request()->has('tableSort') || request()->has('tableSortDirection')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(function ($record) {
                        // 1. Pendaftar Baru (belum pernah aktif)
                        if (!$record->is_active && !$record->expiry_date) {
                            return 'Aktivasi Sekarang';
                        }
                        
                        // 2. Masa Aktif Habis (expired)
                        if (!$record->is_active && $record->expiry_date) {
                            $today = Carbon::now('Asia/Makassar')->startOfDay();
                            $expiry = Carbon::parse($record->expiry_date)->startOfDay();
                            if ($today->gt($expiry)) {
                                return 'Perpanjang';
                            }
                        }
                        
                        // 3. Aktif atau status lainnya
                        return 'Ubah';
                    })
                    ->icon(function ($record) {
                        // 1. Pendaftar Baru
                        if (!$record->is_active && !$record->expiry_date) {
                            return 'heroicon-o-check-circle';
                        }
                        
                        // 2. Masa Aktif Habis
                        if (!$record->is_active && $record->expiry_date) {
                            $today = Carbon::now('Asia/Makassar')->startOfDay();
                            $expiry = Carbon::parse($record->expiry_date)->startOfDay();
                            if ($today->gt($expiry)) {
                                return 'heroicon-o-refresh';
                            }
                        }
                        
                        // 3. Aktif atau status lainnya
                        return 'heroicon-o-pencil';
                    })
                    ->color(function ($record) {
                        // 1. Pendaftar Baru - Hijau
                        if (!$record->is_active && !$record->expiry_date) {
                            return 'success';
                        }
                        
                        // 2. Masa Aktif Habis - Hijau
                        if (!$record->is_active && $record->expiry_date) {
                            $today = Carbon::now('Asia/Makassar')->startOfDay();
                            $expiry = Carbon::parse($record->expiry_date)->startOfDay();
                            if ($today->gt($expiry)) {
                                return 'success';
                            }
                        }
                        
                        // 3. Aktif atau status lainnya - Biru (default)
                        return 'primary';
                    }),
                // RestoreAction::make(), // Tidak perlu lagi karena soft delete dihapus
                // ForceDeleteAction::make(), // DIHAPUS: Terlalu berbahaya untuk data keuangan
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
                    ->url(fn () => route('export-members', ['format' => 'excel']))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('print_pdf')
                    ->label('Cetak PDF')
                    ->color('warning')
                    ->icon('heroicon-o-printer')
                    ->url(fn () => route('export-members', ['format' => 'pdf']))
                    ->openUrlInNewTab(),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }    
}