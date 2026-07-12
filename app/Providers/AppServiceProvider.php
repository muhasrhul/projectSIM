<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\HtmlString;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // --- FITUR BYPASS TIME LIMIT (AGAR TIDAK ERROR 120 DETIK) ---
        if (app()->runningInConsole()) {
            // Jika sedang menjalankan Queue Worker di terminal, waktu tidak dibatasi
            set_time_limit(0);
        } else {
            // Jika akses web biasa, beri nafas 2 menit
            set_time_limit(120); 
        }
        // -----------------------------------------------------------

        Filament::serving(function () {
            // Load custom CSS untuk dark mode fixes dengan versioning
            Filament::registerStyles([
                asset('css/filament-dark-fix.css?v=' . time()),
            ]);

            Filament::registerScripts([
                asset('js/novalidate.js'),
            ]);
            
            // 1. DAFTAR NAVIGATION ITEMS
            Filament::registerNavigationItems([
                NavigationItem::make('Backup Data')
                    ->url(route('backup-database'))
                    ->icon('heroicon-o-database')
                    ->group('Sistem')
                    ->sort(10)
                    ->visible(fn () => auth()->check() && auth()->user()->isSuperAdmin()), // Hanya Super Admin
            ]);

            // 2. ATUR URUTAN GRUP NAVIGASI
            Filament::registerNavigationGroups([
                'Keuangan',
                'Master Data', 
                'Sistem',
                'Pengaturan',
            ]);

            // 3. STYLE CUSTOM UNTUK TAMPILAN LOGIN MODERN
            Filament::pushMeta([
                new HtmlString('
                <style>
                    /* Import Font */
                    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap");

                    /* Latar Belakang Modern dengan Gradient + Gambar Gym */
                    .filament-login-page {
                        background: linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.75)), 
                                    url("/images/bg-login.jpg?v=2") no-repeat center center fixed !important;
                        background-size: cover !important;
                        font-family: "Inter", sans-serif !important;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-height: 100vh;
                    }

                    /* FORCE DARK MODE UNTUK LOGIN PAGE - OVERRIDE SEMUA LIGHT MODE */
                    body:has(.filament-login-page),
                    html:has(.filament-login-page) {
                        background: #000000 !important;
                        color-scheme: dark !important;
                    }

                    /* Force dark class pada body saat di login page */
                    body:has(.filament-login-page) {
                        background: linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.75)), 
                                    url("/images/bg-login.jpg?v=2") no-repeat center center fixed !important;
                        background-size: cover !important;
                    }

                    /* Override light mode variables untuk login page */
                    .filament-login-page,
                    .filament-login-page * {
                        --tw-bg-opacity: 1 !important;
                        --tw-text-opacity: 1 !important;
                    }

                    /* Kartu Login Glassmorphism Modern */
                    .filament-login-page .filament-forms-card-component {
                        background: rgba(24, 24, 27, 0.8) !important;
                        backdrop-filter: blur(15px) !important;
                        -webkit-backdrop-filter: blur(15px) !important;
                        border: 1px solid rgba(255, 255, 255, 0.05) !important;
                        border-radius: 24px !important;
                        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5) !important;
                        padding: 2.5rem !important;
                        max-width: 420px !important;
                        width: 100% !important;
                    }

                    /* Judul Heading Login (MASUK) */
                    .filament-login-page h2 {
                        color: #ffffff !important;
                        font-weight: 900 !important;
                        font-style: italic !important;
                        letter-spacing: -1px !important;
                        text-align: center !important;
                        margin-bottom: 2rem !important;
                        font-size: 1.75rem !important;
                        text-transform: uppercase !important;
                    }

                    /* Label Input */
                    .filament-login-page label {
                        color: #a1a1aa !important;
                        font-weight: 700 !important;
                        font-size: 0.875rem !important;
                        text-transform: uppercase !important;
                        letter-spacing: 0.05em !important;
                        margin-bottom: 0.5rem !important;
                    }

                    /* Styling Input Field Modern */
                    .filament-login-page input {
                        background: rgba(255, 255, 255, 0.05) !important;
                        border: 1px solid rgba(255, 255, 255, 0.1) !important;
                        color: #ffffff !important;
                        border-radius: 12px !important;
                        padding: 0.75rem 1rem !important;
                        transition: all 0.3s ease !important;
                        font-size: 1rem !important;
                    }

                    .filament-login-page input::placeholder {
                        color: #52525b !important;
                    }

                    .filament-login-page input:focus {
                        border-color: #f97316 !important;
                        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2) !important;
                        background: rgba(255, 255, 255, 0.08) !important;
                        outline: none !important;
                    }

                    /* Checkbox "Ingat Saya" */
                    .filament-login-page input[type="checkbox"] {
                        width: 1.25rem !important;
                        height: 1.25rem !important;
                        border-radius: 6px !important;
                        cursor: pointer !important;
                    }

                    .filament-login-page input[type="checkbox"]:checked {
                        background-color: #f97316 !important;
                        border-color: #f97316 !important;
                    }

                    /* Icon mata toggle password */
                    .filament-login-page button[x-on\:click*="passwordIsRevealed"] svg,
                    .filament-login-page button[x-on\:click*="reveal"] svg,
                    .filament-login-page .filament-forms-password-component button svg,
                    .filament-login-page input[type="password"] ~ button svg,
                    .filament-login-page input[type="text"] ~ button svg {
                        color: #f97316 !important;
                        stroke: #f97316 !important;
                    }

                    /* Text "Ingat Saya" */
                    .filament-login-page .filament-forms-field-wrapper-label {
                        color: #d4d4d8 !important;
                    }

                    /* Tombol Submit Login Modern */
                    .filament-login-page button[type="submit"] {
                        background: linear-gradient(135deg, #f97316 0%, #f97316 100%) !important;
                        color: #ffffff !important;
                        border: none !important;
                        border-radius: 12px !important;
                        font-weight: 900 !important;
                        padding: 1rem !important;
                        text-transform: uppercase !important;
                        letter-spacing: 0.1em !important;
                        transition: all 0.3s ease !important;
                        width: 100% !important;
                        box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3) !important;
                        margin-top: 1.5rem !important;
                    }

                    .filament-login-page button[type="submit"]:hover {
                        transform: scale(1.02) !important;
                        box-shadow: 0 15px 35px rgba(249, 115, 22, 0.5) !important;
                    }

                    /* Link "Lupa Password" */
                    .filament-login-page a {
                        color: #0992C2 !important;
                        font-weight: 700 !important;
                        text-decoration: none !important;
                        transition: all 0.3s ease !important;
                    }

                    .filament-login-page a:hover {
                        color: #0bb5e8 !important;
                        text-decoration: underline !important;
                    }

                    /* Copyright Footer */
                    .filament-login-page .filament-footer {
                        color: #3f3f46 !important;
                        font-size: 0.75rem !important;
                        font-weight: 900 !important;
                        letter-spacing: 0.2em !important;
                        text-transform: uppercase !important;
                        text-align: center !important;
                        margin-top: 2rem !important;
                    }

                    /* =========================================
                       UPGRADE MODAL & NOTIFIKASI (LIGHT MEWAH)
                       ========================================= */

                    /* Jendela Modal (Putih Bersih) - LIGHT MODE */
                    .filament-modal-window {
                        background: #ffffff !important;
                        border-radius: 28px !important;
                        border: none !important;
                        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
                    }

                    /* Jendela Modal - DARK MODE */
                    .dark .filament-modal-window {
                        background: rgb(31 41 55) !important; /* gray-800 */
                        border: 1px solid rgb(55 65 81) !important; /* gray-700 */
                        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 10px 10px -5px rgba(0, 0, 0, 0.3) !important;
                    }

                    /* Judul Modal (Hitam Slate) - LIGHT MODE */
                    .filament-modal-heading {
                        color: #1e293b !important;
                        font-family: "Inter", sans-serif !important;
                        font-weight: 800 !important;
                    }

                    /* Judul Modal - DARK MODE */
                    .dark .filament-modal-heading {
                        color: rgb(229 231 235) !important; /* gray-200 */
                    }

                    /* Deskripsi Modal (Abu-abu Halus) - LIGHT MODE */
                    .filament-modal-description {
                        color: #64748b !important;
                        font-weight: 500 !important;
                    }

                    /* Deskripsi Modal - DARK MODE */
                    .dark .filament-modal-description {
                        color: rgb(156 163 175) !important; /* gray-400 */
                    }

                    /* Tombol Konfirmasi (Danger) Menjadi Orange Gold */
                    .filament-modal-actions button.bg-danger-600 {
                        background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%) !important;
                        color: #000000 !important;
                        border-radius: 14px !important;
                        font-weight: 700 !important;
                        text-transform: uppercase !important;
                        border: none !important;
                        letter-spacing: 1px !important;
                        box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2) !important;
                        transition: all 0.3s ease !important;
                    }

                    .filament-modal-actions button.bg-danger-600:hover {
                        transform: translateY(-1px) !important;
                        filter: brightness(1.1) !important;
                    }

                    /* Tombol Batal (Light Grey) */
                    .filament-modal-actions button.bg-white {
                        background: #f1f5f9 !important;
                        color: #475569 !important;
                        border: none !important;
                        border-radius: 14px !important;
                        font-weight: 600 !important;
                    }

                    /* Tombol Batal - DARK MODE */
                    .dark .filament-modal-actions button.bg-white,
                    .dark .filament-modal-actions button[class*="bg-white"] {
                        background: rgb(55 65 81) !important; /* gray-700 */
                        color: rgb(229 231 235) !important; /* gray-200 */
                    }

                    .dark .filament-modal-actions button.bg-white:hover,
                    .dark .filament-modal-actions button[class*="bg-white"]:hover {
                        background: rgb(75 85 99) !important; /* gray-600 */
                    }

                    /* Dropdown Action Menu */
                    .filament-dropdown-panel {
                        background: #ffffff !important;
                        border: 1px solid rgba(0, 0, 0, 0.05) !important;
                        border-radius: 12px !important;
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
                    }

                    .filament-dropdown-list-item:hover {
                        background: #fff7ed !important;
                    }

                    /* User Menu Dropdown - BACKGROUND TERANG UNTUK SEMUA MODE */
                    .filament-user-menu .filament-dropdown-panel,
                    [x-data*="userMenu"] .filament-dropdown-panel,
                    .dark .filament-user-menu .filament-dropdown-panel,
                    .dark [x-data*="userMenu"] .filament-dropdown-panel {
                        background: #ffffff !important;
                        border: 1px solid rgba(0, 0, 0, 0.1) !important;
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3) !important;
                    }

                    /* Force background putih untuk container dropdown */
                    .dark .filament-user-menu > div,
                    .dark [x-data*="userMenu"] > div,
                    .dark .filament-user-menu [role="menu"],
                    .dark [x-data*="userMenu"] [role="menu"] {
                        background: #ffffff !important;
                    }

                    /* Force background putih untuk semua child elements */
                    .dark .filament-user-menu *,
                    .dark [x-data*="userMenu"] * {
                        background-color: transparent !important;
                    }

                    .dark .filament-user-menu .filament-dropdown-panel,
                    .dark [x-data*="userMenu"] .filament-dropdown-panel {
                        background-color: #ffffff !important;
                    }

                    /* Hover background tetap light gray */
                    .dark .filament-user-menu .filament-dropdown-list-item:hover,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item:hover {
                        background-color: #f1f5f9 !important;
                    }

                    /* Font HITAM untuk semua mode - SEMUA ITEM */
                    .filament-user-menu .filament-dropdown-list-item,
                    .filament-user-menu .filament-dropdown-list-item span,
                    .filament-user-menu .filament-dropdown-list-item button,
                    .filament-user-menu .filament-dropdown-list-item button span,
                    .filament-user-menu .filament-dropdown-list-item a,
                    [x-data*="userMenu"] .filament-dropdown-list-item,
                    [x-data*="userMenu"] .filament-dropdown-list-item span,
                    [x-data*="userMenu"] .filament-dropdown-list-item button,
                    [x-data*="userMenu"] .filament-dropdown-list-item button span,
                    [x-data*="userMenu"] .filament-dropdown-list-item a,
                    .dark .filament-user-menu .filament-dropdown-list-item,
                    .dark .filament-user-menu .filament-dropdown-list-item span,
                    .dark .filament-user-menu .filament-dropdown-list-item button,
                    .dark .filament-user-menu .filament-dropdown-list-item button span,
                    .dark .filament-user-menu .filament-dropdown-list-item a,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item span,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item button,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item button span,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item a {
                        color: #1e293b !important;
                        font-weight: 600 !important;
                    }

                    /* Hover state */
                    .filament-user-menu .filament-dropdown-list-item:hover,
                    [x-data*="userMenu"] .filament-dropdown-list-item:hover,
                    .dark .filament-user-menu .filament-dropdown-list-item:hover,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item:hover {
                        background: #f1f5f9 !important;
                    }

                    .filament-user-menu .filament-dropdown-list-item:hover span,
                    .filament-user-menu .filament-dropdown-list-item:hover button,
                    .filament-user-menu .filament-dropdown-list-item:hover button span,
                    .filament-user-menu .filament-dropdown-list-item:hover a,
                    [x-data*="userMenu"] .filament-dropdown-list-item:hover span,
                    [x-data*="userMenu"] .filament-dropdown-list-item:hover button,
                    [x-data*="userMenu"] .filament-dropdown-list-item:hover button span,
                    [x-data*="userMenu"] .filament-dropdown-list-item:hover a,
                    .dark .filament-user-menu .filament-dropdown-list-item:hover span,
                    .dark .filament-user-menu .filament-dropdown-list-item:hover button,
                    .dark .filament-user-menu .filament-dropdown-list-item:hover button span,
                    .dark .filament-user-menu .filament-dropdown-list-item:hover a,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item:hover span,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item:hover button,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item:hover button span,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item:hover a {
                        color: #0992C2 !important;
                    }

                    /* User Name Header - HITAM */
                    .filament-user-menu .filament-dropdown-header,
                    .filament-user-menu .filament-dropdown-header span,
                    [x-data*="userMenu"] .filament-dropdown-header,
                    [x-data*="userMenu"] .filament-dropdown-header span,
                    .dark .filament-user-menu .filament-dropdown-header,
                    .dark .filament-user-menu .filament-dropdown-header span,
                    .dark [x-data*="userMenu"] .filament-dropdown-header,
                    .dark [x-data*="userMenu"] .filament-dropdown-header span {
                        color: #1e293b !important;
                        font-weight: 700 !important;
                    }

                    /* Nama User di Header - Paksa Hitam di DARK MODE */
                    .dark .filament-user-menu .filament-dropdown-header,
                    .dark .filament-user-menu .filament-dropdown-header *,
                    .dark [x-data*="userMenu"] .filament-dropdown-header,
                    .dark [x-data*="userMenu"] .filament-dropdown-header * {
                        color: #1e293b !important;
                    }

                    /* Avatar/Logo di header - Pastikan terlihat */
                    .dark .filament-user-menu .filament-dropdown-header img,
                    .dark [x-data*="userMenu"] .filament-dropdown-header img {
                        opacity: 1 !important;
                        filter: none !important;
                    }

                    /* Nama dan email user di header dropdown */
                    .dark .filament-user-menu .filament-dropdown-header .filament-dropdown-header-label,
                    .dark .filament-user-menu .filament-dropdown-header .filament-dropdown-header-description,
                    .dark [x-data*="userMenu"] .filament-dropdown-header .filament-dropdown-header-label,
                    .dark [x-data*="userMenu"] .filament-dropdown-header .filament-dropdown-header-description {
                        color: #1e293b !important;
                    }

                    /* Force semua text di header dropdown menjadi hitam */
                    .dark .filament-user-menu > div > div:first-child,
                    .dark .filament-user-menu > div > div:first-child *,
                    .dark [x-data*="userMenu"] > div > div:first-child,
                    .dark [x-data*="userMenu"] > div > div:first-child * {
                        color: #1e293b !important;
                    }

                    /* Icon colors - Abu-abu gelap untuk light mode, PUTIH untuk dark mode */
                    .filament-user-menu svg,
                    .filament-user-menu button svg,
                    .filament-user-menu a svg,
                    [x-data*="userMenu"] svg,
                    [x-data*="userMenu"] button svg,
                    [x-data*="userMenu"] a svg {
                        color: #64748b !important;
                        stroke: #64748b !important;
                    }

                    /* DARK MODE: Icon PUTIH untuk user menu */
                    .dark .filament-user-menu svg,
                    .dark .filament-user-menu button svg,
                    .dark .filament-user-menu a svg,
                    .dark .filament-user-menu .filament-dropdown-list-item svg,
                    .dark [x-data*="userMenu"] svg,
                    .dark [x-data*="userMenu"] button svg,
                    .dark [x-data*="userMenu"] a svg,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item svg {
                        color: #ffffff !important;
                        stroke: #ffffff !important;
                        fill: currentColor !important;
                    }

                    /* Icon hover state - Biru untuk SEMUA mode */
                    .filament-user-menu .filament-dropdown-list-item:hover svg,
                    .filament-user-menu .filament-dropdown-list-item:hover button svg,
                    .filament-user-menu .filament-dropdown-list-item:hover a svg,
                    [x-data*="userMenu"] .filament-dropdown-list-item:hover svg,
                    [x-data*="userMenu"] .filament-dropdown-list-item:hover button svg,
                    [x-data*="userMenu"] .filament-dropdown-list-item:hover a svg,
                    .dark .filament-user-menu .filament-dropdown-list-item:hover svg,
                    .dark .filament-user-menu .filament-dropdown-list-item:hover button svg,
                    .dark .filament-user-menu .filament-dropdown-list-item:hover a svg,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item:hover svg,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item:hover button svg,
                    .dark [x-data*="userMenu"] .filament-dropdown-list-item:hover a svg {
                        color: #0992C2 !important;
                        stroke: #0992C2 !important;
                    }

                    /* =========================================
                       SORT ICON - LEBIH KENTARA
                       ========================================= */
                    
                    /* Icon sort di header kolom */
                    .filament-tables-header-cell button svg {
                        width: 20px !important;
                        height: 20px !important;
                        color: #0992C2 !important;
                        stroke-width: 2.5 !important;
                    }

                    /* Hover state untuk sort button */
                    .filament-tables-header-cell button:hover svg {
                        color: #0992C2 !important;
                        transform: scale(1.2);
                        transition: all 0.2s ease;
                    }

                    /* Active sort (sedang digunakan) */
                    .filament-tables-header-cell button[class*="text-primary"] svg {
                        color: #0992C2 !important;
                        font-weight: bold !important;
                    }

                    /* Dark mode */
                    .dark .filament-tables-header-cell button svg {
                        color: #0992C2 !important;
                    }

                    .dark .filament-tables-header-cell button:hover svg {
                        color: #60d5ff !important;
                    }

                    /* =========================================
                       FIX DROPDOWN COLUMN TOGGLE - DARK MODE
                       ========================================= */
                    
                    /* Background dropdown column toggle di dark mode */
                    .dark .filament-tables-column-toggle-dropdown,
                    .dark [x-data*="toggleColumns"] > div,
                    .dark .filament-dropdown-panel {
                        background-color: rgb(31 41 55) !important; /* gray-800 */
                        border-color: rgb(55 65 81) !important; /* gray-700 */
                    }

                    /* Text color untuk item di dropdown */
                    .dark .filament-dropdown-list-item {
                        color: rgb(229 231 235) !important; /* gray-200 */
                    }

                    /* Hover state */
                    .dark .filament-dropdown-list-item:hover {
                        background-color: rgb(55 65 81) !important; /* gray-700 */
                    }

                    /* Checkbox label text */
                    .dark .filament-dropdown-list-item label,
                    .dark .filament-dropdown-list-item span {
                        color: rgb(229 231 235) !important; /* gray-200 */
                    }

                    /* Force semua dropdown di dark mode */
                    .dark [x-data*="dropdown"] > div:not(.filament-user-menu *) {
                        background-color: rgb(31 41 55) !important;
                        border-color: rgb(55 65 81) !important;
                    }

                    /* Force text color untuk dropdown items (kecuali user menu) */
                    .dark [x-data*="dropdown"] > div:not(.filament-user-menu *) .filament-dropdown-list-item,
                    .dark [x-data*="dropdown"] > div:not(.filament-user-menu *) .filament-dropdown-list-item * {
                        color: rgb(229 231 235) !important;
                    }

                    /* Force text color untuk semua elemen dropdown */
                    .dark [x-data] [role="menuitem"],
                    .dark [x-data] [role="menuitem"] *,
                    .dark .filament-dropdown-list-item *,
                    .dark button[x-on\\:click*="theme"] span {
                        color: #1e293b !important;
                    }

                    /* Animasi Modal Pop */
                    .filament-modal-window {
                        animation: modalPop 0.4s cubic-bezier(0.17, 0.89, 0.32, 1.49);
                    }

                    @keyframes modalPop {
                        from { transform: scale(0.95); opacity: 0; }
                        to { transform: scale(1); opacity: 1; }
                    }

                    /* Label Fingerprint ID - Bold - Paksa dengan text content */
                    .filament-forms-field-wrapper label {
                        font-weight: 400;
                    }
                    
                    .filament-forms-field-wrapper label:contains("Fingerprint ID") {
                        font-weight: 700 !important;
                    }
                    
                    /* Fallback: Semua label yang mengandung kata Fingerprint */
                    label {
                        font-weight: inherit;
                    }
                    
                    span.text-sm.font-medium:contains("Fingerprint") {
                        font-weight: 700 !important;
                    }

                    /* Sembunyikan tanda bintang required di semua form */
                    .filament-forms-field-wrapper-label sup,
                    .filament-forms-field-wrapper label sup,
                    label sup {
                        display: none !important;
                    }

                    /* =========================================
                       BOTTOM NAVIGATION BAR - MOBILE ONLY
                       ========================================= */
                    #mobile-bottom-nav {
                        display: none;
                    }

                    @media (max-width: 768px) {
                        #mobile-bottom-nav {
                            display: flex;
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            z-index: 9999;
                            background: #f9fafb;
                            border-top: 1px solid rgba(0,0,0,0.08);
                            padding: 8px 0 12px;
                            justify-content: space-around;
                            align-items: center;
                        }

                        .dark #mobile-bottom-nav {
                            background: #111827;
                            border-top: 1px solid rgba(255,255,255,0.06);
                        }

                        #mobile-bottom-nav a {
                            color: #6b7280;
                        }

                        .dark #mobile-bottom-nav a {
                            color: #71717a;
                        }

                        #mobile-bottom-nav a {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            gap: 3px;
                            text-decoration: none;
                            font-size: 10px;
                            font-weight: 600;
                            letter-spacing: 0.05em;
                            text-transform: uppercase;
                            padding: 4px 12px;
                            border-radius: 12px;
                            transition: all 0.2s ease;
                            flex: 1;
                        }

                        #mobile-bottom-nav a svg {
                            width: 22px;
                            height: 22px;
                            stroke: currentColor;
                        }

                        #mobile-bottom-nav a.active,
                        #mobile-bottom-nav a:hover {
                            color: #f97316;
                        }

                        #mobile-bottom-nav a.nav-kasir {
                            color: #ffffff;
                            background: #f97316;
                            border-radius: 16px;
                            padding: 8px 16px;
                            margin-bottom: 8px;
                            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
                        }

                        #mobile-bottom-nav a.nav-kasir svg {
                            stroke: #ffffff;
                        }

                        /* Tambah padding bawah konten agar tidak tertutup bottom nav */
                        .filament-page,
                        main {
                            padding-bottom: 80px !important;
                        }
                    }
                </style>
                
                <script>
                    // Inject bottom navigation bar
                    document.addEventListener("DOMContentLoaded", function() {
                        if (window.location.pathname.includes("/login")) return;

                        var nav = document.createElement("nav");
                        nav.id = "mobile-bottom-nav";
                        nav.innerHTML = `
                            <a href="/admin" class="nav-home">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                                Home
                            </a>
                            <a href="/admin/kasir-cepat-kantin" class="nav-kasir-link">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/></svg>
                                Kasir
                            </a>
                            <a href="/admin/members" class="nav-member nav-kasir">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                Member
                            </a>
                            <a href="/admin/log-absensi" class="nav-absen">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Absensi
                            </a>
                            <a href="/admin/pakets" class="nav-paket">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>
                                Paket
                            </a>
                        `;

                        document.body.appendChild(nav);

                        // Set active state
                        var path = window.location.pathname;
                        nav.querySelectorAll("a").forEach(function(a) {
                            if (a.getAttribute("href") === path || (path.startsWith(a.getAttribute("href")) && a.getAttribute("href") !== "/admin")) {
                                a.classList.add("active");
                            } else if (a.getAttribute("href") === "/admin" && path === "/admin") {
                                a.classList.add("active");
                            }
                        });

                    });
                </script>
                
                <script>
                    // JavaScript untuk memaksa bold pada label Fingerprint ID
                    document.addEventListener("DOMContentLoaded", function() {
                        function makeFingerprintBold() {
                            const labels = document.querySelectorAll("label, span.text-sm");
                            labels.forEach(label => {
                                if (label.textContent.includes("Fingerprint ID")) {
                                    label.style.fontWeight = "700";
                                    label.style.fontWeight = "bold";
                                }
                            });
                        }
                        
                        // Jalankan saat load
                        makeFingerprintBold();
                        
                        // Jalankan lagi setelah delay (untuk Livewire)
                        setTimeout(makeFingerprintBold, 500);
                        setTimeout(makeFingerprintBold, 1000);
                        
                        // Observer untuk perubahan DOM (Livewire updates)
                        const observer = new MutationObserver(makeFingerprintBold);
                        observer.observe(document.body, { childList: true, subtree: true });
                    });
                </script>
                
                <script>
                    // Auto reset SEMUA (sort + filter + search) saat refresh halaman
                    (function() {
                        // Fungsi untuk reset parameter tabel HANYA saat refresh (F5)
                        function resetTableParametersOnRefresh() {
                            // Cek apakah ini adalah refresh (F5) atau navigasi normal
                            const isRefresh = (
                                performance.navigation && performance.navigation.type === 1 || // TYPE_RELOAD
                                (performance.getEntriesByType && performance.getEntriesByType("navigation")[0] && performance.getEntriesByType("navigation")[0].type === "reload")
                            );
                            
                            // Jika bukan refresh, jangan lakukan apa-apa
                            if (!isRefresh) {
                                return;
                            }
                            
                            // Cek flag untuk mencegah infinite loop
                            if (sessionStorage.getItem("tableResetInProgress")) {
                                // Hapus flag setelah reload selesai
                                sessionStorage.removeItem("tableResetInProgress");
                                return;
                            }
                            
                            // Cek apakah ada parameter tabel di URL
                            const url = new URL(window.location.href);
                            const hasSort = url.searchParams.has("tableSort") || url.searchParams.has("tableSortDirection");
                            const hasSearch = url.searchParams.has("tableSearch");
                            
                            // Cek apakah ada filter (parameter yang dimulai dengan "tableFilters")
                            let hasFilter = false;
                            for (let key of url.searchParams.keys()) {
                                if (key.startsWith("tableFilters")) {
                                    hasFilter = true;
                                    break;
                                }
                            }
                            
                            // Jika ada sort ATAU filter ATAU search, reset SEMUA ke halaman bersih
                            if (hasSort || hasFilter || hasSearch) {
                                // Ambil base URL tanpa query parameters
                                const cleanUrl = url.origin + url.pathname;
                                
                                // Set flag untuk mencegah infinite loop
                                sessionStorage.setItem("tableResetInProgress", "true");
                                
                                // Redirect ke URL bersih (tanpa parameter apapun)
                                window.location.href = cleanUrl;
                            }
                        }
                        
                        // Jalankan saat DOM ready
                        if (document.readyState === "loading") {
                            document.addEventListener("DOMContentLoaded", resetTableParametersOnRefresh);
                        } else {
                            // DOM sudah ready, jalankan langsung
                            resetTableParametersOnRefresh();
                        }
                    })();
                </script>
                
                <script>
                    // FORCE DARK MODE UNTUK LOGIN PAGE
                    (function() {
                        function forceLoginDarkMode() {
                            // Cek apakah ini halaman login
                            if (window.location.pathname.includes(\'/login\')) {
                                // Force add dark class ke html dan body
                                document.documentElement.classList.add(\'dark\');
                                document.body.classList.add(\'dark\');
                                
                                // Set localStorage untuk memastikan dark mode
                                localStorage.setItem(\'theme\', \'dark\');
                                
                                // Override any light mode attempts
                                const observer = new MutationObserver(function(mutations) {
                                    mutations.forEach(function(mutation) {
                                        if (mutation.type === \'attributes\' && mutation.attributeName === \'class\') {
                                            if (window.location.pathname.includes(\'/login\')) {
                                                document.documentElement.classList.add(\'dark\');
                                                document.body.classList.add(\'dark\');
                                            }
                                        }
                                    });
                                });
                                
                                observer.observe(document.documentElement, {
                                    attributes: true,
                                    attributeFilter: [\'class\']
                                });
                                
                                observer.observe(document.body, {
                                    attributes: true,
                                    attributeFilter: [\'class\']
                                });
                            }
                        }
                        
                        // Jalankan segera
                        forceLoginDarkMode();
                        
                        // Jalankan saat DOM ready
                        if (document.readyState === \'loading\') {
                            document.addEventListener(\'DOMContentLoaded\', forceLoginDarkMode);
                        }
                        
                        // Jalankan saat window load
                        window.addEventListener(\'load\', forceLoginDarkMode);
                        
                        // Jalankan berkala untuk memastikan
                        setInterval(function() {
                            if (window.location.pathname.includes(\'/login\')) {
                                document.documentElement.classList.add(\'dark\');
                                document.body.classList.add(\'dark\');
                            }
                        }, 100);
                    })();
                </script>
                
                <?php if (false): // NONAKTIF - Inject "Lupa Password?" link di halaman login ?>
                <script>
                    (function() {
                        function addForgotPasswordLink() {
                            if (!window.location.pathname.includes("/login")) return;
                            if (document.querySelector(".forgot-password-link")) return;
                            const submitButton = document.querySelector(".filament-login-page button[type=submit]");
                            if (!submitButton) return;
                            const container = document.createElement("div");
                            container.className = "forgot-password-container";
                            container.style.cssText = "margin-top: 1.5rem; text-align: center; width: 100%;";
                            const link = document.createElement("a");
                            link.href = "/forgot-password";
                            link.className = "forgot-password-link";
                            link.style.cssText = "display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.875rem; font-weight: 600; color: #0992C2; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.5rem;";
                            link.appendChild(document.createTextNode("Lupa Password?"));
                            container.appendChild(link);
                            submitButton.parentElement.appendChild(container);
                        }
                        if (document.readyState === "loading") {
                            document.addEventListener("DOMContentLoaded", addForgotPasswordLink);
                        } else {
                            addForgotPasswordLink();
                        }
                        setTimeout(addForgotPasswordLink, 500);
                        setTimeout(addForgotPasswordLink, 1000);
                    })();
                </script>
                <?php endif; ?>

                <script>
                    // Toggle Show/Hide Password dengan Icon Mata
                    (function() {
                        function addPasswordToggle() {
                            // Cek apakah ini halaman login
                            if (!window.location.pathname.includes("/login")) {
                                return;
                            }
                            
                            // Cari input password
                            const passwordInput = document.querySelector(".filament-login-page input[type=password]");
                            if (!passwordInput || passwordInput.dataset.toggleAdded) {
                                return;
                            }
                            
                            // Tandai bahwa toggle sudah ditambahkan
                            passwordInput.dataset.toggleAdded = "true";
                            
                            // Buat wrapper untuk input + icon
                            const wrapper = document.createElement("div");
                            wrapper.style.cssText = "position: relative; width: 100%;";
                            
                            // Pindahkan input ke dalam wrapper
                            passwordInput.parentNode.insertBefore(wrapper, passwordInput);
                            wrapper.appendChild(passwordInput);
                            
                            // Tambah padding kanan pada input untuk icon
                            passwordInput.style.paddingRight = "3rem";
                            
                            // Buat button toggle
                            const toggleButton = document.createElement("button");
                            toggleButton.type = "button";
                            toggleButton.style.cssText = "position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; padding: 0.25rem; color: #a1a1aa; transition: color 0.2s ease;";
                            toggleButton.innerHTML = \'<svg class="eye-icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg><svg class="eye-slash-icon" style="display: none;" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>\';
                            
                            // Hover effect
                            toggleButton.addEventListener("mouseenter", function() {
                                this.style.color = "#0992C2";
                            });
                            toggleButton.addEventListener("mouseleave", function() {
                                this.style.color = "#a1a1aa";
                            });
                            
                            // Toggle password visibility
                            toggleButton.addEventListener("click", function() {
                                const eyeIcon = this.querySelector(".eye-icon");
                                const eyeSlashIcon = this.querySelector(".eye-slash-icon");
                                
                                if (passwordInput.type === "password") {
                                    passwordInput.type = "text";
                                    eyeIcon.style.display = "none";
                                    eyeSlashIcon.style.display = "block";
                                } else {
                                    passwordInput.type = "password";
                                    eyeIcon.style.display = "block";
                                    eyeSlashIcon.style.display = "none";
                                }
                            });
                            
                            // Tambahkan button ke wrapper
                            wrapper.appendChild(toggleButton);
                        }
                        
                        // Jalankan saat DOM ready
                        if (document.readyState === "loading") {
                            document.addEventListener("DOMContentLoaded", addPasswordToggle);
                        } else {
                            addPasswordToggle();
                        }
                        
                        // Jalankan lagi setelah delay (untuk Livewire)
                        setTimeout(addPasswordToggle, 500);
                        setTimeout(addPasswordToggle, 1000);
                        
                        // Livewire event listeners
                        document.addEventListener("livewire:load", function() {
                            setTimeout(addPasswordToggle, 100);
                        });
                        
                        document.addEventListener("livewire:update", function() {
                            setTimeout(addPasswordToggle, 100);
                        });
                        
                        // Observer untuk perubahan DOM (dengan debounce)
                        let timeoutId;
                        const observer = new MutationObserver(function() {
                            clearTimeout(timeoutId);
                            timeoutId = setTimeout(addPasswordToggle, 100);
                        });
                        
                        if (document.body) {
                            observer.observe(document.body, { childList: true, subtree: true });
                        }
                    })();
                </script>
                '),
            ]);
        });
    }
}