<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WhatsAppHelper
{
    /**
     * Kirim pesan WhatsApp via Fonnte API
     * 
     * @param string $phone Nomor HP (format: 08xxx atau 628xxx)
     * @param string $message Isi pesan
     * @return array Response dari API
     */
    public static function sendMessage($phone, $message)
    {
        // Validasi API token
        $token = config('services.fonnte.token');
        if (!$token) {
            Log::error('FONNTE_TOKEN tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'FONNTE_TOKEN tidak dikonfigurasi'
            ];
        }

        // Format nomor HP (pastikan format 628xxx)
        $phone = self::formatPhoneNumber($phone);

        try {
            // Kirim request ke Fonnte API dengan timeout pendek dan tanpa retry untuk absen
            $response = Http::timeout(3) // Timeout 3 detik saja
                ->withHeaders([
                    'Authorization' => $token,
                ])->post('https://api.fonnte.com/send', [
                    'target' => $phone,
                    'message' => $message,
                    'countryCode' => '62', // Indonesia
                ]);

            // Cek status HTTP response
            if (!$response->successful()) {
                Log::error('WhatsApp API HTTP Error', [
                    'phone' => $phone,
                    'status_code' => $response->status(),
                    'response_body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'API WhatsApp tidak merespons dengan benar'
                ];
            }

            $result = $response->json();

            // Validasi response structure
            if (!is_array($result)) {
                Log::error('WhatsApp API Invalid Response Format', [
                    'phone' => $phone,
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Format response API tidak valid'
                ];
            }

            // Log response
            Log::info('WhatsApp sent', [
                'phone' => $phone,
                'status' => $result['status'] ?? 'unknown',
                'message' => substr($message, 0, 50) . '...'
            ]);

            // Cek status dari API Fonnte
            $isSuccess = ($result['status'] ?? false) === true;
            
            if (!$isSuccess) {
                Log::warning('WhatsApp API returned failure', [
                    'phone' => $phone,
                    'api_response' => $result
                ]);
            }

            return [
                'success' => $isSuccess,
                'data' => $result,
                'message' => $result['reason'] ?? ($isSuccess ? 'Berhasil' : 'Gagal mengirim pesan')
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('WhatsApp Connection Error', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Koneksi ke server WhatsApp gagal'
            ];

        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('WhatsApp Request Error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'response' => $e->response ? $e->response->body() : null
            ]);

            return [
                'success' => false,
                'message' => 'Permintaan ke API WhatsApp gagal'
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp Unexpected Error', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengirim pesan'
            ];
        }
    }

    /**
     * Format nomor HP ke format 628xxx
     * 
     * @param string $phone
     * @return string
     */
    private static function formatPhoneNumber($phone)
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

        return $phone;
    }

    /**
     * Template: Reminder H-7 membership akan expired
     * STATUS: DISABLED - Untuk input data manual
     */
    public static function sendReminderH7($member)
    {
        // DISABLED: Pesan reminder ke member dimatikan sementara untuk input data manual
        return [
            'success' => true,
            'message' => 'Reminder H-7 disabled for manual data entry'
        ];
        
        /* ORIGINAL CODE - DISABLED
        $message = "🔔 *REMINDER MEMBERSHIP - ARIFAH GYM*\n\n";
        $message .= "Halo *{$member->name}*\n\n";
        $message .= "Membership Anda akan berakhir dalam *7 hari* lagi.\n\n";
        $message .= "📅 Tanggal Berakhir: *" . \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y') . "*\n";
        $message .= "💪 Paket: *{$member->type}*\n\n";
        $message .= "Segera perpanjang membership Anda agar tetap bisa menikmati fasilitas gym!\n\n";
        $message .= "Hubungi kasir atau datang langsung ke ARIFAH Gym.\n\n";
        $message .= "Terima kasih!";

        return self::sendMessage($member->phone, $message);
        */
    }

    /**
     * Template: Reminder H-3 membership akan expired
     * STATUS: DISABLED - Untuk input data manual
     */
    public static function sendReminderH3($member)
    {
        // DISABLED: Pesan reminder ke member dimatikan sementara untuk input data manual
        return [
            'success' => true,
            'message' => 'Reminder H-3 disabled for manual data entry'
        ];
        
        /* ORIGINAL CODE - DISABLED
        $message = "⚠️ *REMINDER PENTING - ARIFAH GYM*\n\n";
        $message .= "Halo *{$member->name}*\n\n";
        $message .= "Membership Anda tinggal *3 hari* lagi!\n\n";
        $message .= "📅 Tanggal Berakhir: *" . \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y') . "*\n";
        $message .= "💪 Paket: *{$member->type}*\n\n";
        $message .= "Jangan sampai terputus! Perpanjang sekarang dan dapatkan promo spesial!\n\n";
        $message .= "Hubungi kasir atau datang langsung ke ARIFAH Gym.\n\n";
        $message .= "Terima kasih!";

        return self::sendMessage($member->phone, $message);
        */
    }

    /**
     * Template: Reminder H-1 membership akan expired
     * STATUS: DISABLED - Untuk input data manual
     */
    public static function sendReminderH1($member)
    {
        $message = "🚨 *REMINDER - ARIFAH GYM*\n\n";
        $message .= "Halo *{$member->name}*\n\n";
        $message .= "Membership Anda akan berakhir *BESOK*!\n\n";
        $message .= "📅 Tanggal Berakhir: *" . \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y') . "*\n";
        $message .= "💪 Paket: *{$member->type}*\n\n";
        $message .= "Ini kesempatan terakhir! Perpanjang hari ini dan tetap aktif!\n\n";
        $message .= "Hubungi kasir atau datang langsung ke ARIFAH Gym.\n\n";
        $message .= "Terima kasih!";

        return self::sendMessage($member->phone, $message);
    }

    /**
     * Template: Notifikasi setelah perpanjangan membership
     */
    public static function sendPerpanjanganSuccess($member)
    {
        // DISABLED: Pesan perpanjangan success dimatikan sementara untuk input data manual
        return [
            'success' => true,
            'message' => 'Perpanjangan success notification disabled for manual data entry'
        ];
        
        /* ORIGINAL CODE - DISABLED
        $message = "✅ *PERPANJANGAN BERHASIL - ARIFAH GYM*\n\n";
        $message .= "Terima kasih *{$member->name}*\n\n";
        $message .= "Membership Anda sudah berhasil diperpanjang!\n\n";
        $message .= "📅 Aktif Sampai: *" . \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y') . "*\n";
        $message .= "💪 Paket: *{$member->type}*\n\n";
        $message .= "Selamat berlatih dan raih target fitness Anda!\n\n";
        $message .= "Terima kasih sudah mempercayai ARIFAH Gym!";

        return self::sendMessage($member->phone, $message);
        */
    }

    /**
     * Template: Ucapan ulang tahun
     */
    public static function sendBirthdayWish($member)
    {
        $message = "🎂 *SELAMAT ULANG TAHUN - ARIFAH GYM*\n\n";
        $message .= "Selamat ulang tahun *{$member->name}*\n\n";
        $message .= "Semoga panjang umur, sehat selalu, dan semakin semangat dalam mencapai target fitness!\n\n";
        $message .= "Terima kasih sudah menjadi bagian dari keluarga ARIFAH Gym!\n\n";
        $message .= "Salam sehat,\n";
        $message .= "ARIFAH Gym Team";

        return self::sendMessage($member->phone, $message);
    }

    /**
     * Template: Blast promo (custom message)
     */
    public static function sendPromo($phone, $message)
    {
        $promoMessage = "📢 *PROMO SPESIAL - ARIFAH GYM*\n\n";
        $promoMessage .= $message . "\n\n";
        $promoMessage .= "Jangan lewatkan kesempatan ini!\n\n";
        $promoMessage .= "Info lebih lanjut hubungi kasir atau datang langsung ke ARIFAH Gym.\n\n";
        $promoMessage .= "Terima kasih!";

        return self::sendMessage($phone, $promoMessage);
    }

    /**
     * Template: Laporan harian ke owner tentang member yang expired HARI INI
     */
    public static function sendDailyReportToOwner($membersExpiredToday)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }

        $message = "🚨 *NOTIFIKASI EXPIRED - ARIFAH GYM*\n\n";
        $message .= "Tanggal: " . \Carbon\Carbon::now('Asia/Makassar')->translatedFormat('d F Y') . "\n\n";

        if ($membersExpiredToday->count() > 0) {
            $message .= "⚠️ *MEMBER YANG EXPIRED HARI INI:*\n\n";
            $message .= "Total: *{$membersExpiredToday->count()} member*\n\n";
            
            foreach ($membersExpiredToday as $member) {
                $expiryDate = \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y');
                $message .= "• *{$member->name}*\n";
                $message .= "  Paket: {$member->type}\n";
                $message .= "  Expired: {$expiryDate}\n";
                $message .= "  HP: {$member->phone}\n\n";
            }

            $message .= "Segera hubungi member untuk perpanjangan!\n\n";
        } else {
            $message .= "✅ *TIDAK ADA MEMBER YANG EXPIRED HARI INI*\n\n";
            $message .= "Semua member masih aktif!\n\n";
        }

        $message .= "ARIFAH Gym System";

        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * Template: Laporan ke owner tentang member yang AKAN EXPIRED BESOK (H-1)
     */
    public static function sendReminderReportToOwner($membersH1)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }

        $message = "🚨 *LAPORAN H-1 EXPIRED*\n\n";
        
        if ($membersH1->count() > 0) {
            // Ambil tanggal expired (besok) dari member pertama
            $expiredDate = \Carbon\Carbon::parse($membersH1->first()->expiry_date)->translatedFormat('d F Y');
            
            // BAGIAN DAFTAR MEMBER
            $message .= "DAFTAR MEMBER YANG AKAN EXPIRED\n";
            $message .= "├─ Pada Tanggal : *{$expiredDate}*\n";
            $message .= "└─ Total Member : *{$membersH1->count()} member*\n\n";
            
            foreach ($membersH1 as $member) {
                $memberExpiryDate = \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y');
                $message .= "• *{$member->name}*\n";
                $message .= "  Paket: {$member->type}\n";
                $message .= "  Expired: {$memberExpiryDate}\n";
                $message .= "  HP: {$member->phone}\n\n";
            }

            $message .= "💡 ACTION: Hubungi member untuk perpanjangan";
        } else {
            // BAGIAN DAFTAR MEMBER (tidak ada member)
            $tomorrowDate = \Carbon\Carbon::now('Asia/Makassar')->addDay()->translatedFormat('d F Y');
            
            $message .= "DAFTAR MEMBER YANG AKAN EXPIRED\n";
            $message .= "├─ Pada Tanggal : *{$tomorrowDate}*\n";
            $message .= "└─ Total Member : *0 member*\n\n";
            
            $message .= "✅ TIDAK ADA MEMBER YANG AKAN EXPIRED BESOK\n\n";
            $message .= "💡 Semua member masih aman!";
        }

        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * Template: Notifikasi transaksi produk ke owner
     */
    public static function sendTransactionNotification($transaction)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }

        $message = "💰 *TRANSAKSI BARU - ARIFAH GYM*\n\n";
        $message .= "Tanggal: " . \Carbon\Carbon::parse($transaction->payment_date)->translatedFormat('d F Y H:i') . "\n\n";
        
        // Customer info
        if ($transaction->member) {
            $message .= "👤 *Customer:* {$transaction->member->name}\n";
        } elseif ($transaction->guest_name) {
            $message .= "👤 *Customer:* {$transaction->guest_name} (Tamu)\n";
        } else {
            $message .= "👤 *Customer:* Umum\n";
        }
        
        // Transaction details
        $message .= "📦 *Produk:* {$transaction->type}\n";
        $message .= "💵 *Harga:* Rp " . number_format($transaction->amount, 0, ',', '.') . "\n";
        $message .= "💳 *Metode:* {$transaction->payment_method}\n";
        $message .= "✅ *Status:* Lunas\n\n";
        
        $message .= "Terima kasih!\n\n";
        $message .= "ARIFAH Gym System";

        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * BARU: Kirim notifikasi untuk transaksi kasir cepat
     */
    public static function sendQuickTransactionNotification($quickTransaction)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }

        $message = "⚡ *KASIR CEPAT - ARIFAH GYM*\n\n";
        $message .= "Tanggal: " . \Carbon\Carbon::parse($quickTransaction->payment_date)->translatedFormat('d F Y H:i') . "\n\n";
        
        // Customer info
        $message .= "👤 *Customer:* {$quickTransaction->guest_name}\n";
        
        // Transaction details
        $message .= "📦 *Produk:* {$quickTransaction->product_name}\n";
        $message .= "💵 *Harga:* Rp " . number_format($quickTransaction->amount, 0, ',', '.') . "\n";
        $message .= "💳 *Metode:* {$quickTransaction->payment_method}\n";
        $message .= "✅ *Status:* Lunas\n\n";
        
        $message .= "Terima kasih!\n\n";
        $message .= "ARIFAH Gym System";

        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * Template: Notifikasi pendaftaran member baru ke owner
     */
    public static function sendPendaftaranBaru($member, $paket)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }

        $message = "📋 *PENDAFTARAN MEMBER BARU*\n";
        $message .= "├─ Nama    : {$member->name}\n";
        $message .= "├─ HP      : {$member->phone}\n";
        $message .= "├─ Email   : {$member->email}\n";
        $message .= "├─ Paket   : {$paket}\n";
        $message .= "└─ Waktu   : " . \Carbon\Carbon::now('Asia/Makassar')->format('d M Y H:i') . "\n\n";
        $message .= "⚠️ STATUS: MENUNGGU AKTIVASI\n\n";
        $message .= "💡 ACTION: Aktivasi di panel admin";

        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * Template: Notifikasi aktivasi member ke owner
     */
    public static function sendAktivasiMember($member, $transaction)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }

        $message = "✅ *AKTIVASI MEMBER*\n\n";
        
        // BAGIAN 1: DATA MEMBER
        $message .= "DATA MEMBER\n";
        $message .= "├─ Nama        : {$member->name}\n";
        $message .= "├─ HP          : {$member->phone}\n";
        
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
        $message .= "├─ Aktif s/d : " . \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y') . "\n";
        $message .= "└─ Waktu     : " . \Carbon\Carbon::now('Asia/Makassar')->translatedFormat('d F Y H:i') . "\n\n";
        
        $message .= "🎉 Member aktif dan siap latihan!";

        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * Template: Notifikasi perpanjangan member ke owner
     */
    public static function sendPerpanjanganMember($member, $transaction)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }

        $message = "🔄 *PERPANJANGAN MEMBERSHIP*\n\n";
        
        // BAGIAN 1: DATA MEMBER
        $message .= "DATA MEMBER\n";
        $message .= "├─ Nama        : {$member->name}\n";
        $message .= "├─ HP          : {$member->phone}\n";
        
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
        $message .= "├─ Aktif s/d : " . \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y') . "\n";
        $message .= "└─ Waktu     : " . \Carbon\Carbon::now('Asia/Makassar')->translatedFormat('d F Y H:i') . "\n\n";
        
        $message .= "🎉 Perpanjangan berhasil!";

        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * Template: Notifikasi perpanjangan EARLY (H-2 sampai H-1) ke owner
     */
    public static function sendPerpanjanganEarly($member, $transaction)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }

        $message = "⚡ *PERPANJANGAN EARLY*\n\n";
        
        // BAGIAN 1: DATA MEMBER
        $message .= "DATA MEMBER\n";
        $message .= "├─ Nama        : {$member->name}\n";
        $message .= "├─ HP          : {$member->phone}\n";
        
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
        $message .= "├─ Aktif s/d : " . \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y') . "\n";
        $message .= "└─ Waktu     : " . \Carbon\Carbon::now('Asia/Makassar')->translatedFormat('d F Y H:i') . "\n\n";
        
        $message .= "🎉 Perpanjangan early berhasil!\n";
        $message .= "💡 Member tidak kehilangan sisa waktu membership";

        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * Template: Laporan pembukuan harian ke owner
     */
    public static function sendDailyCashFlowReport($date, $cashFlows, $totalIncome, $totalExpense, $netBalance)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }
        
        // Format tanggal Indonesia
        $tanggal = $date->translatedFormat('d F Y');
        $tanggalUrl = $date->format('Y-m-d'); // Format untuk URL
        
        // Buat pesan laporan
        $message = "📊 *LAPORAN PEMBUKUAN HARIAN*\n";
        $message .= "🗓️ Tanggal: *{$tanggal}*\n\n";
        
        // RINGKASAN KEUANGAN
        $message .= "💰 *RINGKASAN KEUANGAN*\n";
        $message .= "├─ Pemasukan : Rp " . number_format($totalIncome, 0, ',', '.') . "\n";
        $message .= "├─ Pengeluaran: Rp " . number_format($totalExpense, 0, ',', '.') . "\n";
        $message .= "└─ Saldo Bersih: Rp " . number_format($netBalance, 0, ',', '.') . "\n\n";
        
        // Hapus bagian detail transaksi
        // Langsung ke link export PDF
        
        // LINK EXPORT PDF dengan tanggal spesifik (protected dengan auth)
        $exportUrl = "https://arifahgym.cloud/export/pembukuan?period=single&date={$tanggalUrl}";
        $message .= "📄 *EXPORT LAPORAN PDF*\n";
        $message .= "Klik link berikut untuk download:\n";
        $message .= "{$exportUrl}\n\n";
        
        $message .= "ARIFAH Gym System";
        
        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * Kirim notifikasi absen member ke owner
     */
    public static function sendAbsenNotification($member, $totalLatihan, $badge)
    {
        // Nomor owner (ambil dari env atau hardcode)
        $ownerPhone = env('OWNER_WHATSAPP', '6281234567890'); // Ganti dengan nomor owner
        
        $now = Carbon::now('Asia/Makassar');
        $jamAbsen = $now->format('H:i');
        $tanggalAbsen = $now->translatedFormat('d F Y');
        
        // Format pesan dengan 3 bagian
        $message = "🏋️ *ABSEN MEMBER*\n\n";
        
        // BAGIAN 1: DATA MEMBER
        $message .= "DATA MEMBER\n";
        $message .= "├─ Nama     : {$member->name}\n";
        $message .= "├─ WhatsApp : {$member->phone}\n";
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
            $expiredDate = Carbon::parse($member->expiry_date);
            $sisaHari = $expiredDate->diffInDays($now);
            
            if ($expiredDate->isFuture()) {
                $message .= "├─ Sisa Waktu : {$sisaHari} hari lagi\n";
                $message .= "└─ Expired    : " . $expiredDate->translatedFormat('d F Y');
            } else {
                $message .= "└─ Status     : EXPIRED";
            }
        } else {
            $message .= "└─ Status     : Member Harian";
        }
        
        return self::sendMessage($ownerPhone, $message);
    }

    /**
     * Template: Notifikasi hutang yang belum lunas ke owner
     */
    public static function sendUnpaidDebtReminder($unpaidDebts)
    {
        $ownerPhone = config('services.whatsapp.owner');
        
        if (!$ownerPhone) {
            Log::warning('OWNER_WHATSAPP tidak ditemukan di config');
            return [
                'success' => false,
                'message' => 'OWNER_WHATSAPP tidak dikonfigurasi'
            ];
        }

        $now = Carbon::now('Asia/Makassar');
        $tanggal = $now->translatedFormat('d F Y');
        
        $totalHutang = $unpaidDebts->sum('amount');
        
        $message = "💳 *REMINDER HUTANG BELUM LUNAS*\n";
        $message .= "🗓️ Tanggal: *{$tanggal}*\n\n";
        
        $message .= "⚠️ *DAFTAR HUTANG BELUM LUNAS*\n";
        $message .= "├─ Total Hutang : *{$unpaidDebts->count()} transaksi*\n";
        $message .= "└─ Total Nominal: *Rp " . number_format($totalHutang, 0, ',', '.') . "*\n\n";
        
        $message .= "DETAIL HUTANG:\n\n";
        
        foreach ($unpaidDebts as $index => $debt) {
            $tanggalHutang = Carbon::parse($debt->payment_date)->translatedFormat('d M Y');
            $sisaHari = Carbon::parse($debt->payment_date)->diffInDays($now);
            
            $message .= ($index + 1) . ". *{$debt->guest_name}*\n";
            $message .= "   Produk  : {$debt->product_name}\n";
            $message .= "   Nominal : Rp " . number_format($debt->amount, 0, ',', '.') . "\n";
            $message .= "   Tanggal : {$tanggalHutang} ({$sisaHari} hari lalu)\n";
            
            if (!empty($debt->customer_phone)) {
                $message .= "   HP      : {$debt->customer_phone}\n";
            }
            
            $message .= "\n";
        }
        
        $message .= "💡 ACTION: Hubungi customer untuk pelunasan";
        $message .= "\n\nARIFAH Gym System";
        
        return self::sendMessage($ownerPhone, $message);
    }
}