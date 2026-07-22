<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Member;
use App\Models\Attendance;
use App\Models\Transaction; 
use App\Http\Controllers\FrontMemberController;
use App\Http\Controllers\ForgotPasswordController;
use Filament\Notifications\Notification;

/*
|--------------------------------------------------------------------------
| Web Routes - ARIFAH Gym Makassar
|--------------------------------------------------------------------------
*/

// ROUTE LOGIN ALIAS (untuk mencegah error "Route [login] not defined")
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// 1. HALAMAN UTAMA - Redirect ke admin login
Route::get('/', function () {
    return redirect('/admin');
})->name('home');

// 1.1 HALAMAN KASIR - Landing page untuk pilih menu absen atau registrasi
// Route::get('/kasir', function () {
//     return view('kasir');
// })->name('kasir');

// 1.2 FORGOT PASSWORD & RESET PASSWORD (KHUSUS ADMIN/OWNER) - OTP VIA WHATSAPP
Route::get('/forgot-password', [ForgotPasswordController::class, 'showOtpRequestForm'])->name('password.request.otp');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])->name('password.send.otp');
Route::get('/verify-otp', [ForgotPasswordController::class, 'showVerifyOtpForm'])->name('password.verify.otp.form');
Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->name('password.verify.otp');
Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.reset.submit');

// 2. PROSES PENDAFTARAN (DINONAKTIFKAN)
// Route::get('/daftar', function () {
//     $pakets = \App\Models\Paket::where('is_active', true)->get();
//     return view('daftar', compact('pakets'));
// });

// Route::post('/daftar', [FrontMemberController::class, 'store'])->name('member.register');

// 2.1 UPDATE METODE PEMBAYARAN
Route::post('/update-payment-method', [FrontMemberController::class, 'updatePaymentMethod'])->name('member.updatePaymentMethod');

// 3. PROSES ABSENSI
Route::get('/absen', function () {
    return view('absen');
});

// 3.1 HALAMAN SCAN QR ABSENSI
Route::get('/absen-qr', function () {
    return view('absen-qr');
});

// 3.2 API: PROSES ABSENSI VIA QR CODE
Route::post('/absen-qr', function (Request $request) {
    $memberId = $request->input('member_id');

    if (!$memberId || !is_numeric($memberId)) {
        return response()->json(['status' => 'error', 'message' => 'QR Code tidak valid.']);
    }

    $member = Member::find($memberId);

    if (!$member) {
        return response()->json(['status' => 'error', 'message' => 'Member tidak ditemukan.']);
    }

    if (!$member->is_active) {
        return response()->json(['status' => 'error', 'message' => 'Member Non-Aktif/Expired. Silakan perpanjang.']);
    }

    // Cek tanggal expired
    if ($member->expiry_date) {
        $today = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
        $expiryDate = \Carbon\Carbon::parse($member->expiry_date)->startOfDay();
        if ($today->equalTo($expiryDate)) {
            return response()->json(['status' => 'error', 'message' => "Membership {$member->name} berakhir hari ini. Silakan perpanjang."]);
        }
    }

    // Cek double absen
    $sudahAbsen = Attendance::where('member_id', $member->id)
        ->whereDate('created_at', now())
        ->exists();

    if ($sudahAbsen) {
        return response()->json(['status' => 'error', 'message' => "{$member->name} sudah absen hari ini."]);
    }

    // Catat absen
    Attendance::create(['member_id' => $member->id, 'created_at' => now()]);

    $now = \Carbon\Carbon::now('Asia/Makassar');
    $totalLatihanBulanIni = Attendance::where('member_id', $member->id)
        ->whereMonth('created_at', $now->month)
        ->whereYear('created_at', $now->year)
        ->count();

    $totalLatihanAllTime = Attendance::where('member_id', $member->id)->count();

    $badge = 'BEGINNER';
    if ($totalLatihanAllTime >= 100) $badge = 'GYM MASTER';
    elseif ($totalLatihanAllTime >= 50) $badge = 'ARIFAH WARRIOR';
    elseif ($totalLatihanAllTime >= 20) $badge = 'CONSISTENT';

    // Notifikasi admin
    try {
        $allAdmins = \App\Models\User::all();
        foreach ($allAdmins as $admin) {
            \Filament\Notifications\Notification::make()
                ->title('Member Absen (QR)')
                ->body("**{$member->name}** absen via QR. (Total: {$totalLatihanBulanIni}x bulan ini)")
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->sendToDatabase($admin);
        }
        \App\Helpers\WhatsAppHelper::sendAbsenNotification($member, $totalLatihanBulanIni, $badge);
        \App\Helpers\TelegramHelper::sendAbsenNotification($member, $totalLatihanBulanIni, $badge);
    } catch (\Exception $e) {
        \Log::warning('QR absen notification error: ' . $e->getMessage());
    }

    return response()->json([
        'status'        => 'success',
        'member_name'   => $member->name,
        'member_id'     => $member->id,
        'paket_nama'    => $member->type ?? 'MEMBER REGULAR',
        'total_latihan' => $totalLatihanBulanIni,
        'badge'         => $badge,
    ]);
});

Route::post('/absen', function (Request $request) {
    // 1. Bersihkan Nomor HP
    $cleanPhone = preg_replace('/[^0-9]/', '', $request->phone);
    if (str_starts_with($cleanPhone, '62')) {
        $cleanPhone = '0' . substr($cleanPhone, 2);
    }

    // 2. Cari Member
    $member = Member::where('phone', 'like', "%$cleanPhone%")->first();

    if (!$member) {
        return back()->with('error', 'Nomor tidak terdaftar! Silakan hubungi kasir.');
    }

    if (!$member->is_active) {
        return back()->with('error', 'Member Anda Non-Aktif/Expired. Silakan lapor ke kasir.');
    }

    // 2.5. Cek Apakah Hari Ini = Tanggal Expired (Tolak Absen di Hari Terakhir)
    if ($member->expiry_date) {
        $today = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
        $expiryDate = \Carbon\Carbon::parse($member->expiry_date)->startOfDay();
        
        if ($today->equalTo($expiryDate)) {
            return back()->with('error', "Maaf {$member->name}, membership Anda berakhir hari ini. Silakan perpanjang terlebih dahulu untuk bisa absen.");
        }
    }

    // 3. Cek Apakah Sudah Absen Hari Ini (Kecuali Tamu Harian)
    if ($member->name !== 'Tamu Harian') {
        $sudahAbsen = Attendance::where('member_id', $member->id)
                        ->whereDate('created_at', now())
                        ->exists();

        if ($sudahAbsen) {
            return back()->with('error', "Maaf {$member->name}, Anda sudah melakukan absen hari ini.");
        }
    }

    // 4. Catat Absen Baru
    Attendance::create([
        'member_id' => $member->id,
        'created_at' => now(),
    ]);

    // --- FITUR: HITUNG STATISTIK LATIHAN ---
    // 1. Total sesi BULAN INI (untuk ditampilkan di layar - reset per bulan)
    $bulanIni = \Carbon\Carbon::now('Asia/Makassar')->month;
    $tahunIni = \Carbon\Carbon::now('Asia/Makassar')->year;
    
    $totalLatihanBulanIni = Attendance::where('member_id', $member->id)
        ->whereMonth('created_at', $bulanIni)
        ->whereYear('created_at', $tahunIni)
        ->count();
    
    // 2. Total sesi ALL TIME (untuk badge - tidak reset)
    $totalLatihanAllTime = Attendance::where('member_id', $member->id)->count();

    // Tentukan Level Motivasi & Badge berdasarkan total ALL TIME
    $motivasi = 'Semangat! Perjalanan baru dimulai!';
    $badge = 'BEGINNER';
    
    if ($totalLatihanAllTime >= 100) {
        $motivasi = 'LEGEND! Anda adalah Gym Master!';
        $badge = 'GYM MASTER';
    } elseif ($totalLatihanAllTime >= 50) {
        $motivasi = 'Luar Biasa! Anda adalah Arifah Warrior!';
        $badge = 'ARIFAH WARRIOR';
    } elseif ($totalLatihanAllTime >= 20) {
        $motivasi = 'Konsistensi yang mantap! Keep going!';
        $badge = 'CONSISTENT';
    }

    // 5. Kirim Notifikasi ke Admin (Updated dengan Info Total Latihan)
    $allAdmins = \App\Models\User::all(); 
    foreach ($allAdmins as $admin) {
        // JALUR 1: Filament (Untuk memicu lonceng live)
        Notification::make()
            ->title('Member Absen Baru!')
            ->body("**{$member->name}** baru saja melakukan absensi. (Total: {$totalLatihanBulanIni}x bulan ini)")
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->sendToDatabase($admin);
            
    }

    // 6 & 7. Kirim Notifikasi WhatsApp & Telegram ke Owner (NON-BLOCKING)
    // Notifikasi berjalan dengan timeout sangat pendek agar tidak mengganggu response
    try {
        // WhatsApp Notification dengan timeout protection
        try {
            \App\Helpers\WhatsAppHelper::sendAbsenNotification($member, $totalLatihanBulanIni, $badge);
        } catch (\Exception $e) {
            \Log::warning('WhatsApp notification skipped: ' . $e->getMessage());
        }
        
        // Telegram Notification dengan timeout protection
        try {
            \App\Helpers\TelegramHelper::sendAbsenNotification($member, $totalLatihanBulanIni, $badge);
        } catch (\Exception $e) {
            \Log::warning('Telegram notification skipped: ' . $e->getMessage());
        }
    } catch (\Exception $e) {
        // Jika ada error apapun, log saja dan lanjutkan
        \Log::error('Notification error (non-blocking): ' . $e->getMessage());
    }

    // 8. Kembalikan Respon Sukses + Data Statistik ke View (PRIORITAS UTAMA)
    return back()->with([
        'success'      => true,
        'member_name'  => $member->name,
        'member_id'    => $member->id, // Kirim ID asli
        'order_id'     => $member->order_id ?? 'REG-' . str_pad($member->id, 5, '0', STR_PAD_LEFT), // INI DIA KUNCINYA!
        'paket_nama'   => $member->type ?? 'MEMBER REGULAR', // Sesuaikan dengan kolom 'type'
        'expiry_date'  => $member->expiry_date 
            ? \Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y') 
            : 'Member Harian',
        'total_latihan'=> $totalLatihanBulanIni, // Tampilkan total bulan ini
        'badge'        => $badge, // Badge berdasarkan all-time
        'motivasi'     => $motivasi
    ]);
});

// ========================================
// PROTECTED EXPORT ROUTES (ADMIN ONLY)
// ========================================
Route::middleware(['auth:web'])->group(function () {
    
    // 4. LAPORAN KEUANGAN MEMBER
    Route::get('/cetak-laporan', function (Request $request) {
    // PERMISSION: Hanya Super Admin yang bisa akses
    if (!auth()->user()->isSuperAdmin()) {
        abort(403, 'Unauthorized action.');
    }
    
    // FILTER: Hanya transaksi member reguler (bukan kasir cepat)
    $query = Transaction::with('member')
        ->where(function (Builder $query) {
            // Filter hanya berdasarkan guest_name, bukan relasi member
            $query->where('guest_name', '!=', 'Tamu Harian')
                  ->where('guest_name', '!=', 'Tamu Latihan Harian')
                  // ATAU jika member masih ada, filter berdasarkan member name
                  ->orWhereHas('member', function (Builder $subQuery) {
                      $subQuery->where('name', '!=', 'Tamu Harian')
                               ->where('name', '!=', 'Tamu Latihan Harian');
                  });
        });
    
    // FILTER TAMBAHAN DARI TABEL
    $additionalFilters = [];
    
    // Filter berdasarkan tipe paket (transaction.type)
    if ($request->query('paket_type')) {
        $paketType = $request->query('paket_type');
        
        // Untuk paket standar, gunakan LIKE yang lebih fleksibel
        if ($paketType === 'Member Harian') {
            $query->where('type', 'like', '%Harian%');
        } elseif ($paketType === 'Member 1 Bulan') {
            $query->where('type', 'like', '%1 Bulan%');
        } elseif ($paketType === 'Mingguan') {
            $query->where('type', 'like', '%Mingguan%');
        } else {
            // Untuk paket lain, gunakan LIKE dengan nama paket
            $query->where('type', 'like', "%{$paketType}%");
        }
        
        $additionalFilters[] = 'Paket: ' . $paketType;
    }
    
    // Filter transaksi bulan ini
    if ($request->query('this_month') === '1') {
        $query->whereMonth('payment_date', \Carbon\Carbon::now()->month)
              ->whereYear('payment_date', \Carbon\Carbon::now()->year);
        $additionalFilters[] = 'Bulan Ini';
    }
    
    // Filter data yang dihapus
    if ($request->query('trashed')) {
        $trashedValue = $request->query('trashed');
        if ($trashedValue === 'with') {
            $query->withTrashed();
            $additionalFilters[] = 'Termasuk Data Dihapus';
        } elseif ($trashedValue === 'only') {
            $query->onlyTrashed();
            $additionalFilters[] = 'Hanya Data Dihapus';
        }
        // Default (without) tidak perlu ditambahkan karena sudah default behavior
    }
    
    // FILTER TANGGAL
    $filterType = $request->query('filter_type');
    $dateFilterText = '';
    
    if ($filterType === 'single' && $request->query('single_date')) {
        $singleDate = $request->query('single_date');
        $query->whereDate('payment_date', $singleDate);
        $dateFilterText = ' - Tanggal: ' . \Carbon\Carbon::parse($singleDate)->format('d/m/Y');
    } elseif ($filterType === 'range' && $request->query('start_date') && $request->query('end_date')) {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $query->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $dateFilterText = ' - Periode: ' . \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
    }
    
    // Gabungkan filter tambahan ke dalam teks header
    $additionalFilterText = '';
    if (!empty($additionalFilters)) {
        $additionalFilterText = ' - Filter: ' . implode(', ', $additionalFilters);
    }
    
    $data = $query->orderBy('payment_date', 'asc')->get();
    
    $data->transform(function ($item) {
        $item->type = preg_replace('/[^\x20-\x7E]/', '', $item->type);
        $item->type = trim($item->type);
        return $item;
    });

    if ($request->query('format') == 'pdf') {
        return view('laporan_pdf', compact('data', 'dateFilterText', 'additionalFilterText'));
    }

    $filename = "Laporan_Keuangan_Member_ARIFAH_GYM_" . date('d-m-Y') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    $total = 0;
    $output = "<table border='1'>
                <tr>
                    <th colspan='10' style='background-color: #f97316; font-size: 16px; height: 35px; color: white;'>LAPORAN KEUANGAN MEMBER - ARIFAH GYM{$dateFilterText}{$additionalFilterText}</th>
                </tr>
                <tr style='background-color: #eeeeee;'>
                    <th>No</th>
                    <th>ID</th>
                    <th>Order ID</th>
                    <th>Tanggal Bayar</th>
                    <th>Nama Customer</th>
                    <th>Member ID</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Metode</th>
                    <th>Nominal</th>
                </tr>";
                
    $no = 1;
    foreach ($data as $row) {
        $total += $row->amount;
        $namaTampil = $row->member ? $row->member->name : ($row->guest_name ?? 'Umum/Tamu');
        $memberId = $row->member_id ?? '-';
        
        // Status dengan warna
        $statusText = ucfirst($row->status ?? 'paid');
        $statusColor = '#000000'; // Default hitam
        if ($row->status === 'completed' || $row->status === 'paid') {
            $statusColor = '#10b981'; // Hijau
        } elseif ($row->status === 'pending') {
            $statusColor = '#fbbf24'; // Kuning
        } elseif ($row->status === 'failed' || $row->status === 'refund') {
            $statusColor = '#ef4444'; // Merah
        }

        $output .= "<tr>
                        <td style='text-align: center;'>{$no}</td>
                        <td style='text-align: center;'>{$row->id}</td>
                        <td>{$row->order_id}</td>
                        <td>" . \Carbon\Carbon::parse($row->payment_date)->format('d/m/Y H:i') . "</td>
                        <td>" . $namaTampil . "</td>
                        <td style='text-align: center;'>" . $memberId . "</td>
                        <td>" . $row->type . "</td>
                        <td style='text-align: center; color: {$statusColor}; font-weight: bold;'>" . $statusText . "</td>
                        <td>" . $row->payment_method . "</td>
                        <td style='text-align: right;'>Rp " . number_format($row->amount, 0, ',', '.') . "</td>
                    </tr>";
        $no++;
    }
    
    $output .= "<tr>
                <th colspan='9' style='text-align:right; background-color: #eeeeee;'>TOTAL PENDAPATAN MEMBER:</th>
                <th style='background-color: #2ecc71; text-align: right;'>Rp " . number_format($total, 0, ',', '.') . "</th>
              </tr>";
    $output .= "</table>";

    return Response::make($output);
})->name('cetak-laporan');

// 4.1 LAPORAN KEUANGAN KASIR CEPAT
Route::get('/cetak-laporan-kasir', function (Request $request) {
    // PERMISSION: Hanya Super Admin yang bisa akses
    if (!auth()->user()->isSuperAdmin()) {
        abort(403, 'Unauthorized action.');
    }
    
    $query = \App\Models\QuickTransaction::query();
    
    // FILTER TAMBAHAN DARI TABEL
    $additionalFilters = [];
    
    // Filter berdasarkan status
    if ($request->query('status_filter')) {
        $statusFilter = $request->query('status_filter');
        $query->where('status', $statusFilter);
        $statusLabel = $statusFilter === 'paid' ? 'Lunas' : 'Belum Bayar';
        $additionalFilters[] = 'Status: ' . $statusLabel;
    }
    
    // Filter berdasarkan jenis produk
    if ($request->query('product_type')) {
        $productType = $request->query('product_type');
        $query->where('product_name', 'like', "%{$productType}%");
        $additionalFilters[] = 'Produk: ' . $productType;
    }
    
    // Filter transaksi bulan ini
    if ($request->query('this_month') === '1') {
        $query->whereMonth('payment_date', \Carbon\Carbon::now()->month)
              ->whereYear('payment_date', \Carbon\Carbon::now()->year);
        $additionalFilters[] = 'Bulan Ini';
    }
    
    // FILTER TANGGAL DARI FORM (prioritas lebih tinggi dari filter tabel)
    $filterType = $request->query('filter_type');
    $dateFilterText = '';
    
    if ($filterType === 'single' && $request->query('single_date')) {
        $singleDate = $request->query('single_date');
        $query->whereDate('payment_date', $singleDate);
        $dateFilterText = ' - Tanggal: ' . \Carbon\Carbon::parse($singleDate)->format('d/m/Y');
    } elseif ($filterType === 'range' && $request->query('start_date') && $request->query('end_date')) {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $query->whereBetween('payment_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $dateFilterText = ' - Periode: ' . \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
    }
    
    // Gabungkan filter tambahan ke dalam teks header
    $additionalFilterText = '';
    if (!empty($additionalFilters)) {
        $additionalFilterText = ' - Filter: ' . implode(', ', $additionalFilters);
    }
    
    $data = $query->orderBy('payment_date', 'asc')->get();
    
    $data->transform(function ($item) {
        $item->type = preg_replace('/[^\x20-\x7E]/', '', $item->type);
        $item->type = trim($item->type);
        return $item;
    });

    if ($request->query('format') == 'pdf') {
        return view('laporan_kasir_pdf', compact('data', 'dateFilterText', 'additionalFilterText'));
    }

    $filename = "Laporan_Kasir_Cepat_ARIFAH_GYM_" . date('d-m-Y') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    $total = 0;
    $output = "<table border='1'>
                <tr>
                    <th colspan='8' style='background-color: #f97316; font-size: 16px; height: 35px; color: white;'>LAPORAN KEUANGAN KASIR CEPAT - ARIFAH GYM{$dateFilterText}{$additionalFilterText}</th>
                </tr>
                <tr style='background-color: #eeeeee;'>
                    <th>No</th>
                    <th>ID</th>
                    <th>Order ID</th>
                    <th>Tanggal Bayar</th>
                    <th>Nama Tamu</th>
                    <th>Produk</th>
                    <th>Metode</th>
                    <th>Nominal</th>
                </tr>";
                
    $no = 1;
    foreach ($data as $row) {
        $total += $row->amount;
        
        $output .= "<tr>
                        <td style='text-align: center;'>{$no}</td>
                        <td style='text-align: center;'>{$row->id}</td>
                        <td>{$row->order_id}</td>
                        <td>" . \Carbon\Carbon::parse($row->payment_date)->format('d/m/Y H:i') . "</td>
                        <td>" . $row->guest_name . "</td>
                        <td>" . $row->product_name . "</td>
                        <td>" . $row->payment_method . "</td>
                        <td style='text-align: right;'>Rp " . number_format($row->amount, 0, ',', '.') . "</td>
                    </tr>";
        $no++;
    }
    
    $output .= "<tr>
                <th colspan='7' style='text-align:right; background-color: #eeeeee;'>TOTAL PENDAPATAN KASIR CEPAT:</th>
                <th style='background-color: #2ecc71; text-align: right;'>Rp " . number_format($total, 0, ',', '.') . "</th>
              </tr>";
    $output .= "</table>";

    return Response::make($output);
})->name('cetak-laporan-kasir');

// 4.2 LAPORAN PENGELUARAN
Route::get('/cetak-laporan-pengeluaran', function (Request $request) {
    // PERMISSION: Hanya Super Admin yang bisa akses
    if (!auth()->user()->isSuperAdmin()) {
        abort(403, 'Unauthorized action.');
    }
    
    $query = \App\Models\Expense::with('creator');
    
    // FILTER TAMBAHAN DARI TABEL
    $additionalFilters = [];
    
    // Filter berdasarkan kategori
    if ($request->query('category')) {
        $category = $request->query('category');
        $query->where('category', $category);
        $additionalFilters[] = 'Kategori: ' . $category;
    }
    
    // Filter pengeluaran bulan ini
    if ($request->query('this_month') === '1') {
        $query->whereMonth('expense_date', \Carbon\Carbon::now()->month)
              ->whereYear('expense_date', \Carbon\Carbon::now()->year);
        $additionalFilters[] = 'Bulan Ini';
    }
    
    // FILTER TANGGAL DARI FORM (prioritas lebih tinggi dari filter tabel)
    $filterType = $request->query('filter_type');
    $dateFilterText = '';
    
    if ($filterType === 'single' && $request->query('single_date')) {
        $singleDate = $request->query('single_date');
        $query->whereDate('expense_date', $singleDate);
        $dateFilterText = ' - Tanggal: ' . \Carbon\Carbon::parse($singleDate)->format('d/m/Y');
    } elseif ($filterType === 'range' && $request->query('start_date') && $request->query('end_date')) {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $query->whereBetween('expense_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $dateFilterText = ' - Periode: ' . \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
    }
    
    // Gabungkan filter tambahan ke dalam teks header
    $additionalFilterText = '';
    if (!empty($additionalFilters)) {
        $additionalFilterText = ' - Filter: ' . implode(', ', $additionalFilters);
    }
    
    $data = $query->orderBy('expense_date', 'asc')->get();

    if ($request->query('format') == 'pdf') {
        return view('laporan_pengeluaran_pdf', compact('data', 'dateFilterText', 'additionalFilterText'));
    }

    $filename = "Laporan_Pengeluaran_ARIFAH_GYM_" . date('d-m-Y') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    $total = 0;
    $output = "<table border='1'>
                <tr>
                    <th colspan='8' style='background-color: #f97316; font-size: 16px; height: 35px; color: white;'>LAPORAN PENGELUARAN - ARIFAH GYM{$dateFilterText}{$additionalFilterText}</th>
                </tr>
                <tr style='background-color: #eeeeee;'>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Item/Barang</th>
                    <th>Qty</th>
                    <th>Total Harga</th>
                    <th>No. Nota</th>
                    <th>Dicatat Oleh</th>
                </tr>";
                
    $no = 1;
    foreach ($data as $row) {
        $total += $row->amount;
        $creatorName = $row->creator ? $row->creator->name : 'User Dihapus';
        
        $output .= "<tr>
                        <td style='text-align: center;'>{$no}</td>
                        <td style='text-align: center;'>" . \Carbon\Carbon::parse($row->expense_date)->format('d/m/Y') . "</td>
                        <td>" . $row->category . "</td>
                        <td>" . $row->item . "</td>
                        <td style='text-align: center;'>" . $row->quantity . "</td>
                        <td style='text-align: right;'>Rp " . number_format($row->amount, 0, ',', '.') . "</td>
                        <td>" . ($row->receipt_number ?? '-') . "</td>
                        <td>" . $creatorName . "</td>
                    </tr>";
        $no++;
    }
    
    $output .= "<tr>
                <th colspan='7' style='text-align:right; background-color: #eeeeee;'>TOTAL PENGELUARAN:</th>
                <th style='background-color: #ef4444; color: white; text-align: right;'>Rp " . number_format($total, 0, ',', '.') . "</th>
              </tr>";
    $output .= "</table>";

    return Response::make($output);
})->name('cetak-laporan-pengeluaran');

// 5. EXPORT DAFTAR MEMBER
Route::get('/export-members', function (Request $request) {
    $data = Member::orderBy('created_at', 'desc')->get();

    $format = $request->query('format', 'excel');

    if ($format == 'pdf') {
        return view('members_pdf', compact('data'));
    }

    if ($format == 'csv') {
        $filename = "Daftar_Member_ARIFAH_GYM_" . date('d-m-Y') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header
            fputcsv($file, ['No', 'ID', 'Nama', 'NIK', 'Fingerprint', 'Email', 'WhatsApp', 'Tipe Member', 'Tanggal Bergabung', 'Tanggal Berakhir', 'Status']);
            
            $no = 1;
            foreach ($data as $row) {
                $joinDate = $row->join_date ? \Carbon\Carbon::parse($row->join_date)->format('d/m/Y') : '-';
                $expiryDate = $row->expiry_date ? \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') : '-';
                
                // Tentukan status
                $today = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
                if (!$row->is_active && !$row->expiry_date) {
                    $status = 'Pendaftar Baru';
                } elseif (!$row->is_active && $row->expiry_date) {
                    $expiry = \Carbon\Carbon::parse($row->expiry_date)->startOfDay();
                    $status = $today->gt($expiry) ? 'Masa Aktif Habis' : 'Non-Aktif';
                } elseif ($row->is_active) {
                    $status = 'Aktif';
                } else {
                    $status = 'Non-Aktif';
                }
                
                fputcsv($file, [
                    $no,
                    $row->id,
                    $row->name,
                    $row->nik ?? '-',
                    $row->fingerprint_id ?? '-',
                    $row->email,
                    $row->phone,
                    $row->type,
                    $joinDate,
                    $expiryDate,
                    $status
                ]);
                $no++;
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Excel format (default)
    $filename = "Daftar_Member_ARIFAH_GYM_" . date('d-m-Y') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    $output = "<table border='1'>
                <tr>
                    <th colspan='11' style='background-color: #f97316; font-size: 16px; height: 35px; color: white;'>DAFTAR MEMBER ARIFAH GYM</th>
                </tr>
                <tr style='background-color: #eeeeee;'>
                    <th>No</th>
                    <th>ID</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Fingerprint</th>
                    <th>Email</th>
                    <th>WhatsApp</th>
                    <th>Tipe Member</th>
                    <th>Tanggal Bergabung</th>
                    <th>Tanggal Berakhir</th>
                    <th>Status</th>
                </tr>";
                
    $no = 1;
    foreach ($data as $row) {
        $joinDate = $row->join_date ? \Carbon\Carbon::parse($row->join_date)->format('d/m/Y') : '-';
        $expiryDate = $row->expiry_date ? \Carbon\Carbon::parse($row->expiry_date)->format('d/m/Y') : '-';
        
        // Tentukan status
        $today = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
        if (!$row->is_active && !$row->expiry_date) {
            $status = 'Pendaftar Baru';
            $statusColor = '#fbbf24'; // Kuning
        } elseif (!$row->is_active && $row->expiry_date) {
            $expiry = \Carbon\Carbon::parse($row->expiry_date)->startOfDay();
            if ($today->gt($expiry)) {
                $status = 'Masa Aktif Habis';
                $statusColor = '#ef4444'; // Merah
            } else {
                $status = 'Non-Aktif';
                $statusColor = '#000000'; // Hitam
            }
        } elseif ($row->is_active) {
            $status = 'Aktif';
            $statusColor = '#10b981'; // Hijau
        } else {
            $status = 'Non-Aktif';
            $statusColor = '#000000'; // Hitam
        }

        $output .= "<tr>
                        <td style='text-align: center;'>{$no}</td>
                        <td style='text-align: center;'>{$row->id}</td>
                        <td>{$row->name}</td>
                        <td>" . ($row->nik ?? '-') . "</td>
                        <td style='text-align: center;'>" . ($row->fingerprint_id ?? '-') . "</td>
                        <td>{$row->email}</td>
                        <td>{$row->phone}</td>
                        <td>{$row->type}</td>
                        <td style='text-align: center;'>{$joinDate}</td>
                        <td style='text-align: center;'>{$expiryDate}</td>
                        <td style='text-align: center; color: {$statusColor}; font-weight: bold;'>{$status}</td>
                    </tr>";
        $no++;
    }
    
    $output .= "<tr>
                <th colspan='11' style='text-align:center; background-color: #eeeeee;'>TOTAL MEMBER: " . $data->count() . " orang</th>
              </tr>";
    $output .= "</table>";

    return Response::make($output);
})->name('export-members');

// 6. EXPORT LOG ABSENSI
Route::get('/export-attendance', function (Request $request) {
    $query = Attendance::with('member');
    
    // FILTER TANGGAL
    $filterType = $request->query('filter_type');
    $dateFilterText = '';
    
    if ($filterType === 'single' && $request->query('single_date')) {
        $singleDate = $request->query('single_date');
        $query->whereDate('created_at', $singleDate);
        $dateFilterText = ' - Tanggal: ' . \Carbon\Carbon::parse($singleDate)->format('d/m/Y');
    } elseif ($filterType === 'range' && $request->query('start_date') && $request->query('end_date')) {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $dateFilterText = ' - Periode: ' . \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
    }
    
    $data = $query->orderBy('created_at', 'asc')->get();

    if ($request->query('format') == 'pdf') {
        return view('attendance_pdf', compact('data', 'dateFilterText'));
    }

    $filename = "Log_Absensi_ARIFAH_GYM_" . date('d-m-Y') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    
    $output = "<table border='1'>
                <tr>
                    <th colspan='7' style='background-color: #f97316; font-size: 16px; height: 35px; color: white;'>LOG ABSENSI ARIFAH GYM{$dateFilterText}</th>
                </tr>
                <tr style='background-color: #eeeeee;'>
                    <th>No</th>
                    <th>Nama Member</th>
                    <th>Tipe Member</th>
                    <th>WhatsApp</th>
                    <th>Tanggal Absen</th>
                    <th>Jam Absen</th>
                    <th>Hari</th>
                </tr>";
                
    $no = 1;
    foreach ($data as $row) {
        $memberName = $row->member ? $row->member->name : 'Member Dihapus';
        $memberType = $row->member ? $row->member->type : '-';
        $memberPhone = $row->member ? $row->member->phone : '-';
        $tanggal = \Carbon\Carbon::parse($row->created_at)->format('d/m/Y');
        $jam = \Carbon\Carbon::parse($row->created_at)->format('H:i');
        $hari = \Carbon\Carbon::parse($row->created_at)->translatedFormat('l');

        $output .= "<tr>
                        <td style='text-align: center;'>{$no}</td>
                        <td>{$memberName}</td>
                        <td>{$memberType}</td>
                        <td>{$memberPhone}</td>
                        <td style='text-align: center;'>{$tanggal}</td>
                        <td style='text-align: center;'>{$jam}</td>
                        <td style='text-align: center;'>{$hari}</td>
                    </tr>";
        $no++;
    }
    
    $output .= "<tr>
                <th colspan='7' style='text-align:center; background-color: #eeeeee;'>TOTAL ABSENSI: " . $data->count() . " kali</th>
              </tr>";
    $output .= "</table>";

    return Response::make($output);
})->name('export-attendance');

// 7. BACKUP DATABASE
Route::get('/backup-database', function () {
    // PERMISSION: Hanya Super Admin yang bisa akses
    if (!auth()->user()->isSuperAdmin()) {
        abort(403, 'Unauthorized action.');
    }
    
    $dbName = config('database.connections.mysql.database');
    $dbUser = config('database.connections.mysql.username');
    $dbPass = config('database.connections.mysql.password');
    $dbHost = config('database.connections.mysql.host', '127.0.0.1');
    $filename = "backup_arifahgym_" . date('Y-m-d_His') . ".sql";
    $filePath = storage_path('app/' . $filename);
    
    // Deteksi OS dan set path mysqldump
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows (XAMPP)
        $mysqldumpPath = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
    } else {
        // Linux/Unix
        $mysqldumpPath = "mysqldump";
    }
    
    // Buat file konfigurasi sementara untuk credentials (lebih aman)
    $configFile = storage_path('app/.my.cnf.tmp');
    $configContent = "[client]\n";
    $configContent .= "user={$dbUser}\n";
    $configContent .= "password=\"{$dbPass}\"\n";
    $configContent .= "host={$dbHost}\n";
    file_put_contents($configFile, $configContent);
    chmod($configFile, 0600); // Set permission agar hanya owner yang bisa baca
    
    // Build command menggunakan config file
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $command = "\"{$mysqldumpPath}\" --defaults-extra-file=\"{$configFile}\" \"{$dbName}\" > \"{$filePath}\" 2>&1";
    } else {
        $command = "{$mysqldumpPath} --defaults-extra-file=\"{$configFile}\" \"{$dbName}\" > \"{$filePath}\" 2>&1";
    }
    
    // Execute command
    exec($command, $output, $returnVar);
    
    // Hapus file konfigurasi sementara
    if (file_exists($configFile)) {
        unlink($configFile);
    }
    
    // Check if backup was successful
    if (file_exists($filePath) && filesize($filePath) > 0) {
        // Cek apakah file berisi error message
        $firstLine = fgets(fopen($filePath, 'r'));
        if (strpos($firstLine, 'Usage:') !== false || strpos($firstLine, 'Error:') !== false) {
            // File berisi error, bukan backup
            $errorContent = file_get_contents($filePath);
            unlink($filePath);
            
            \Log::error('Backup database failed - mysqldump error', [
                'command' => str_replace($dbPass, '***', $command),
                'error_content' => $errorContent,
                'return_var' => $returnVar
            ]);
            
            return response()->json([
                'error' => 'Gagal melakukan backup database.',
                'message' => 'Terjadi error saat menjalankan mysqldump.',
                'debug' => config('app.debug') ? [
                    'error' => $errorContent,
                    'return_code' => $returnVar
                ] : null
            ], 500);
        }
        
        return response()->download($filePath)->deleteFileAfterSend(true);
    } else {
        // Log error untuk debugging
        \Log::error('Backup database failed - file not created', [
            'command' => str_replace($dbPass, '***', $command),
            'return_var' => $returnVar,
            'output' => $output,
            'file_exists' => file_exists($filePath),
            'file_size' => file_exists($filePath) ? filesize($filePath) : 0
        ]);
        
        return response()->json([
            'error' => 'Gagal melakukan backup database.',
            'message' => 'File backup tidak berhasil dibuat.',
            'debug' => config('app.debug') ? [
                'return_code' => $returnVar,
                'output' => $output
            ] : null
        ], 500);
    }
})->name('backup-database');

}); // End of auth middleware group

// 12. ROUTE UNTUK MELIHAT TANDA TANGAN DIGITAL
Route::get('/signature/{member}', function (Member $member) {
    if (!$member->digital_signature) {
        abort(404, 'Tanda tangan tidak ditemukan');
    }
    
    return view('signature-view', compact('member'));
})->name('member.signature');

// 13. ROUTE DOWNLOAD KARTU MEMBER
Route::get('/member-card/{member}', function (Member $member) {
    $now = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
    $expiredDate = \Carbon\Carbon::parse($member->expiry_date)->startOfDay();
    $isExpired = $expiredDate->lt($now) || !$member->is_active;

    // Fetch QR code server-side untuk menghindari CORS
    try {
        $qrResponse = \Illuminate\Support\Facades\Http::timeout(5)
            ->get("https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$member->id}");
        $qrBase64 = 'data:image/png;base64,' . base64_encode($qrResponse->body());
    } catch (\Exception $e) {
        $qrBase64 = '';
    }

    return view('member-card-download', compact('member', 'isExpired', 'qrBase64'));
})->middleware(['auth:web'])->name('member.card.download');

// Export Pembukuan PDF - PROTECTED
Route::middleware(['auth'])->get('/export/pembukuan', function (Request $request) {
    $period = $request->get('period', 'today');
    $now = \Carbon\Carbon::now('Asia/Makassar');
    
    // Tentukan range tanggal berdasarkan periode
    if ($period === 'single' && $request->has('date')) {
        // BARU: Support untuk tanggal spesifik dari link notifikasi
        $specificDate = \Carbon\Carbon::parse($request->get('date'), 'Asia/Makassar');
        $periodLabel = 'Tanggal ' . $specificDate->translatedFormat('d F Y');
        $data = \App\Models\CashFlow::whereDate('date', $specificDate->toDateString())
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        $startDate = $specificDate->copy()->startOfDay();
        $endDate = $specificDate->copy()->endOfDay();
    
    } elseif ($period === 'today') {
        $periodLabel = 'Hari Ini - ' . $now->format('d F Y');
        $data = \App\Models\CashFlow::whereDate('date', $now->toDateString())
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->endOfDay();
    
    } elseif ($period === 'week') {
        $periodLabel = 'Minggu Ini (7 Hari Terakhir)';
        $startDate = $now->copy()->subDays(6)->startOfDay();
        $endDate = $now->copy()->endOfDay();
        $data = \App\Models\CashFlow::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    
    } elseif ($period === 'month') {
        $periodLabel = 'Bulan ' . $now->translatedFormat('F Y');
        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfMonth();
        $data = \App\Models\CashFlow::whereMonth('date', $now->month)
            ->whereYear('date', $now->year)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
    } elseif (preg_match('/^\d{4}-\d{2}$/', $period)) {
        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $period . '-01', 'Asia/Makassar');
        $periodLabel = 'Bulan ' . $date->translatedFormat('F Y');
        $data = \App\Models\CashFlow::whereMonth('date', $date->month)
            ->whereYear('date', $date->year)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
            
    } else {
        $periodLabel = 'Hari Ini - ' . $now->format('d F Y');
        $data = \App\Models\CashFlow::whereDate('date', $now->toDateString())
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();
        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->endOfDay();
    }
    
    // Hitung totals
    $totalIncome = $data->where('type', 'income')->sum('amount');
    $totalExpense = $data->where('type', 'expense')->sum('amount');
    $finalBalance = $totalIncome - $totalExpense;
    
    // Hitung running balance untuk setiap record
    $runningBalance = 0;
    $dataWithBalance = $data->map(function ($record) use (&$runningBalance) {
        if ($record->type === 'income') {
            $runningBalance += $record->amount;
        } else {
            $runningBalance -= $record->amount;
        }
        $record->running_balance = $runningBalance;
        return $record;
    });
    
    // Set default startDate dan endDate jika belum ada
    if (!isset($startDate)) {
        $startDate = $data->first()->date ?? $now;
    }
    if (!isset($endDate)) {
        $endDate = $data->last()->date ?? $now;
    }
    
    // Jika format CSV
    if ($request->get('format') === 'csv') {
        $filename = 'Laporan_Arus_Kas_' . str_replace(' ', '_', $periodLabel) . '_' . date('d-m-Y') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($dataWithBalance) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($file, ['Tanggal', 'Tipe', 'Keterangan', 'Pemasukan', 'Pengeluaran', 'Saldo']);
            foreach ($dataWithBalance as $row) {
                fputcsv($file, [
                    \Carbon\Carbon::parse($row->date)->format('d/m/Y H:i'),
                    $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    $row->description ?? '-',
                    $row->type === 'income' ? $row->amount : 0,
                    $row->type === 'expense' ? $row->amount : 0,
                    $row->running_balance,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    return view('laporan_pembukuan_pdf', [
        'data' => $dataWithBalance,
        'periodLabel' => $periodLabel,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'totalIncome' => $totalIncome,
        'totalExpense' => $totalExpense,
        'finalBalance' => $finalBalance,
        'generatedAt' => $now->format('d F Y, H:i') . ' WITA'
    ]);
})->name('export.pembukuan');

// Data chart laporan arus kas
Route::get('/admin/laporan-arus-kas-data', function (\Illuminate\Http\Request $request) {
    $month = $request->get('month', \Carbon\Carbon::now()->month);
    $year  = $request->get('year',  \Carbon\Carbon::now()->year);

    $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $endDate   = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth();
    $now       = \Carbon\Carbon::now('Asia/Makassar');

    $pemasukan   = \App\Models\CashFlow::selectRaw('DATE(date) as tgl, SUM(amount) as total')
        ->whereMonth('date', $month)->whereYear('date', $year)->where('type', 'income')
        ->groupByRaw('DATE(date)')->pluck('total', 'tgl');

    $pengeluaran = \App\Models\CashFlow::selectRaw('DATE(date) as tgl, SUM(amount) as total')
        ->whereMonth('date', $month)->whereYear('date', $year)->where('type', 'expense')
        ->groupByRaw('DATE(date)')->pluck('total', 'tgl');

    $labels = []; $dataPemasukan = []; $dataPengeluaran = [];
    $current = $startDate->copy();
    while ($current <= $endDate) {
        $key = $current->format('Y-m-d');
        $isFuture = $current->gt($now);
        $labels[]          = $current->format('d');
        $dataPemasukan[]   = $isFuture ? null : ($pemasukan[$key] ?? 0);
        $dataPengeluaran[] = $isFuture ? null : ($pengeluaran[$key] ?? 0);
        $current->addDay();
    }

    return response()->json([
        'labels'      => $labels,
        'pemasukan'   => $dataPemasukan,
        'pengeluaran' => $dataPengeluaran,
    ]);
})->middleware('auth')->name('laporan.arus-kas.data');
