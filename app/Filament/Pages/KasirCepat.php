<?php

namespace App\Filament\Pages;

use App\Models\QuickTransaction; // Tabel terpisah untuk kasir cepat
use App\Models\Product;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class KasirCepat extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-list';
    protected static ?string $title = 'Kasir Penjualan';
    protected static ?string $navigationLabel = 'Kasir Penjualan';
    protected static string $view = 'filament.pages.kasir-cepat';

    // PERMISSION: Semua role bisa akses (ini untuk kasir)
    public static function canAccess(): bool
    {
        return true; // Super Admin, Admin, dan Staff bisa akses
    }

    // TAMBAHAN: Fungsi untuk mengirim data produk ke file Blade (tampilan)
    protected function getViewData(): array
    {
        // Ambil produk langsung tanpa cache (real-time)
        $products = Product::where('is_active', true)->get();
        
        // Ambil hutang yang belum lunas (status pending) untuk ditampilkan
        $unpaidDebts = QuickTransaction::pending()->orderBy('payment_date', 'desc')->get();
        
        return [
            'products' => $products,
            'unpaidDebts' => $unpaidDebts,
        ];
    }

    /**
     * SISTEM BARU: Transaksi Langsung 100% Tanpa Member Bayangan
     * Menggunakan tabel quick_transactions yang terpisah
     * TIDAK ADA absensi untuk kasir cepat (tamu harian tidak perlu tracking detail)
     */
    public function bayarHarian($productId, $paymentMethod = 'cash', $quantity = 1, $memberName = null)
    {
        // 1. Ambil data produk dari database berdasarkan ID yang diklik
        $product = Product::find($productId);

        if (!$product) {
            Notification::make()
                ->title('Produk tidak ditemukan!')
                ->danger()
                ->send();
            return;
        }

        // 2. Validasi quantity
        if ($quantity < 1) $quantity = 1;
        if ($quantity > $product->stock) {
            Notification::make()
                ->title('Stock Tidak Cukup!')
                ->body("Stock **{$product->name}** hanya tersisa **{$product->stock}** pcs. Tidak bisa menjual **{$quantity}** pcs.")
                ->danger()
                ->send();
            return;
        }

        // 3. CEK STOCK - Jika habis, tolak transaksi
        if ($product->stock <= 0) {
            Notification::make()
                ->title('Stock Habis!')
                ->body("Produk **{$product->name}** sudah habis. Silakan isi stock terlebih dahulu.")
                ->danger()
                ->send();
            return;
        }

        $unitPrice = $product->price;
        $totalAmount = $unitPrice * $quantity;
        $itemName = $product->name;
        $nominal = "Rp " . number_format($totalAmount, 0, ',', '.');

        // 4. KURANGI STOCK PRODUK sesuai quantity
        $product->decrement('stock', $quantity);
        
        // Clear cache dashboard agar pendapatan update langsung
        cache()->forget('stats_omset_hari_ini');
        cache()->forget('stats_total_omzet');

        // 5. Format metode pembayaran
        $paymentMethodLabel = match($paymentMethod) {
            'cash' => 'Transfer Bank',
            'transfer' => 'QRIS',
            default => 'Transfer Bank'
        };

        // 6. SIMPAN KE TABEL QUICK_TRANSACTIONS
        $guestName = $memberName 
            ? $memberName 
            : (str_contains(strtolower($itemName), 'latihan') || str_contains(strtolower($itemName), 'harian') 
                ? 'Tamu Latihan' 
                : 'Tamu Kantin');
        
        $quickTransaction = QuickTransaction::create([
            'guest_name'     => $guestName,
            'product_name'   => $quantity > 1 ? "{$itemName} ({$quantity}x)" : $itemName,
            'order_id'       => 'KASIR-' . date('YmdHis'),
            'amount'         => $totalAmount,
            'type'           => $itemName,
            'payment_method' => $paymentMethodLabel,
            'payment_date'   => now(),
        ]);

        // 6.1. CATAT KE CASH FLOW (SEPERTI MEMBER TRANSACTIONS)
        \App\Models\CashFlow::createEntry(
            'income',
            'kasir',
            'Penjualan - ' . ($quantity > 1 ? "{$itemName} ({$quantity}x)" : $itemName) . ' (' . $quickTransaction->guest_name . ')',
            $totalAmount,
            $quickTransaction->id,
            now()
        );

        // 7. KIRIM NOTIFIKASI KE DATABASE ADMIN
        $admins = User::all();
        $quantityText = $quantity > 1 ? " ({$quantity}x)" : "";
        $customerInfo = $memberName ? " oleh **{$memberName}**" : "";
        foreach ($admins as $admin) {
            Notification::make()
                ->title("Pembayaran {$itemName}")
                ->body("Kasir baru saja mencatat penjualan **{$itemName}{$quantityText}**{$customerInfo} seharga **{$nominal}** via **{$paymentMethodLabel}**. Stock tersisa: **{$product->stock}**")
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->sendToDatabase($admin);
        }

        // 8. KIRIM NOTIFIKASI TELEGRAM
        \App\Helpers\TelegramHelper::sendTransaksiKasir($quickTransaction);

        // 9. KIRIM NOTIFIKASI WHATSAPP KE OWNER
        \App\Helpers\WhatsAppHelper::sendQuickTransactionNotification($quickTransaction);

        // 10. Notifikasi melayang (Toast) di layar kasir
        $customerInfo = $memberName ? " oleh **{$memberName}**" : "";
        Notification::make()
            ->title('Transaksi Berhasil!')
            ->body("Pembayaran **{$itemName}{$quantityText}**{$customerInfo} sebesar **{$nominal}** via **{$paymentMethodLabel}** telah dicatat. Stock tersisa: **{$product->stock}**")
            ->success()
            ->send();
    }

    /**
     * Method untuk mencatat hutang baru
     */
    public function catatHutang($productId, $customerName, $phone, $quantity = 1, $notes = null)
    {
        // 1. Ambil data produk
        $product = Product::find($productId);

        if (!$product) {
            Notification::make()
                ->title('Produk tidak ditemukan!')
                ->danger()
                ->send();
            return;
        }

        // 2. Validasi quantity dan stock
        if ($quantity < 1) $quantity = 1;
        if ($quantity > $product->stock) {
            Notification::make()
                ->title('Stock Tidak Cukup!')
                ->body("Stock **{$product->name}** hanya tersisa **{$product->stock}** pcs.")
                ->danger()
                ->send();
            return;
        }

        if ($product->stock <= 0) {
            Notification::make()
                ->title('Stock Habis!')
                ->body("Produk **{$product->name}** sudah habis.")
                ->danger()
                ->send();
            return;
        }

        // 3. Validasi nama pelanggan
        if (empty(trim($customerName))) {
            Notification::make()
                ->title('Nama Pelanggan Wajib Diisi!')
                ->danger()
                ->send();
            return;
        }

        $unitPrice = $product->price;
        $totalAmount = $unitPrice * $quantity;

        // 4. KURANGI STOCK PRODUK
        $product->decrement('stock', $quantity);

        // 5. SIMPAN HUTANG KE QUICK_TRANSACTIONS dengan status PENDING
        $quickTransaction = QuickTransaction::create([
            'guest_name' => trim($customerName),
            'customer_phone' => $phone ? trim($phone) : null,
            'notes' => $notes ? trim($notes) : null,
            'product_name' => $quantity > 1 ? "Hutang: {$product->name} ({$quantity}x)" : "Hutang: {$product->name}",
            'order_id' => 'HUTANG-' . date('YmdHis'),
            'amount' => $totalAmount,
            'type' => 'Hutang: ' . $product->name,
            'payment_method' => 'Pending', // Belum ada pembayaran
            'payment_date' => now(),
            'status' => 'pending', // Status hutang
        ]);

        // 6. KIRIM NOTIFIKASI KE ADMIN
        $admins = User::all();
        $nominal = "Rp " . number_format($totalAmount, 0, ',', '.');
        $quantityText = $quantity > 1 ? " ({$quantity}x)" : "";
        
        foreach ($admins as $admin) {
            Notification::make()
                ->title("Hutang Baru Dicatat")
                ->body("Kasir mencatat hutang **{$customerName}** untuk **{$product->name}{$quantityText}** senilai **{$nominal}**. Stock tersisa: **{$product->stock}**")
                ->icon('heroicon-o-information-circle')
                ->iconColor('warning')
                ->sendToDatabase($admin);
        }

        // 7. Notifikasi sukses
        Notification::make()
            ->title('Hutang Berhasil Dicatat!')
            ->body("Hutang **{$customerName}** untuk **{$product->name}{$quantityText}** senilai **{$nominal}** telah dicatat.")
            ->success()
            ->send();

        // Clear cache
        cache()->forget('stats_omset_hari_ini');
        cache()->forget('stats_total_omzet');
    }

    /**
     * Method untuk mencatat hutang dengan harga custom (Lain-lain)
     */
    public function catatHutangCustom($customerName, $phone, $totalAmount, $notes = null)
    {
        // 1. Validasi nama pelanggan
        if (empty(trim($customerName))) {
            Notification::make()
                ->title('Nama Pelanggan Wajib Diisi!')
                ->danger()
                ->send();
            return;
        }

        // 2. Validasi total amount (pastikan numeric dan > 0)
        $totalAmount = filter_var($totalAmount, FILTER_VALIDATE_INT);
        if ($totalAmount === false || $totalAmount <= 0) {
            Notification::make()
                ->title('Total Hutang Tidak Valid!')
                ->body('Total hutang harus berupa angka dan lebih dari 0.')
                ->danger()
                ->send();
            return;
        }

        // 3. SIMPAN HUTANG KE QUICK_TRANSACTIONS dengan status PENDING
        $quickTransaction = QuickTransaction::create([
            'guest_name' => trim($customerName),
            'customer_phone' => $phone ? trim($phone) : null,
            'notes' => $notes ? trim($notes) : null,
            'product_name' => 'Hutang: Lain-lain',
            'order_id' => 'HUTANG-' . date('YmdHis'),
            'amount' => $totalAmount,
            'type' => 'Hutang: Lain-lain',
            'payment_method' => 'Pending', // Belum ada pembayaran
            'payment_date' => now(),
            'status' => 'pending', // Status hutang
        ]);

        // 4. KIRIM NOTIFIKASI KE ADMIN
        $admins = User::all();
        $nominal = "Rp " . number_format($totalAmount, 0, ',', '.');
        
        foreach ($admins as $admin) {
            Notification::make()
                ->title("Hutang Baru Dicatat")
                ->body("Kasir mencatat hutang **{$customerName}** untuk **Lain-lain** senilai **{$nominal}**.")
                ->icon('heroicon-o-information-circle')
                ->iconColor('warning')
                ->sendToDatabase($admin);
        }

        // 5. Notifikasi sukses
        Notification::make()
            ->title('Hutang Berhasil Dicatat!')
            ->body("Hutang **{$customerName}** untuk **Lain-lain** senilai **{$nominal}** telah dicatat.")
            ->success()
            ->send();

        // Clear cache
        cache()->forget('stats_omset_hari_ini');
        cache()->forget('stats_total_omzet');
    }

    /**
     * Method untuk mengambil data members untuk autocomplete
     */
    public function getMembersData()
    {
        $members = \App\Models\Member::select('name', 'phone')
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get()
            ->map(function($member) {
                return [
                    'name' => $member->name,
                    'phone' => $member->phone
                ];
            });

        return $members->toArray();
    }
    public function bayarHutang($transactionId, $paymentMethod = 'cash')
    {
        $transaction = QuickTransaction::find($transactionId);

        if (!$transaction) {
            Notification::make()
                ->title('Transaksi tidak ditemukan!')
                ->danger()
                ->send();
            return;
        }

        if ($transaction->status !== 'pending') {
            Notification::make()
                ->title('Transaksi sudah dibayar!')
                ->danger()
                ->send();
            return;
        }

        // Format metode pembayaran
        $paymentMethodLabel = match($paymentMethod) {
            'cash' => 'Transfer Bank',
            'transfer' => 'QRIS',
            default => 'Transfer Bank'
        };

        // Update status menjadi paid dan update payment_method
        $transaction->update([
            'status' => 'paid',
            'payment_method' => $paymentMethodLabel,
            'payment_date' => now(), // Update waktu pembayaran
        ]);

        // CATAT KE CASH FLOW KETIKA HUTANG DIBAYAR
        \App\Models\CashFlow::createEntry(
            'income',
            'kasir',
            'Pembayaran Hutang - ' . $transaction->product_name . ' (' . $transaction->guest_name . ')',
            $transaction->amount,
            $transaction->id,
            now()
        );

        // Notifikasi sukses
        $nominal = "Rp " . number_format($transaction->amount, 0, ',', '.');
        
        Notification::make()
            ->title('Pembayaran Hutang Berhasil!')
            ->body("Hutang **{$transaction->guest_name}** sebesar **{$nominal}** telah dibayar via **{$paymentMethodLabel}**.")
            ->success()
            ->send();

        // Kirim notifikasi ke admin
        $admins = User::all();
        foreach ($admins as $admin) {
            Notification::make()
                ->title("Pembayaran Hutang")
                ->body("**{$transaction->guest_name}** telah melunasi hutang **{$transaction->product_name}** sebesar **{$nominal}** via **{$paymentMethodLabel}**")
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->sendToDatabase($admin);
        }

        // Clear cache
        cache()->forget('stats_omset_hari_ini');
        cache()->forget('stats_total_omzet');
    }
}