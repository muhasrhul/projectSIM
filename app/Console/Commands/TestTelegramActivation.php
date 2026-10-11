<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use App\Models\Paket;

class TestTelegramActivation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test-activation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test notifikasi pendaftaran member dengan button aktivasi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Telegram Activation Feature...');
        $this->newLine();
        
        // Cek konfigurasi
        $botToken = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');
        
        if (!$botToken || !$chatId) {
            $this->error('❌ TELEGRAM_BOT_TOKEN atau TELEGRAM_CHAT_ID belum diset');
            return 1;
        }
        
        // Cari member yang belum aktif
        $member = Member::where('is_active', false)->first();
        
        if (!$member) {
            $this->warn('⚠️ Tidak ada member yang belum aktif');
            $this->info('💡 Membuat dummy member untuk testing...');
            
            $paket = Paket::where('is_active', true)->first();
            
            if (!$paket) {
                $this->error('❌ Tidak ada paket aktif. Buat paket terlebih dahulu.');
                return 1;
            }
            
            $member = Member::create([
                'name' => 'Test Member ' . time(),
                'phone' => '08123456789',
                'email' => 'test' . time() . '@example.com',
                'type' => $paket->nama_paket,
                'is_active' => false,
            ]);
            
            $this->info("✅ Dummy member created: {$member->name} (ID: {$member->id})");
        } else {
            $this->info("📋 Member found: {$member->name} (ID: {$member->id})");
        }
        
        $this->newLine();
        $this->info('📤 Mengirim notifikasi dengan button aktivasi...');
        
        // Kirim notifikasi
        $result = \App\Helpers\TelegramHelper::sendPendaftaranBaru($member, $member->type);
        
        if ($result) {
            $this->newLine();
            $this->info('✅ Notifikasi terkirim!');
            $this->newLine();
            $this->info('💡 Cek Telegram Anda:');
            $this->info('1. Buka notifikasi yang baru saja dikirim');
            $this->info('2. Klik tombol "✅ Aktivasi Sekarang"');
            $this->info('3. Bot akan langsung mengaktifkan member');
            $this->newLine();
            $this->info('Atau coba command:');
            $this->info("/aktivasi {$member->id}");
            return 0;
        } else {
            $this->error('❌ Gagal mengirim notifikasi');
            $this->info('💡 Cek log untuk detail error');
            return 1;
        }
    }
}
