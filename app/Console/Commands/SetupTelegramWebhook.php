<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetupTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:setup-webhook {--remove : Hapus webhook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup webhook untuk Telegram Bot (aktivasi member via Telegram)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🤖 Setup Telegram Bot Webhook...');
        $this->newLine();
        
        // Cek token
        $botToken = env('TELEGRAM_BOT_TOKEN');
        
        if (!$botToken) {
            $this->error('❌ TELEGRAM_BOT_TOKEN tidak ditemukan di .env');
            $this->newLine();
            $this->info('📝 Langkah-langkah:');
            $this->info('1. Buka Telegram, cari @BotFather');
            $this->info('2. Kirim: /newbot (atau gunakan bot yang sudah ada)');
            $this->info('3. Copy token yang diberikan');
            $this->info('4. Tambahkan ke .env: TELEGRAM_BOT_TOKEN=your_token');
            return 1;
        }
        
        // Cek apakah ingin remove webhook
        if ($this->option('remove')) {
            return $this->removeWebhook($botToken);
        }
        
        // Get webhook URL
        $appUrl = env('APP_URL');
        if (!$appUrl || $appUrl === 'http://localhost') {
            $this->error('❌ APP_URL tidak valid atau masih localhost');
            $this->newLine();
            $this->warn('⚠️ Webhook Telegram memerlukan URL publik dengan HTTPS');
            $this->info('Contoh: APP_URL=https://arifahgym.cloud');
            return 1;
        }
        
        $webhookUrl = rtrim($appUrl, '/') . '/api/telegram/webhook';
        
        $this->info("📍 Webhook URL: {$webhookUrl}");
        $this->newLine();
        
        // Set webhook
        try {
            $response = Http::timeout(30)
                ->post("https://api.telegram.org/bot{$botToken}/setWebhook", [
                    'url' => $webhookUrl,
                    'allowed_updates' => ['message', 'callback_query'],
                ]);
            
            if ($response->successful()) {
                $result = $response->json();
                
                if ($result['ok'] ?? false) {
                    $this->info('✅ Webhook berhasil diset!');
                    $this->newLine();
                    $this->info('📋 Detail:');
                    $this->info("URL: {$webhookUrl}");
                    $this->info('Updates: message, callback_query');
                    $this->newLine();
                    $this->info('🎉 Bot siap menerima perintah aktivasi member!');
                    $this->newLine();
                    $this->info('💡 Perintah yang tersedia:');
                    $this->info('• /aktivasi [ID atau Nama] - Aktivasi member');
                    $this->info('• /help - Panduan penggunaan bot');
                    $this->info('• Klik tombol "Aktivasi Sekarang" di notifikasi pendaftaran');
                    return 0;
                } else {
                    $this->error('❌ Gagal set webhook');
                    $this->error('Response: ' . json_encode($result));
                    return 1;
                }
            } else {
                $this->error('❌ Request gagal: ' . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Remove webhook
     */
    private function removeWebhook($botToken)
    {
        $this->warn('🗑️ Menghapus webhook...');
        $this->newLine();
        
        try {
            $response = Http::timeout(30)
                ->post("https://api.telegram.org/bot{$botToken}/deleteWebhook");
            
            if ($response->successful()) {
                $result = $response->json();
                
                if ($result['ok'] ?? false) {
                    $this->info('✅ Webhook berhasil dihapus');
                    $this->info('ℹ️ Bot akan kembali ke mode polling (jika digunakan)');
                    return 0;
                } else {
                    $this->error('❌ Gagal hapus webhook');
                    $this->error('Response: ' . json_encode($result));
                    return 1;
                }
            } else {
                $this->error('❌ Request gagal: ' . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
