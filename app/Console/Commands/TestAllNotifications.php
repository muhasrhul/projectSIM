<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use App\Models\CashFlow;
use App\Models\QuickTransaction;
use App\Helpers\TelegramHelper;
use App\Helpers\WhatsAppHelper;
use Carbon\Carbon;

class TestAllNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:test-all';

    /**
     * The console command description.
     */
    protected $description = 'Test semua notifikasi Telegram & WhatsApp dengan data dummy';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing All Notifications...');
        $this->newLine();
        
        // 1. Test Laporan H-1 Expired
        $this->testReminderH1();
        
        // 2. Test Laporan Arus Kas Harian
        $this->testDailyCashFlow();
        
        // 3. Test Absen Member
        $this->testAbsenNotification();
        
        // 4. Test Reminder Hutang
        $this->testUnpaidDebt();
        
        $this->newLine();
        $this->info('✅ Semua test selesai! Cek Telegram & WhatsApp Anda.');
        
        return 0;
    }
    
    /**
     * Test 1: Laporan H-1 Expired
     */
    private function testReminderH1()
    {
        $this->info('1️⃣ Testing Laporan H-1 Expired...');
        
        // Ambil member yang akan expired besok (atau buat collection kosong untuk test)
        $tomorrow = Carbon::now('Asia/Makassar')->addDay()->startOfDay();
        $membersH1 = Member::where('is_active', true)
            ->whereDate('expiry_date', $tomorrow)
            ->get();
        
        if ($membersH1->isEmpty()) {
            $this->warn('   ⚠️  Tidak ada member yang expired besok');
            $this->info('   📤 Mengirim laporan kosong...');
        } else {
            $this->info("   ✅ Ditemukan {$membersH1->count()} member");
        }
        
        // Kirim notifikasi
        $telegramResult = TelegramHelper::sendReminderReportToOwner($membersH1);
        $waResult = WhatsAppHelper::sendReminderReportToOwner($membersH1);
        
        if ($telegramResult) {
            $this->info('   ✅ Telegram: Berhasil');
        } else {
            $this->error('   ❌ Telegram: Gagal');
        }
        
        if ($waResult['success']) {
            $this->info('   ✅ WhatsApp: Berhasil');
        } else {
            $this->error('   ❌ WhatsApp: Gagal');
        }
        
        $this->newLine();
    }
    
    /**
     * Test 2: Laporan Arus Kas Harian
     */
    private function testDailyCashFlow()
    {
        $this->info('2️⃣ Testing Laporan Arus Kas Harian...');
        
        $today = Carbon::now('Asia/Makassar');
        $cashFlows = CashFlow::whereDate('date', $today->format('Y-m-d'))->get();
        
        $totalIncome = $cashFlows->where('type', 'income')->sum('amount');
        $totalExpense = $cashFlows->where('type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;
        
        if ($cashFlows->isEmpty()) {
            $this->warn('   ⚠️  Tidak ada transaksi hari ini');
            $this->info('   📤 Mengirim laporan dengan nilai 0...');
        } else {
            $this->info("   ✅ Ditemukan {$cashFlows->count()} transaksi");
            $this->info("   💰 Pemasukan: Rp " . number_format($totalIncome, 0, ',', '.'));
            $this->info("   💸 Pengeluaran: Rp " . number_format($totalExpense, 0, ',', '.'));
            $this->info("   📊 Saldo: Rp " . number_format($netBalance, 0, ',', '.'));
        }
        
        // Kirim notifikasi
        $telegramResult = TelegramHelper::sendDailyCashFlowReport($today, $cashFlows, $totalIncome, $totalExpense, $netBalance);
        $waResult = WhatsAppHelper::sendDailyCashFlowReport($today, $cashFlows, $totalIncome, $totalExpense, $netBalance);
        
        if ($telegramResult) {
            $this->info('   ✅ Telegram: Berhasil');
        } else {
            $this->error('   ❌ Telegram: Gagal');
        }
        
        if ($waResult['success']) {
            $this->info('   ✅ WhatsApp: Berhasil');
        } else {
            $this->error('   ❌ WhatsApp: Gagal');
        }
        
        $this->newLine();
    }
    
    /**
     * Test 3: Absen Member
     */
    private function testAbsenNotification()
    {
        $this->info('3️⃣ Testing Notifikasi Absen Member...');
        
        // Ambil member aktif pertama untuk test
        $member = Member::where('is_active', true)->first();
        
        if (!$member) {
            $this->error('   ❌ Tidak ada member aktif untuk test');
            $this->newLine();
            return;
        }
        
        $this->info("   ✅ Testing dengan member: {$member->name}");
        
        // Hitung total latihan bulan ini (dummy)
        $totalLatihan = rand(5, 20);
        
        // Tentukan badge berdasarkan total latihan
        if ($totalLatihan >= 20) {
            $badge = '🏆 Champion';
        } elseif ($totalLatihan >= 15) {
            $badge = '🥇 Gold';
        } elseif ($totalLatihan >= 10) {
            $badge = '🥈 Silver';
        } elseif ($totalLatihan >= 5) {
            $badge = '🥉 Bronze';
        } else {
            $badge = '⭐ Starter';
        }
        
        $this->info("   📊 Total Latihan: {$totalLatihan}x");
        $this->info("   🏅 Badge: {$badge}");
        
        // Kirim notifikasi
        $telegramResult = TelegramHelper::sendAbsenNotification($member, $totalLatihan, $badge);
        $waResult = WhatsAppHelper::sendAbsenNotification($member, $totalLatihan, $badge);
        
        if ($telegramResult) {
            $this->info('   ✅ Telegram: Berhasil');
        } else {
            $this->error('   ❌ Telegram: Gagal');
        }
        
        if ($waResult['success']) {
            $this->info('   ✅ WhatsApp: Berhasil');
        } else {
            $this->error('   ❌ WhatsApp: Gagal');
        }
        
        $this->newLine();
    }
    
    /**
     * Test 4: Reminder Hutang
     */
    private function testUnpaidDebt()
    {
        $this->info('4️⃣ Testing Reminder Hutang Belum Lunas...');
        
        // Ambil hutang yang belum lunas
        $unpaidDebts = QuickTransaction::where('payment_status', 'pending')->get();
        
        if ($unpaidDebts->isEmpty()) {
            $this->warn('   ⚠️  Tidak ada hutang yang belum lunas');
            $this->info('   ℹ️  Notifikasi tidak akan dikirim (sesuai logic)');
            $this->newLine();
            return;
        }
        
        $totalHutang = $unpaidDebts->sum('amount');
        $this->info("   ✅ Ditemukan {$unpaidDebts->count()} hutang");
        $this->info("   💳 Total: Rp " . number_format($totalHutang, 0, ',', '.'));
        
        // Kirim notifikasi
        $telegramResult = TelegramHelper::sendUnpaidDebtReminder($unpaidDebts);
        $waResult = WhatsAppHelper::sendUnpaidDebtReminder($unpaidDebts);
        
        if ($telegramResult) {
            $this->info('   ✅ Telegram: Berhasil');
        } else {
            $this->error('   ❌ Telegram: Gagal');
        }
        
        if ($waResult['success']) {
            $this->info('   ✅ WhatsApp: Berhasil');
        } else {
            $this->error('   ❌ WhatsApp: Gagal');
        }
        
        $this->newLine();
    }
}
