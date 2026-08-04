<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use App\Models\Member;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // ========================================
        // AUTO UPDATE STATUS MEMBER EXPIRED
        // ========================================
        
        // PRODUCTION: Jalankan setiap hari jam 00:01 (tengah malam)
        $schedule->command('members:update-expired')
            ->dailyAt('00:01')
            ->timezone('Asia/Makassar')
            ->appendOutputTo(storage_path('logs/scheduler.log'));
        
        // TESTING: Uncomment baris di bawah untuk test (jalankan setiap menit)
        // Setelah test berhasil, comment lagi dan gunakan yang dailyAt
        // $schedule->command('members:update-expired')
        //     ->everyMinute()
        //     ->appendOutputTo(storage_path('logs/scheduler.log'));

        // ========================================
        // AUTO KIRIM REMINDER WHATSAPP MEMBERSHIP
        // ========================================
        
        // PRODUCTION: Jalankan setiap hari jam 09:00 pagi
        $schedule->command('membership:send-reminders')
            ->dailyAt('09:00')
            ->timezone('Asia/Makassar')
            ->appendOutputTo(storage_path('logs/whatsapp-reminders.log'));
        
        // TESTING: Uncomment baris di bawah untuk test (jalankan setiap menit)
        // Setelah test berhasil, comment lagi dan gunakan yang dailyAt
        // $schedule->command('membership:send-reminders')
        //     ->everyMinute()
        //     ->appendOutputTo(storage_path('logs/whatsapp-reminders.log'));

        // ========================================
        // AUTO KIRIM LAPORAN ARUS KAS HARIAN
        // ========================================
        
        // PRODUCTION: Jalankan setiap hari jam 23:30 malam (akhir hari)
        $schedule->command('cashflow:daily-report')
            ->dailyAt('23:30')
            ->timezone('Asia/Makassar')
            ->appendOutputTo(storage_path('logs/daily-cashflow-report.log'));
        
        // TESTING: Uncomment baris di bawah untuk test (jalankan setiap menit)
        // Setelah test berhasil, comment lagi dan gunakan yang dailyAt
        // $schedule->command('cashflow:daily-report')
        //     ->everyMinute()
        //     ->appendOutputTo(storage_path('logs/daily-cashflow-report.log'));

        // ========================================
        // AUTO CLEAR NOTIFICATIONS
        // ========================================
        
        // PRODUCTION: Hapus notifikasi lama setiap hari jam 02:00 dini hari
        $schedule->command('notifications:clear --days=0')
            ->dailyAt('02:00')
            ->timezone('Asia/Makassar')
            ->appendOutputTo(storage_path('logs/clear-notifications.log'));
        
        // TESTING: Uncomment baris di bawah untuk test (jalankan setiap menit)
        // Setelah test berhasil, comment lagi dan gunakan yang dailyAt
        // $schedule->command('notifications:clear --days=1')
        //     ->everyMinute()
        //     ->appendOutputTo(storage_path('logs/clear-notifications.log'));

        // ========================================
        // AUTO KIRIM REMINDER HUTANG BELUM LUNAS
        // ========================================
        
        // PRODUCTION: Jalankan setiap hari jam 22:30 malam (10:30 malam)
        $schedule->command('debt:send-reminder')
            ->dailyAt('22:30')
            ->timezone('Asia/Makassar')
            ->appendOutputTo(storage_path('logs/debt-reminder.log'));
        
        // TESTING: Uncomment baris di bawah untuk test (jalankan setiap menit)
        // Setelah test berhasil, comment lagi dan gunakan yang dailyAt
        // $schedule->command('debt:send-reminder')
        //     ->everyMinute()
        //     ->appendOutputTo(storage_path('logs/debt-reminder.log'));

        // ========================================
        // AUTO BACKUP DATABASE
        // ========================================
        
        // PRODUCTION: Backup database setiap hari jam 03:00 dini hari
        $schedule->command('backup:database')
            ->dailyAt('03:00')
            ->timezone('Asia/Makassar')
            ->appendOutputTo(storage_path('logs/backup.log'));
        
        // TESTING: Uncomment baris di bawah untuk test (jalankan setiap menit)
        // Setelah test berhasil, comment lagi dan gunakan yang dailyAt
        // $schedule->command('backup:database')
        //     ->everyMinute()
        //     ->appendOutputTo(storage_path('logs/backup.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}