<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class Member extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'signature_timestamp' => 'datetime',
    ];

    /**
     * Generate card token jika belum ada (accessor untuk backward compatibility)
     */
    public function getCardTokenAttribute($value)
    {
        // Jika token kosong, generate dan save
        if (empty($value)) {
            $token = \Illuminate\Support\Str::random(32);
            $this->attributes['card_token'] = $token;
            $this->saveQuietly(); // Save tanpa trigger events
            return $token;
        }
        
        return $value;
    }
    /**
     * Logika Perpanjangan Dinamis (Renewal via Tombol Khusus)
     */
    public function perpanjangSatuBulan()
    {
        $paket = \App\Models\Paket::where('nama_paket', $this->type)->first();
        
        if (!$paket) {
            Notification::make()->title('Paket Tidak Ditemukan')->danger()->send();
            return;
        }

        $durasi = $paket->durasi_hari;
        $harga  = $paket->harga;

        $tanggalLama = $this->expiry_date ? Carbon::parse($this->expiry_date) : now();
        
        if ($durasi >= 30) {
            // Paket bulanan: hitung bulan dari durasi_hari
            $bulan = round($durasi / 30);
            $tanggalBaru = $tanggalLama->isPast() ? now()->addMonths($bulan) : $tanggalLama->addMonths($bulan);
        } else {
            // Paket harian: expired di hari yang sama untuk durasi = 1
            if ($durasi == 1) {
                // Member harian expired di hari yang sama
                $tanggalBaru = $tanggalLama->isPast() ? now() : $tanggalLama->addDays(1);
            } else {
                // Paket beberapa hari (misal 3 hari, 7 hari)
                $tanggalBaru = $tanggalLama->isPast() ? now()->addDays($durasi - 1) : $tanggalLama->addDays($durasi);
            }
        }

        // PERBAIKAN ATOMIK: GUNAKAN DATABASE TRANSACTION
        try {
            \DB::beginTransaction();
            
            // 1️⃣ CEK DUPLIKAT DALAM TRANSACTION (DENGAN LOCK)
            $now = Carbon::now('Asia/Makassar');
            $existingTransaction = \App\Models\Transaction::where('member_id', $this->id)
                ->where('type', 'like', 'Perpanjangan:%')
                ->whereDate('payment_date', $now->format('Y-m-d'))
                ->lockForUpdate() // Prevent race condition
                ->exists();
            
            if ($existingTransaction) {
                \DB::rollback();
                Notification::make()
                    ->title('Transaksi Sudah Ada')
                    ->body('Transaksi perpanjangan sudah ada untuk hari ini.')
                    ->warning()
                    ->send();
                return;
            }
            
            // 2️⃣ UPDATE MEMBER DATA
            $this->update([
                'expiry_date' => $tanggalBaru->format('Y-m-d'),
                'is_active' => true,
            ]);

            // 3️⃣ CATAT TRANSAKSI
            $transaction = \App\Models\Transaction::create([
                'member_id'      => $this->id,
                'order_id'       => 'RNW-' . strtoupper(uniqid()), 
                'amount'         => $harga, 
                'type'           => 'Perpanjangan: ' . $this->type,
                'payment_method' => 'QRIS',
                'payment_date'   => $now,
                'guest_name'     => $this->name,
            ]);
            
            // 4️⃣ CATAT KE CASH FLOW
            \App\Models\CashFlow::createEntry(
                'income',
                'member',
                'Perpanjangan - ' . $this->name . ' (' . $this->type . ')',
                $harga,
                $transaction->id,
                $now
            );
            
            // Commit jika semua berhasil
            \DB::commit();
            
            \Log::info('Perpanjangan via model berhasil:', [
                'member_id' => $this->id,
                'member_name' => $this->name,
                'old_expiry' => $tanggalLama->format('Y-m-d'),
                'new_expiry' => $tanggalBaru->format('Y-m-d'),
                'transaction_id' => $transaction->id,
                'amount' => $harga
            ]);
            
        } catch (\Exception $e) {
            // Rollback jika ada yang gagal
            \DB::rollback();
            
            Notification::make()
                ->title('Gagal Memproses Perpanjangan')
                ->body('Terjadi kesalahan saat menyimpan data. Error: ' . $e->getMessage())
                ->danger()
                ->send();
            
            \Log::error('Error saat perpanjangan via model: ' . $e->getMessage());
            return;
        }
    }

    protected static function boot()
    {
        parent::boot();

        // Auto-generate card_token saat member dibuat
        static::creating(function ($member) {
            if (empty($member->card_token)) {
                $member->card_token = \Illuminate\Support\Str::random(32);
            }
        });

        // OBSERVER DINONAKTIFKAN - Transaksi sekarang dihandle di CreateMember.php dan EditMember.php
        // untuk menghindari duplikasi dan memastikan konsistensi data
        
        /*
        static::updating(function ($model) {
            // OBSERVER INI SUDAH TIDAK DIGUNAKAN LAGI
            // Pembuatan transaksi sekarang dilakukan di:
            // - CreateMember.php (untuk member baru)
            // - EditMember.php (untuk aktivasi dan perpanjangan)
            // 
            // Alasan dinonaktifkan:
            // 1. Mencegah double transaction
            // 2. Memastikan konsistensi payment_method dari form
            // 3. CreateMember dan EditMember sudah menggunakan withoutEvents()
        });
        */
    }
}