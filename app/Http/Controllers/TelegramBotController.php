<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Member;
use App\Models\Paket;
use App\Models\Transaction;
use App\Models\CashFlow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TelegramBotController extends Controller
{
    /**
     * Handle webhook dari Telegram Bot
     */
    public function webhook(Request $request)
    {
        $update = $request->all();
        
        Log::info('[Telegram Bot] Webhook received', ['update' => $update]);
        
        // Handle callback query (inline button)
        if (isset($update['callback_query'])) {
            return $this->handleCallbackQuery($update['callback_query']);
        }
        
        // Handle message (command)
        if (isset($update['message'])) {
            return $this->handleMessage($update['message']);
        }
        
        return response()->json(['ok' => true]);
    }
    
    /**
     * Handle inline button callback
     */
    private function handleCallbackQuery($callbackQuery)
    {
        $callbackData = $callbackQuery['data'] ?? '';
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $messageId = $callbackQuery['message']['message_id'] ?? null;
        $userName = $callbackQuery['from']['first_name'] ?? 'Admin';
        
        if (!$chatId) {
            return response()->json(['ok' => true]);
        }
        
        // Parse callback data: aktivasi_[member_id]_[paket_id]
        if (preg_match('/^aktivasi_(\d+)_(\d+)$/', $callbackData, $matches)) {
            $memberId = $matches[1];
            $paketId = $matches[2];
            
            $result = $this->aktivasiMember($memberId, $paketId, $userName);
            
            // Tidak perlu kirim pesan tambahan, notifikasi lengkap sudah dikirim dari helper
            // Cukup answer callback query untuk hilangkan loading
            $this->answerCallbackQuery($callbackQuery['id'], $result['alert']);
        }
        
        return response()->json(['ok' => true]);
    }
    
    /**
     * Handle text message (commands)
     */
    private function handleMessage($message)
    {
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? '';
        
        if (!$chatId) {
            return response()->json(['ok' => true]);
        }
        
        // Command: /aktivasi [member_id atau nama]
        if (preg_match('/^\/aktivasi\s+(.+)$/i', $text, $matches)) {
            $search = trim($matches[1]);
            return $this->handleAktivasiCommand($chatId, $search);
        }
        
        // Command: /help
        if (preg_match('/^\/help$/i', $text)) {
            return $this->handleHelpCommand($chatId);
        }
        
        return response()->json(['ok' => true]);
    }
    
    /**
     * Handle command /aktivasi
     */
    private function handleAktivasiCommand($chatId, $search)
    {
        // Cari member berdasarkan ID atau nama
        if (is_numeric($search)) {
            $member = Member::find($search);
        } else {
            $member = Member::where('name', 'like', "%{$search}%")
                ->where('is_active', false)
                ->first();
        }
        
        if (!$member) {
            $this->sendMessage($chatId, "❌ Member tidak ditemukan: {$search}");
            return response()->json(['ok' => true]);
        }
        
        if ($member->is_active) {
            $this->sendMessage($chatId, "✅ Member *{$member->name}* sudah aktif.");
            return response()->json(['ok' => true]);
        }
        
        // Cari paket
        $paket = Paket::where('nama_paket', $member->type)->first();
        
        if (!$paket) {
            $this->sendMessage($chatId, "❌ Paket tidak ditemukan untuk member *{$member->name}*");
            return response()->json(['ok' => true]);
        }
        
        // Kirim konfirmasi dengan inline keyboard
        $message = "📋 *KONFIRMASI AKTIVASI MEMBER*\n\n";
        $message .= "Nama: {$member->name}\n";
        $message .= "HP: {$member->phone}\n";
        $message .= "Paket: {$paket->nama_paket}\n";
        $message .= "Harga: Rp " . number_format($paket->harga, 0, ',', '.') . "\n\n";
        $message .= "Aktifkan member ini?";
        
        $keyboard = [
            [
                [
                    'text' => '✅ Aktivasi Sekarang',
                    'callback_data' => "aktivasi_{$member->id}_{$paket->id}"
                ]
            ]
        ];
        
        $this->sendMessage($chatId, $message, $keyboard);
        
        return response()->json(['ok' => true]);
    }
    
    /**
     * Handle command /help
     */
    private function handleHelpCommand($chatId)
    {
        $message = "🤖 *ARIFAH GYM BOT - PANDUAN*\n\n";
        $message .= "*Perintah yang tersedia:*\n";
        $message .= "• /aktivasi [ID atau Nama] - Aktivasi member\n";
        $message .= "• /help - Tampilkan panduan ini\n\n";
        $message .= "*Cara aktivasi member:*\n";
        $message .= "1. Klik tombol dari notifikasi pendaftaran\n";
        $message .= "2. Atau ketik: /aktivasi [ID/Nama member]\n\n";
        $message .= "Contoh:\n";
        $message .= "• /aktivasi 123\n";
        $message .= "• /aktivasi Budi Santoso";
        
        $this->sendMessage($chatId, $message);
        
        return response()->json(['ok' => true]);
    }
    
    /**
     * Proses aktivasi member
     */
    private function aktivasiMember($memberId, $paketId, $adminName = 'Admin')
    {
        try {
            DB::beginTransaction();
            
            $member = Member::find($memberId);
            $paket = Paket::find($paketId);
            
            if (!$member || !$paket) {
                DB::rollback();
                return [
                    'message' => '❌ Data tidak ditemukan',
                    'alert' => 'Member atau paket tidak ditemukan'
                ];
            }
            
            if ($member->is_active) {
                DB::rollback();
                return [
                    'message' => "✅ Member *{$member->name}* sudah aktif sebelumnya",
                    'alert' => 'Member sudah aktif'
                ];
            }
            
            // Hitung tanggal expired
            $durasi = $paket->durasi_hari;
            $now = Carbon::now('Asia/Makassar');
            
            if ($durasi >= 30) {
                $bulan = round($durasi / 30);
                $expiryDate = $now->copy()->addMonths($bulan);
            } else {
                if ($durasi == 1) {
                    $expiryDate = $now->copy();
                } else {
                    $expiryDate = $now->copy()->addDays($durasi - 1);
                }
            }
            
            // Update member
            $member->update([
                'is_active' => true,
                'expiry_date' => $expiryDate->format('Y-m-d'),
            ]);
            
            // Buat transaksi
            $transaction = Transaction::create([
                'member_id' => $member->id,
                'order_id' => 'ACT-' . strtoupper(uniqid()),
                'amount' => $paket->harga + ($paket->registration_fee ?? 0),
                'type' => 'Pendaftaran Baru: ' . $paket->nama_paket,
                'payment_method' => 'Transfer Bank',
                'payment_date' => $now,
                'guest_name' => $member->name,
            ]);
            
            // Catat ke cash flow
            CashFlow::createEntry(
                'income',
                'member',
                'Pendaftaran Baru - ' . $member->name . ' (' . $paket->nama_paket . ')',
                $transaction->amount,
                $transaction->id,
                $now
            );
            
            DB::commit();
            
            Log::info('[Telegram Bot] Member activated', [
                'member_id' => $member->id,
                'admin' => $adminName,
                'transaction_id' => $transaction->id
            ]);
            
            // Refresh member data untuk notifikasi
            $memberFresh = Member::find($member->id);
            
            // Kirim notifikasi aktivasi (sama seperti di EditMember)
            \App\Helpers\TelegramHelper::sendAktivasiMember($memberFresh, $transaction);
            \App\Helpers\WhatsAppHelper::sendAktivasiMember($memberFresh, $transaction);
            
            // Pesan singkat untuk callback answer (edit pesan yang diklik)
            $message = "✅ *AKTIVASI BERHASIL!*\n\n";
            $message .= "Member: {$member->name}\n";
            $message .= "Paket: {$paket->nama_paket}\n";
            $message .= "Aktif s/d: " . $expiryDate->format('d M Y') . "\n\n";
            $message .= "Diaktivasi oleh: {$adminName}\n";
            $message .= "Waktu: " . now()->format('d M Y, H:i') . " WITA";
            
            return [
                'message' => $message,
                'alert' => 'Aktivasi berhasil! ✅'
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('[Telegram Bot] Activation failed', [
                'member_id' => $memberId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'message' => '❌ *AKTIVASI GAGAL*\n\nTerjadi kesalahan: ' . $e->getMessage(),
                'alert' => 'Aktivasi gagal'
            ];
        }
    }
    
    /**
     * Kirim pesan ke Telegram
     */
    private function sendMessage($chatId, $message, $keyboard = null)
    {
        $botToken = config('services.telegram.bot_token');
        
        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ];
        
        if ($keyboard) {
            $data['reply_markup'] = json_encode([
                'inline_keyboard' => $keyboard
            ]);
        }
        
        try {
            \Illuminate\Support\Facades\Http::timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", $data);
        } catch (\Exception $e) {
            Log::error('[Telegram Bot] Send message failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Edit pesan yang sudah dikirim
     */
    private function editMessage($chatId, $messageId, $message)
    {
        $botToken = config('services.telegram.bot_token');
        
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ];
        
        try {
            \Illuminate\Support\Facades\Http::timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/editMessageText", $data);
        } catch (\Exception $e) {
            Log::error('[Telegram Bot] Edit message failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Answer callback query
     */
    private function answerCallbackQuery($callbackQueryId, $text = null)
    {
        $botToken = config('services.telegram.bot_token');
        
        $data = [
            'callback_query_id' => $callbackQueryId,
        ];
        
        if ($text) {
            $data['text'] = $text;
        }
        
        try {
            \Illuminate\Support\Facades\Http::timeout(10)
                ->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", $data);
        } catch (\Exception $e) {
            Log::error('[Telegram Bot] Answer callback failed', ['error' => $e->getMessage()]);
        }
    }
}
