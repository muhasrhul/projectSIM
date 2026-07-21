<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramHelper
{
    /**
     * Format nomor HP menjadi link WhatsApp untuk Telegram
     * 
     * @param string $phone Nomor HP (format: 08xxx atau 628xxx)
     * @return string Link WhatsApp yang bisa diklik di Telegram
     */
    private static function formatPhoneLink($phone)
    {
        // Hapus semua karakter non-digit
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Jika dimulai dengan 0, ganti dengan 62
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        // Jika tidak dimulai dengan 62, tambahkan 62
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        // Return format link Telegram dengan Markdown
        return "[{$phone}](https://wa.me/{$phone})";
    }
    
    /**
     * Kirim pesan ke Telegram
     * 
     * @param string $message Pesan yang akan dikirim (support Markdown)
     * @return bool Success status
     */
    public static function send($message)
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        
        // Jika tidak ada konfigurasi Telegram, skip
        if (!$botToken || !$chatId) {
            Log::warning('[Telegram] Bot token atau chat ID belum diset di config');
            return false;
        }
        
        // Potong pesan jika melebihi batas Telegram (4096 karakter)
        if (strlen($message) > 4096) {
            $message = substr($message, 0, 4090) . "\n...";
        }

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                $response = Http::timeout(10)
                    ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => $message,
                        'parse_mode' => 'Markdown',
                    ]);
                
                if ($response->successful()) {
                    Log::info('[Telegram] Notifikasi berhasil dikirim', [
                        'chat_id' => $chatId,
                        'message_preview' => substr($message, 0, 100),
                        'attempt' => $attempt,
                    ]);
                    return true;
                } else {
                    Log::warning('[Telegram] Gagal kirim notifikasi (attempt ' . $attempt . ')', [
                        'response' => $response->body()
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('[Telegram] Error kirim notifikasi (attempt ' . $attempt . ')', [
                    'error' => $e->getMessage()
                ]);
            }

            if ($attempt < $maxRetries) {
                sleep(pow(2, $attempt - 1)); // Exponential backoff: 1s, 2s, 4s
            }
        }

        Log::error('[Telegram] Gagal kirim notifikasi setelah ' . $maxRetries . ' percobaan');
        return false;
    }
    
    /**
     * Format pesan transaksi kasir cepat
     */
    public static function sendTransaksiKasir($quickTransaction)
    {
        $message = "⚡ *KASIR CEPAT - ARIFAH GYM*\n\n";
        $message .= "Tanggal: " . \Carbon\Carbon::parse($quickTransaction->payment_date)->format('d M Y H:i') . "\n\n";
        
        // Customer info
        $message .= "👤 *Customer:* {$quickTransaction->guest_name}\n";
        
        // Transaction details
        $message .= "📦 *Produk:* {$quickTransaction->product_name}\n";
        $message .= "💵 *Harga:* Rp " . number_format($quickTransaction->amount, 0, ',', '.') . "\n";
        $message .= "💳 *Metode:* {$quickTransaction->payment_method}\n";
        $message .= "✅ *Status:* Lunas\n\n";
        
        $message .= "Terima kasih!\n\n";
        $message .= "ARIFAH Gym System";
        
        return self::send($message);
    }
    
    /**
     * Format pesan pendaftaran member baru (dari web)
     */
    public static function sendPendaftaranBaru($member, $paket)
    {
        $phoneLink = self::formatPhoneLink($member->phone);
        
        $message = "📋 *PENDAFTARAN MEMBER BARU*\n";
        $message .= "├─ Nama    : {$member->name}\n";
        $message .= "├─ HP      : {$phoneLink}\n";
        $message .= "├─ Email   : {$member->email}\n";
        $message .= "├─ Paket   : {$paket}\n";
        $message .= "└─ Waktu   : " . \Carbon\Carbon::now('Asia/Makassar')->format('d M Y H:i') . "\n\n";
        $message .= "⚠️ STATUS: MENUNGGU AKTIVASI\n\n";
        $message .= "💡 ACTION: Aktivasi di panel admin";
        
        return self::send($message);
    }
    
    /**
     * Format pesan aktivasi member & transaksi
     */
    public static function sendAktivasiMember($member, $transaction)
    {
        $phoneLink = self::formatPhoneLink($member->phone);
        
        // Log untuk debug
        \Illuminate\Support\Facades\Log::info('[Telegram] Data member untuk notifikasi', [
            'member_id' => $member->id,
            'name' => $member->name,
            'fingerprint_id' => $member->fingerprint_id,
            'fingerprint_id_type' => gettype($member->fingerprint_id),
            'all_attributes' => $member->getAttributes()
        ]);
        
        $message = "✅ *AKTIVASI MEMBER*\n\n";
        
        // BAGIAN 1: DATA MEMBER
        $message .= "DATA MEMBER\n";
        $message .= "├─ Nama        : {$member->name}\n";
        $message .= "├─ HP          : {$phoneLink}\n";
        
        // Tambahkan Fingerprint ID
        if (!empty($member->fingerprint_id)) {
            $message .= "└─ Fingerprint : {$member->fingerprint_id}\n\n";
        } else {
            $message .= "└─ Fingerprint : -\n\n";
        }
        
        // BAGIAN 2: PAKET & PEMBAYARAN
        $message .= "PAKET & PEMBAYARAN\n";
        $message .= "├─ Paket   : {$member->type}\n";
        $message .= "├─ Total   : Rp " . number_format($transaction->amount, 0, ',', '.') . "\n";
        $message .= "└─ Metode  : {$transaction->payment_method}\n\n";
        
        // BAGIAN 3: MASA AKTIF
        $message .= "MASA AKTIF\n";
        $message .= "├─ Aktif s/d : " . \Carbon\Carbon::parse($member->expiry_date)->format('d M Y') . "\n";
        $message .= "└─ Waktu     : " . now()->format('d M Y, H:i') . " WITA\n\n";
        
        $message .= "🎉 Member aktif dan siap latihan!";
        
        return self::send($message);
    }
    
    /**
     * Format pesan perpanjangan member
     */
    public static function sendPerpanjanganMember($member, $transaction)
    {
        $phoneLink = self::formatPhoneLink($member->phone);
        
        // Log untuk debug
        \Illuminate\Support\Facades\Log::info('[Telegram] Data member untuk notifikasi perpanjangan', [
            'member_id' => $member->id,
            'name' => $member->name,
            'fingerprint_id' => $member->fingerprint_id,
        ]);
        
        $message = "🔄 *PERPANJANGAN MEMBERSHIP*\n\n";
        
        // BAGIAN 1: DATA MEMBER
        $message .= "DATA MEMBER\n";
        $message .= "├─ Nama        : {$member->name}\n";
        $message .= "├─ HP          : {$phoneLink}\n";
        
        // Tambahkan Fingerprint ID
        if (!empty($member->fingerprint_id)) {
            $message .= "└─ Fingerprint : {$member->fingerprint_id}\n\n";
        } else {
            $message .= "└─ Fingerprint : -\n\n";
        }
        
        // BAGIAN 2: PAKET & PEMBAYARAN
        $message .= "PAKET & PEMBAYARAN\n";
        $message .= "├─ Paket   : {$member->type}\n";
        $message .= "├─ Total   : Rp " . number_format($transaction->amount, 0, ',', '.') . "\n";
        $message .= "└─ Metode  : {$transaction->payment_method}\n\n";
        
        // BAGIAN 3: MASA AKTIF
        $message .= "MASA AKTIF\n";
        $message .= "├─ Aktif s/d : " . \Carbon\Carbon::parse($member->expiry_date)->format('d M Y') . "\n";
        $message .= "└─ Waktu     : " . now()->format('d M Y, H:i') . " WITA\n\n";
        
        $message .= "🎉 Perpanjangan berhasil!";
        
        return self::send($message);
    }

    /**
     * Format pesan perpanjangan EARLY (H-2 sampai H-1)
     */
    public static function sendPerpanjanganEarly($member, $transaction)
    {
        $phoneLink = self::formatPhoneLink($member->phone);
        
        // Log untuk debug
        \Illuminate\Support\Facades\Log::info('[Telegram] Data member untuk notifikasi perpanjangan early', [
            'member_id' => $member->id,
            'name' => $member->name,
            'fingerprint_id' => $member->fingerprint_id,
        ]);
        
        $message = "⚡ *PERPANJANGAN EARLY*\n\n";
        
        // BAGIAN 1: DATA MEMBER
        $message .= "DATA MEMBER\n";
        $message .= "├─ Nama        : {$member->name}\n";
        $message .= "├─ HP          : {$phoneLink}\n";
        
        // Tambahkan Fingerprint ID
        if (!empty($member->fingerprint_id)) {
            $message .= "└─ Fingerprint : {$member->fingerprint_id}\n\n";
        } else {
            $message .= "└─ Fingerprint : -\n\n";
        }
        
        // BAGIAN 2: PAKET & PEMBAYARAN
        $message .= "PAKET & PEMBAYARAN\n";
        $message .= "├─ Paket   : {$member->type}\n";
        $message .= "├─ Total   : Rp " . number_format($transaction->amount, 0, ',', '.') . "\n";
        $message .= "└─ Metode  : {$transaction->payment_method}\n\n";
        
        // BAGIAN 3: MASA AKTIF
        $message .= "MASA AKTIF\n";
        $message .= "├─ Aktif s/d : " . \Carbon\Carbon::parse($member->expiry_date)->format('d M Y') . "\n";
        $message .= "└─ Waktu     : " . now()->format('d M Y, H:i') . " WITA\n\n";
        
        $message .= "🎉 Perpanjangan early berhasil!\n";
        $message .= "💡 Member tidak kehilangan sisa waktu membership";
        
        return self::send($message);
    }

    /**
     * Format pesan laporan H-1 expired ke owner
     */
    public static function sendReminderReportToOwner($membersH1)
    {
        $message = "🚨 *LAPORAN H-1 EXPIRED*\n\n";
        
        if ($membersH1->count() > 0) {
            // Ambil tanggal expired (besok) dari member pertama
            $expiredDate = \Carbon\Carbon::parse($membersH1->first()->expiry_date)->format('d M Y');
            
            // BAGIAN DAFTAR MEMBER
            $message .= "DAFTAR MEMBER YANG AKAN EXPIRED\n";
            $message .= "├─ Pada Tanggal : *{$expiredDate}*\n";
            $message .= "└─ Total Member : *{$membersH1->count()} member*\n\n";
            
            foreach ($membersH1 as $member) {
                $phoneLink = self::formatPhoneLink($member->phone);
                $memberExpiryDate = \Carbon\Carbon::parse($member->expiry_date)->format('d M Y');
                $message .= "• *{$member->name}*\n";
                $message .= "  Paket: {$member->type}\n";
                $message .= "  Expired: {$memberExpiryDate}\n";
                $message .= "  HP: {$phoneLink}\n\n";
            }

            $message .= "💡 ACTION: Hubungi member untuk perpanjangan";
        } else {
            // BAGIAN DAFTAR MEMBER (tidak ada member)
            $tomorrowDate = \Carbon\Carbon::now('Asia/Makassar')->addDay()->format('d M Y');
            
            $message .= "DAFTAR MEMBER YANG AKAN EXPIRED\n";
            $message .= "├─ Pada Tanggal : *{$tomorrowDate}*\n";
            $message .= "└─ Total Member : *0 member*\n\n";
            
            $message .= "✅ TIDAK ADA MEMBER YANG AKAN EXPIRED BESOK\n\n";
            $message .= "💡 Semua member masih aman!";
        }

        return self::send($message);
    }

    /**
     * Format pesan laporan pembukuan harian ke owner
     */
    public static function sendDailyCashFlowReport($date, $cashFlows, $totalIncome, $totalExpense, $netBalance)
    {
        // Format tanggal Indonesia
        $tanggal = $date->format('d M Y');
        $tanggalUrl = $date->format('Y-m-d'); // Format untuk URL
        
        // Buat pesan laporan
        $message = "📊 *LAPORAN PEMBUKUAN HARIAN*\n";
        $message .= "🗓️ Tanggal: *{$tanggal}*\n\n";
        
        // RINGKASAN KEUANGAN
        $message .= "💰 *RINGKASAN KEUANGAN*\n";
        $message .= "├─ Pemasukan : Rp " . number_format($totalIncome, 0, ',', '.') . "\n";
        $message .= "├─ Pengeluaran: Rp " . number_format($totalExpense, 0, ',', '.') . "\n";
        $message .= "└─ Saldo Bersih: Rp " . number_format($netBalance, 0, ',', '.') . "\n\n";
        
        // LINK EXPORT PDF dengan tanggal spesifik (protected dengan auth)
        $exportUrl = "https://arifahgym.cloud/export/pembukuan?period=single&date={$tanggalUrl}";
        $message .= "📄 *EXPORT LAPORAN PDF*\n";
        $message .= "Klik link berikut untuk download:\n";
        $message .= "{$exportUrl}\n\n";
        
        $message .= "ARIFAH Gym System";
        
        return self::send($message);
    }

    /**
     * Format pesan notifikasi absen member ke owner
     */
    public static function sendAbsenNotification($member, $totalLatihan, $badge)
    {
        $phoneLink = self::formatPhoneLink($member->phone);
        $now = \Carbon\Carbon::now('Asia/Makassar');
        $jamAbsen = $now->format('H:i');
        $tanggalAbsen = $now->format('d M Y');
        
        // Format pesan dengan 3 bagian
        $message = "🏋️ *ABSEN MEMBER*\n\n";
        
        // BAGIAN 1: DATA MEMBER
        $message .= "DATA MEMBER\n";
        $message .= "├─ Nama     : {$member->name}\n";
        $message .= "├─ WhatsApp : {$phoneLink}\n";
        $message .= "├─ Jam      : {$jamAbsen} WITA\n";
        $message .= "└─ Tanggal  : {$tanggalAbsen}\n\n";
        
        // BAGIAN 2: PAKET
        $message .= "PAKET\n";
        $message .= "├─ Tipe         : {$member->type}\n";
        $message .= "├─ Total Latihan: {$totalLatihan}x\n";
        $message .= "└─ Badge        : {$badge}\n\n";
        
        // BAGIAN 3: MASA AKTIF
        $message .= "MASA AKTIF\n";
        if ($member->expiry_date) {
            $expiredDate = \Carbon\Carbon::parse($member->expiry_date);
            $sisaHari = $expiredDate->diffInDays($now);
            
            if ($expiredDate->isFuture()) {
                $message .= "├─ Sisa Waktu : {$sisaHari} hari lagi\n";
                $message .= "└─ Expired    : " . $expiredDate->format('d M Y');
            } else {
                $message .= "└─ Status     : EXPIRED";
            }
        } else {
            $message .= "└─ Status     : Member Harian";
        }
        
        return self::send($message);
    }

    /**
     * Format pesan notifikasi hutang belum lunas ke owner
     */
    public static function sendUnpaidDebtReminder($unpaidDebts)
    {
        $now = \Carbon\Carbon::now('Asia/Makassar');
        $tanggal = $now->format('d M Y');
        
        $totalHutang = $unpaidDebts->sum('amount');
        
        $message = "💳 *REMINDER HUTANG BELUM LUNAS*\n";
        $message .= "🗓️ Tanggal: *{$tanggal}*\n\n";
        
        $message .= "⚠️ *DAFTAR HUTANG BELUM LUNAS*\n";
        $message .= "├─ Total Hutang : *{$unpaidDebts->count()} transaksi*\n";
        $message .= "└─ Total Nominal: *Rp " . number_format($totalHutang, 0, ',', '.') . "*\n\n";
        
        $message .= "DETAIL HUTANG:\n\n";
        
        foreach ($unpaidDebts as $index => $debt) {
            $tanggalHutang = \Carbon\Carbon::parse($debt->payment_date)->format('d M Y');
            $sisaHari = \Carbon\Carbon::parse($debt->payment_date)->diffInDays($now);
            
            $message .= ($index + 1) . ". *{$debt->guest_name}*\n";
            $message .= "   Produk  : {$debt->product_name}\n";
            $message .= "   Nominal : Rp " . number_format($debt->amount, 0, ',', '.') . "\n";
            $message .= "   Tanggal : {$tanggalHutang} ({$sisaHari} hari lalu)\n";
            
            if (!empty($debt->customer_phone)) {
                $phoneLink = self::formatPhoneLink($debt->customer_phone);
                $message .= "   HP      : {$phoneLink}\n";
            }
            
            $message .= "\n";
        }
        
        $message .= "💡 ACTION: Hubungi customer untuk pelunasan";
        $message .= "\n\nARIFAH Gym System";
        
        return self::send($message);
    }
}

