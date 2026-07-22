<x-filament::page>
    <style>
        .grid-kasir {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .btn-kasir {
            width: 100%;
            /* Sudut dibuat lebih premium (tidak terlalu bulat) */
            border-radius: 12px; 
            padding: 50px 30px;
            text-align: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        /* Efek Hover Premium */
        .btn-kasir:hover {
            transform: translateY(-8px);
            filter: brightness(1.1);
        }
        .btn-kasir:active {
            transform: translateY(-2px);
        }
        /* Warna Orange ARIFAH Gym Premium */
        .btn-orange {
            background: linear-gradient(135deg, #ff8c00 0%, #ff4500 100%);
            color: #000000;
            box-shadow: 0 10px 30px rgba(255, 69, 0, 0.3);
            border-bottom: 6px solid #b33000;
        }
        /* Warna Dark Premium (Glassmorphism style) */
        .btn-dark {
            background: linear-gradient(135deg, #2d2d30 0%, #1a1a1c 100%);
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border-bottom: 6px solid #000000;
        }
        .nama-produk {
            font-size: 1rem;
            font-weight: 800;
            text-transform: uppercase;
            display: block;
            margin-bottom: 5px;
            letter-spacing: 2px;
            opacity: 0.8;
        }
        .harga-produk {
            font-size: 5.5rem;
            font-weight: 900;
            display: block;
            line-height: 1;
            font-style: italic;
            letter-spacing: -2px;
        }
        .label-bawah {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-top: 20px;
            display: block;
            background: rgba(255,255,255,0.1);
            padding: 4px 10px;
            border-radius: 4px;
        }
        .btn-orange .label-bawah {
            background: rgba(0,0,0,0.1);
        }
        /* Stock Badge */
        .stock-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stock-habis {
            background: #ef4444;
            color: white;
        }
        .stock-menipis {
            background: #f59e0b;
            color: white;
        }
        .stock-aman {
            background: #10b981;
            color: white;
        }
        .btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
            filter: grayscale(50%);
        }

        /* DEBT SECTION RESPONSIVE THEME STYLES */
        .debt-section {
            /* Smooth transitions for theme switching */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Light mode default */
            --debt-bg-primary: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.1) 100%);
            --debt-bg-secondary: rgba(255,255,255,0.7);
            --debt-border-primary: rgba(255,255,255,0.2);
            --debt-border-secondary: rgba(255,255,255,0.3);
            --debt-text-primary: #1e293b;
            --debt-text-secondary: #64748b;
            --debt-text-title: linear-gradient(135deg, #1e293b, #475569);
            --debt-item-bg: linear-gradient(135deg, rgba(254, 243, 199, 0.8), rgba(253, 230, 138, 0.6));
            --debt-customer-color: #92400e;
            --debt-product-color: #a16207;
            --debt-phone-color: #78716c;
            --debt-amount-bg: rgba(245, 158, 11, 0.2);
            --debt-amount-color: inherit;
            --debt-empty-bg: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(241, 245, 249, 0.6));
            --debt-empty-border: rgba(148, 163, 184, 0.3);
            --debt-empty-icon-bg: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            --debt-empty-title-color: #475569;
            --debt-empty-subtitle-color: #64748b;
            --debt-decorative-1-opacity: 0.1;
            --debt-decorative-2-opacity: 0.1;
        }

        /* Smooth transitions for all debt section elements */
        .debt-section * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, opacity 0.3s ease;
        }

        /* Dark mode - System preference */
        @media (prefers-color-scheme: dark) {
            .debt-section {
                --debt-bg-primary: linear-gradient(135deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.6) 100%);
                --debt-bg-secondary: rgba(30, 41, 59, 0.6);
                --debt-border-primary: rgba(71, 85, 105, 0.3);
                --debt-border-secondary: rgba(71, 85, 105, 0.4);
                --debt-text-primary: #f1f5f9;
                --debt-text-secondary: #94a3b8;
                --debt-text-title: linear-gradient(135deg, #f1f5f9, #e2e8f0);
                --debt-item-bg: linear-gradient(135deg, rgba(45, 55, 72, 0.8), rgba(26, 32, 44, 0.6));
                --debt-customer-color: #fbbf24;
                --debt-product-color: #f59e0b;
                --debt-phone-color: #94a3b8;
                --debt-amount-bg: rgba(245, 158, 11, 0.3);
                --debt-amount-color: #fbbf24;
                --debt-empty-bg: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.4));
                --debt-empty-border: rgba(71, 85, 105, 0.4);
                --debt-empty-icon-bg: linear-gradient(135deg, #374151, #1f2937);
                --debt-empty-title-color: #e2e8f0;
                --debt-empty-subtitle-color: #94a3b8;
                --debt-decorative-1-opacity: 0.15;
                --debt-decorative-2-opacity: 0.15;
            }
        }

        /* Dark mode - Filament class (higher specificity) */
        .dark .debt-section {
            --debt-bg-primary: linear-gradient(135deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.6) 100%);
            --debt-bg-secondary: rgba(30, 41, 59, 0.6);
            --debt-border-primary: rgba(71, 85, 105, 0.3);
            --debt-border-secondary: rgba(71, 85, 105, 0.4);
            --debt-text-primary: #f1f5f9;
            --debt-text-secondary: #94a3b8;
            --debt-text-title: linear-gradient(135deg, #f1f5f9, #e2e8f0);
            --debt-item-bg: linear-gradient(135deg, rgba(45, 55, 72, 0.8), rgba(26, 32, 44, 0.6));
            --debt-customer-color: #fbbf24;
            --debt-product-color: #f59e0b;
            --debt-phone-color: #94a3b8;
            --debt-amount-bg: rgba(245, 158, 11, 0.3);
            --debt-amount-color: #fbbf24;
            --debt-empty-bg: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.4));
            --debt-empty-border: rgba(71, 85, 105, 0.4);
            --debt-empty-icon-bg: linear-gradient(135deg, #374151, #1f2937);
            --debt-empty-title-color: #e2e8f0;
            --debt-empty-subtitle-color: #94a3b8;
            --debt-decorative-1-opacity: 0.15;
            --debt-decorative-2-opacity: 0.15;
        }

        /* Light mode - Explicit override when NOT dark */
        html:not(.dark) .debt-section {
            --debt-bg-primary: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.1) 100%);
            --debt-bg-secondary: rgba(255,255,255,0.7);
            --debt-border-primary: rgba(255,255,255,0.2);
            --debt-border-secondary: rgba(255,255,255,0.3);
            --debt-text-primary: #1e293b;
            --debt-text-secondary: #64748b;
            --debt-text-title: linear-gradient(135deg, #1e293b, #475569);
            --debt-item-bg: linear-gradient(135deg, rgba(254, 243, 199, 0.8), rgba(253, 230, 138, 0.6));
            --debt-customer-color: #92400e;
            --debt-product-color: #a16207;
            --debt-phone-color: #78716c;
            --debt-amount-bg: rgba(245, 158, 11, 0.2);
            --debt-amount-color: inherit;
            --debt-empty-bg: linear-gradient(135deg, rgba(248, 250, 252, 0.8), rgba(241, 245, 249, 0.6));
            --debt-empty-border: rgba(148, 163, 184, 0.3);
            --debt-empty-icon-bg: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            --debt-empty-title-color: #475569;
            --debt-empty-subtitle-color: #64748b;
            --debt-decorative-1-opacity: 0.1;
            --debt-decorative-2-opacity: 0.1;
        }

        /* MOBILE RESPONSIVE STYLES FOR DEBT SECTION */
        @media (max-width: 768px) {
            .debt-section {
                margin-top: 20px !important;
            }
            
            /* Main container mobile adjustments */
            .debt-section > div {
                padding: 20px !important;
                border-radius: 16px !important;
            }
            
            /* Header section mobile */
            .debt-header {
                flex-direction: column !important;
                gap: 16px !important;
                align-items: flex-start !important;
                margin-bottom: 20px !important;
            }
            
            .debt-header-content {
                width: 100% !important;
            }
            
            .debt-header-content h2 {
                font-size: 1.4rem !important;
                margin-bottom: 4px !important;
            }
            
            .debt-header-content p {
                font-size: 0.85rem !important;
                line-height: 1.3 !important;
            }
            
            .debt-header-button {
                width: 100% !important;
                padding: 12px 20px !important;
                font-size: 0.9rem !important;
                text-align: center !important;
                margin-left: 0 !important;
            }
            
            /* Content area mobile */
            .debt-content {
                padding: 16px !important;
                border-radius: 16px !important;
            }
            
            .debt-content-header {
                margin-bottom: 16px !important;
            }
            
            .debt-content-header h3 {
                font-size: 1.05rem !important;
            }
            
            /* Debt items mobile */
            .debt-items-grid {
                gap: 12px !important;
            }
            
            .debt-item {
                flex-direction: column !important;
                align-items: flex-start !important;
                padding: 16px !important;
                gap: 12px !important;
            }
            
            .debt-item-content {
                width: 100% !important;
            }
            
            .debt-item-content > div:first-child {
                font-size: 1rem !important;
                margin-bottom: 4px !important;
            }
            
            .debt-item-content > div:nth-child(2) {
                font-size: 0.85rem !important;
                margin-bottom: 6px !important;
            }
            
            .debt-item-details {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 4px !important;
            }
            
            .debt-amount-badge {
                font-size: 0.8rem !important;
                padding: 4px 8px !important;
            }
            
            .debt-phone-info {
                font-size: 0.75rem !important;
            }
            
            .debt-item-actions {
                width: 100% !important;
                justify-content: flex-end !important;
            }
            
            .debt-pay-button {
                padding: 8px 16px !important;
                font-size: 0.8rem !important;
                min-width: 80px !important;
            }
            
            /* Empty state mobile */
            .debt-empty-state {
                padding: 24px 16px !important;
            }
            
            .debt-empty-title {
                font-size: 0.8rem !important;
                margin-bottom: 6px !important;
            }
            
            .debt-empty-subtitle {
                font-size: 0.7rem !important;
                line-height: 1.4 !important;
            }
            
            /* Link mobile */
            .debt-view-all-link {
                font-size: 0.85rem !important;
                padding: 6px 12px !important;
            }
        }

        /* EXTRA SMALL MOBILE (320px - 480px) */
        @media (max-width: 480px) {
            .debt-section > div {
                padding: 16px !important;
                margin: 0 -8px !important;
                border-radius: 12px !important;
            }
            
            .debt-header-content h2 {
                font-size: 1.25rem !important;
            }
            
            .debt-header-content p {
                font-size: 0.8rem !important;
            }
            
            .debt-header-button {
                padding: 10px 16px !important;
                font-size: 0.85rem !important;
            }
            
            .debt-content {
                padding: 12px !important;
            }
            
            .debt-item {
                padding: 12px !important;
            }
            
            .debt-item-content > div:first-child {
                font-size: 0.95rem !important;
            }
            
            .debt-empty-state {
                padding: 20px 12px !important;
            }
            
            .debt-empty-title {
                font-size: 0.75rem !important;
            }
            
            .debt-empty-subtitle {
                font-size: 0.65rem !important;
            }
        }

        /* LANDSCAPE MOBILE ADJUSTMENTS */
        @media (max-width: 768px) and (orientation: landscape) {
            .debt-header {
                flex-direction: row !important;
                align-items: center !important;
            }
            
            .debt-header-button {
                width: auto !important;
                min-width: 160px !important;
                margin-left: 20px !important;
            }
        }

        /* SYSTEM BRANDING RESPONSIVE STYLES */
        .system-branding {
            /* Light mode default */
            --branding-bg: #f4f4f5;
            --branding-border: #e4e4e7;
            --branding-text: #71717a;
        }

        /* Dark mode - System preference */
        @media (prefers-color-scheme: dark) {
            .system-branding {
                --branding-bg: rgba(30, 41, 59, 0.6);
                --branding-border: rgba(71, 85, 105, 0.4);
                --branding-text: #94a3b8;
            }
        }

        /* Dark mode - Filament class */
        .dark .system-branding {
            --branding-bg: rgba(30, 41, 59, 0.6);
            --branding-border: rgba(71, 85, 105, 0.4);
            --branding-text: #94a3b8;
        }

        /* Light mode - Explicit override */
        html:not(.dark) .system-branding {
            --branding-bg: #f4f4f5;
            --branding-border: #e4e4e7;
            --branding-text: #71717a;
        }

        /* Mobile responsive for branding */
        @media (max-width: 768px) {
            .system-branding {
                padding: 8px 20px !important;
                font-size: 0.65rem !important;
            }
        }

        @media (max-width: 480px) {
            .system-branding {
                padding: 6px 16px !important;
                font-size: 0.6rem !important;
                letter-spacing: 1px !important;
            }
        }

        .btn-disabled:hover {
            transform: none;
        }

        /* Modal Styles - Modern Design */
        .modal-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            background: rgba(0, 0, 0, 0.75) !important;
            backdrop-filter: blur(12px) !important;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 99999 !important;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-sizing: border-box !important;
        }
        .modal-content {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 0;
            max-width: min(380px, 90vw);
            max-height: 85vh;
            width: 90%;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            transform: scale(0.8) translateY(40px);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .modal-overlay.show {
            display: flex !important;
            opacity: 1 !important;
        }
        .modal-overlay.show .modal-content {
            transform: scale(1) translateY(0);
        }
        
        /* Force modal to cover everything */
        body.modal-open {
            overflow: hidden !important;
        }
        .modal-overlay.show {
            position: fixed !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            min-height: 100vh !important;
            min-width: 100vw !important;
        }

        /* MODAL DARK MODE STYLES */
        .modal-content {
            /* Light mode default */
            --modal-bg: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            --modal-text: #1f2937;
            --modal-label: #374151;
            --modal-input-bg: white;
            --modal-input-border: #e5e7eb;
            --modal-input-text: #1f2937;
            --modal-input-placeholder: #9ca3af;
            
            background: var(--modal-bg) !important;
        }

        /* Dark mode for modals */
        @media (prefers-color-scheme: dark) {
            .modal-content {
                --modal-bg: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.9));
                --modal-text: #f1f5f9;
                --modal-label: #e2e8f0;
                --modal-input-bg: rgba(30, 41, 59, 0.8);
                --modal-input-border: rgba(71, 85, 105, 0.6);
                --modal-input-text: #f1f5f9;
                --modal-input-placeholder: #94a3b8;
            }
        }

        .dark .modal-content {
            --modal-bg: linear-gradient(145deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.9));
            --modal-text: #f1f5f9;
            --modal-label: #e2e8f0;
            --modal-input-bg: rgba(30, 41, 59, 0.8);
            --modal-input-border: rgba(71, 85, 105, 0.6);
            --modal-input-text: #f1f5f9;
            --modal-input-placeholder: #94a3b8;
        }

        html:not(.dark) .modal-content {
            --modal-bg: linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.9));
            --modal-text: #1f2937;
            --modal-label: #374151;
            --modal-input-bg: white;
            --modal-input-border: #e5e7eb;
            --modal-input-text: #1f2937;
            --modal-input-placeholder: #9ca3af;
        }

        /* Form elements dark mode */
        .modal-form-label {
            color: var(--modal-label) !important;
        }

        .modal-form-input, .modal-form-select {
            background: var(--modal-input-bg) !important;
            border-color: var(--modal-input-border) !important;
            color: var(--modal-input-text) !important;
        }

        .modal-form-input::placeholder {
            color: var(--modal-input-placeholder) !important;
        }

        .modal-form-select option {
            background: var(--modal-input-bg) !important;
            color: var(--modal-input-text) !important;
        }

        /* CUSTOM DROPDOWN STYLES */
        .custom-dropdown {
            position: relative;
        }

        .dropdown-button:hover {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1) !important;
        }

        .dropdown-menu.show {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }

        .dropdown-option:hover {
            background: rgba(245, 158, 11, 0.1) !important;
            color: #f59e0b !important;
        }

        .dropdown-option:last-child {
            border-bottom: none !important;
        }

        /* Dark mode dropdown menu */
        @media (prefers-color-scheme: dark) {
            .dropdown-option:hover {
                background: rgba(245, 158, 11, 0.2) !important;
            }
        }

        .dark .dropdown-option:hover {
            background: rgba(245, 158, 11, 0.2) !important;
        }

        /* Scrollbar styling for dropdown */
        .dropdown-menu::-webkit-scrollbar {
            width: 6px;
        }

        .dropdown-menu::-webkit-scrollbar-track {
            background: transparent;
        }

        .dropdown-menu::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        .dropdown-menu::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.3);
        }

        /* Member suggestions styling */
        .member-suggestion {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--modal-input-text);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .member-suggestion:hover {
            background: rgba(245, 158, 11, 0.1) !important;
            color: #f59e0b !important;
        }

        .member-suggestion:last-child {
            border-bottom: none;
        }

        .member-name {
            font-weight: 600;
        }

        .member-phone {
            font-size: 0.85rem;
            opacity: 0.7;
        }

        /* Dark mode for member suggestions */
        @media (prefers-color-scheme: dark) {
            .member-suggestion:hover {
                background: rgba(245, 158, 11, 0.2) !important;
            }
        }

        .dark .member-suggestion:hover {
            background: rgba(245, 158, 11, 0.2) !important;
        }

        /* Member suggestions scrollbar - Hidden but scrollable */
        #memberSuggestionsPayment {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }

        #memberSuggestionsPayment::-webkit-scrollbar {
            display: none; /* Chrome/Safari/Opera */
        }

        /* Scroll indicators dengan gradient */
        #memberSuggestionsPayment::before {
            content: '';
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(to bottom, var(--modal-input-bg) 0%, transparent 100%);
            pointer-events: none;
            z-index: 1;
            display: block;
        }

        #memberSuggestionsPayment::after {
            content: '';
            position: sticky;
            bottom: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: linear-gradient(to top, var(--modal-input-bg) 0%, transparent 100%);
            pointer-events: none;
            z-index: 1;
            display: block;
        }

        .modal-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 16px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }
        .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }
        @keyframes shimmer {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }
        .modal-title {
            font-size: 1.2rem;
            font-weight: 900;
            margin-bottom: 4px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        .modal-subtitle {
            font-size: 0.7rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        .modal-body {
            padding: 16px 20px;
            max-height: calc(80vh - 180px);
            overflow-y: auto;
            flex: 1;
            min-height: 0;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE/Edge */
        }
        
        .modal-body::-webkit-scrollbar {
            display: none; /* Chrome/Safari/Opera */
        }
        .quantity-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        .qty-btn {
            width: 40px;
            height: 40px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: 900;
            color: #f59e0b;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
        }
        .qty-btn:hover {
            border-color: #f59e0b;
            background: linear-gradient(145deg, #fef3c7, #fde68a);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px -2px rgba(245, 158, 11, 0.2);
        }
        .qty-btn:active {
            transform: translateY(0);
        }
        #quantityInput {
            width: 80px;
            height: 40px;
            text-align: center;
            font-size: 1.4rem;
            font-weight: 900;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            color: #f59e0b;
            transition: all 0.3s ease;
        }
        #quantityInput:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.1);
        }
        .total-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
            border: 1px solid rgba(245, 158, 11, 0.1);
        }
        .total-label {
            font-size: 0.7rem;
            color: #f59e0b;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .total-amount {
            font-size: 1.8rem;
            font-weight: 900;
            color: #d97706;
            font-style: italic;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        #unitPrice {
            font-size: 1rem;
            font-weight: 700;
            color: #6b7280;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }
        .payment-btn {
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            background: linear-gradient(145deg, #ffffff, #f8fafc);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .payment-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        .payment-btn:hover::before {
            left: 100%;
        }
        .payment-btn:hover {
            border-color: #f59e0b;
            background: linear-gradient(145deg, #fef3c7, #fde68a);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px -4px rgba(245, 158, 11, 0.2);
        }
        .payment-btn.selected {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px -3px rgba(245, 158, 11, 0.4);
        }
        .payment-btn.selected:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px -5px rgba(245, 158, 11, 0.5);
        }

        /* PAYMENT BUTTONS DARK MODE */
        .payment-btn {
            /* Light mode default */
            --payment-bg: linear-gradient(145deg, #ffffff, #f8fafc);
            --payment-border: #e2e8f0;
            --payment-text: #374151;
            --payment-hover-bg: linear-gradient(145deg, #f0fdf4, #dcfce7);
            --payment-hover-border: #059669;
            
            background: var(--payment-bg) !important;
            border-color: var(--payment-border) !important;
            color: var(--payment-text) !important;
        }

        .payment-btn:hover {
            background: var(--payment-hover-bg) !important;
            border-color: var(--payment-hover-border) !important;
        }

        /* Dark mode for payment buttons */
        @media (prefers-color-scheme: dark) {
            .payment-btn {
                --payment-bg: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.6));
                --payment-border: rgba(71, 85, 105, 0.6);
                --payment-text: #e2e8f0;
                --payment-hover-bg: linear-gradient(145deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.1));
                --payment-hover-border: #f59e0b;
            }
        }

        .dark .payment-btn {
            --payment-bg: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.6));
            --payment-border: rgba(71, 85, 105, 0.6);
            --payment-text: #e2e8f0;
            --payment-hover-bg: linear-gradient(145deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.1));
            --payment-hover-border: #f59e0b;
        }

        html:not(.dark) .payment-btn {
            --payment-bg: linear-gradient(145deg, #ffffff, #f8fafc);
            --payment-border: #e2e8f0;
            --payment-text: #374151;
            --payment-hover-bg: linear-gradient(145deg, #fef3c7, #fde68a);
            --payment-hover-border: #f59e0b;
        }

        /* Selected state remains the same (orange) for both modes */
        .payment-btn.selected {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
            border-color: #f59e0b !important;
            color: white !important;
        }

        .payment-icon {
            font-size: 1.4rem;
            margin-bottom: 6px;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
        }
        .payment-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .modal-footer {
            display: flex;
            gap: 12px;
            padding: 0 20px 20px 20px;
            flex-shrink: 0;
        }
        .btn-modal {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-transform: uppercase;
            letter-spacing: 0.6px;
            position: relative;
            overflow: hidden;
        }
        .btn-modal::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }
        .btn-modal:active::before {
            width: 300px;
            height: 300px;
        }
        .btn-cancel {
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            color: #374151;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-cancel:hover {
            background: linear-gradient(135deg, #e5e7eb, #d1d5db);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.15);
        }
        .btn-confirm {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.3);
        }
        .btn-confirm:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px -4px rgba(245, 158, 11, 0.4);
        }
    </style>

    <div class="grid-kasir">
        @foreach($products as $product)
            <button 
                onclick="showPaymentModal({{ $product->id }}, '{{ $product->name }}', {{ $product->price }}, {{ $product->stock }})"
                class="btn-kasir {{ $product->color === 'orange' ? 'btn-orange' : 'btn-dark' }} {{ $product->stock <= 0 ? 'btn-disabled' : '' }}"
                {{ $product->stock <= 0 ? 'disabled' : '' }}
            >
                <!-- Stock Badge -->
                @if($product->stock <= 0)
                    <span class="stock-badge stock-habis">HABIS</span>
                @elseif($product->stock <= 5)
                    <span class="stock-badge stock-menipis">{{ $product->stock }} pcs</span>
                @else
                    <span class="stock-badge stock-aman">{{ $product->stock }} pcs</span>
                @endif

                <span class="nama-produk">
                    {{ $product->name }}
                </span>
                
                <span class="harga-produk">
                    {{ number_format($product->price / 1000, 0) }}K
                </span>
                
                <span class="label-bawah">
                    {{ $product->stock <= 0 ? 'Stock Habis' : 'Klik untuk Bayar' }}
                </span>
            </button>
        @endforeach
    </div>

    <!-- SECTION PENCATATAN HUTANG - PREMIUM DESIGN WITH DARK MODE -->
    <div class="debt-section" style="margin-top: 40px; padding: 0; position: relative;">
        <!-- Background dengan glassmorphism effect -->
        <div style="
            background: var(--debt-bg-primary);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid var(--debt-border-primary);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1), 0 0 0 1px rgba(255,255,255,0.05);
            padding: 32px;
            position: relative;
            overflow: hidden;
        ">
            <!-- Decorative elements -->
            <div style="
                position: absolute;
                top: -50px;
                right: -50px;
                width: 100px;
                height: 100px;
                background: linear-gradient(45deg, #f59e0b, #d97706);
                border-radius: 50%;
                opacity: var(--debt-decorative-1-opacity);
                filter: blur(20px);
            "></div>
            
            <div style="
                position: absolute;
                bottom: -30px;
                left: -30px;
                width: 60px;
                height: 60px;
                background: linear-gradient(45deg, #3b82f6, #1d4ed8);
                border-radius: 50%;
                opacity: var(--debt-decorative-2-opacity);
                filter: blur(15px);
            "></div>

            <!-- Header Section -->
            <div class="debt-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; position: relative; z-index: 2;">
                <div class="debt-header-content" style="flex: 1;">
                    <h2 style="
                        font-size: 1.75rem;
                        font-weight: 900;
                        background: var(--debt-text-title);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        margin: 0;
                        margin-bottom: 6px;
                        letter-spacing: -0.025em;
                        line-height: 1.2;
                    ">Pencatatan Hutang</h2>
                    <p style="
                        font-size: 0.95rem;
                        color: var(--debt-text-secondary);
                        margin: 0;
                        font-weight: 500;
                        line-height: 1.4;
                    ">Kelola pembelian yang belum dibayar lunas dengan mudah</p>
                </div>
                
                <!-- Premium Button -->
                <button class="debt-header-button" onclick="showDebtModal()" style="
                    padding: 14px 28px;
                    background: linear-gradient(135deg, #f59e0b, #d97706);
                    color: white;
                    border: none;
                    border-radius: 16px;
                    font-weight: 700;
                    font-size: 0.95rem;
                    cursor: pointer;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.3);
                    position: relative;
                    overflow: hidden;
                    flex-shrink: 0;
                    margin-left: 20px;
                " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 32px rgba(245, 158, 11, 0.4)'" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 24px rgba(245, 158, 11, 0.3)'">
                    <span style="position: relative; z-index: 2;">+ Catat Hutang Baru</span>
                    <!-- Button shine effect -->
                    <div style="
                        position: absolute;
                        top: 0;
                        left: -100%;
                        width: 100%;
                        height: 100%;
                        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
                        transition: left 0.5s;
                    "></div>
                </button>
            </div>

            <!-- Daftar Hutang Belum Lunas -->
            <div class="debt-content" style="
                background: var(--debt-bg-secondary);
                backdrop-filter: blur(10px);
                border-radius: 20px;
                padding: 24px;
                border: 1px solid var(--debt-border-secondary);
                box-shadow: 0 8px 32px rgba(0,0,0,0.1);
                position: relative;
                z-index: 2;
            ">
                <div class="debt-content-header" style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                    <div style="
                        width: 8px;
                        height: 8px;
                        background: linear-gradient(45deg, #f59e0b, #d97706);
                        border-radius: 50%;
                        box-shadow: 0 0 12px rgba(245, 158, 11, 0.5);
                    "></div>
                    <h3 style="
                        font-size: 1.2rem;
                        font-weight: 800;
                        color: var(--debt-text-primary);
                        margin: 0;
                        letter-spacing: -0.025em;
                    ">Daftar Hutang Belum Lunas</h3>
                </div>
                
                @if($unpaidDebts->count() > 0)
                    <div class="debt-items-grid" style="display: grid; gap: 16px;">
                        @foreach($unpaidDebts as $debt)
                            <div class="debt-item" style="
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                padding: 20px;
                                background: var(--debt-item-bg);
                                backdrop-filter: blur(10px);
                                border-radius: 16px;
                                border: 1px solid rgba(245, 158, 11, 0.2);
                                border-left: 4px solid #f59e0b;
                                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                                position: relative;
                                overflow: hidden;
                            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 32px rgba(245, 158, 11, 0.2)'" 
                               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                                
                                <!-- Subtle pattern overlay -->
                                <div style="
                                    position: absolute;
                                    top: 0;
                                    right: 0;
                                    width: 60px;
                                    height: 60px;
                                    background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, transparent 70%);
                                "></div>
                                
                                <div class="debt-item-content" style="flex: 1; position: relative; z-index: 2;">
                                    <div style="
                                        font-weight: 800;
                                        color: var(--debt-customer-color);
                                        margin-bottom: 6px;
                                        font-size: 1.05rem;
                                    ">{{ $debt->guest_name }}</div>
                                    <div style="
                                        font-size: 0.9rem;
                                        color: var(--debt-product-color);
                                        margin-bottom: 4px;
                                        font-weight: 600;
                                    ">{{ $debt->product_name }}</div>
                                    <div class="debt-item-details" style="
                                        font-size: 0.85rem;
                                        color: var(--debt-product-color);
                                        display: flex;
                                        align-items: center;
                                        gap: 8px;
                                        flex-wrap: wrap;
                                    ">
                                        <span class="debt-amount-badge" style="
                                            background: var(--debt-amount-bg);
                                            color: var(--debt-amount-color);
                                            padding: 2px 8px;
                                            border-radius: 6px;
                                            font-weight: 700;
                                        ">Rp {{ number_format($debt->amount, 0, ',', '.') }}</span>
                                        @if($debt->customer_phone)
                                            <span class="debt-phone-info" style="color: var(--debt-phone-color);">• {{ $debt->customer_phone }}</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="debt-item-actions" style="display: flex; gap: 10px; position: relative; z-index: 2;">
                                    <button class="debt-pay-button" onclick="showPayDebtModal({{ $debt->id }}, '{{ $debt->guest_name }}', {{ $debt->amount }})" 
                                            style="
                                                padding: 10px 20px;
                                                background: linear-gradient(135deg, #f59e0b, #d97706);
                                                color: white;
                                                border: none;
                                                border-radius: 12px;
                                                font-size: 0.85rem;
                                                font-weight: 700;
                                                cursor: pointer;
                                                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                                                box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
                                            " onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 6px 16px rgba(245, 158, 11, 0.4)'" 
                                               onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 4px 12px rgba(245, 158, 11, 0.3)'">
                                        ✓ Bayar
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a class="debt-view-all-link" href="/admin/quick-transactions" style="
                            color: #f59e0b;
                            font-size: 0.95rem;
                            font-weight: 700;
                            text-decoration: none;
                            padding: 8px 16px;
                            border-radius: 8px;
                            transition: all 0.3s ease;
                            display: inline-block;
                        " onmouseover="this.style.background='rgba(245, 158, 11, 0.1)'" 
                           onmouseout="this.style.background='transparent'">
                            Lihat Semua Transaksi →
                        </a>
                    </div>
                @else
                    <div class="debt-empty-state" style="
                        text-align: center;
                        padding: 40px 20px;
                        color: var(--debt-text-secondary);
                        background: var(--debt-empty-bg);
                        border-radius: 16px;
                        border: 2px dashed var(--debt-empty-border);
                    ">
                        <div class="debt-empty-title" style="
                            font-weight: 700;
                            font-size: 1.1rem;
                            margin-bottom: 8px;
                            color: var(--debt-empty-title-color);
                        ">Tidak ada hutang yang belum lunas</div>
                        <div class="debt-empty-subtitle" style="
                            font-size: 0.95rem;
                            color: var(--debt-empty-subtitle-color);
                            font-weight: 500;
                        ">Semua pelanggan sudah melunasi hutangnya dengan baik</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Catat Hutang Baru -->
    <div id="debtModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Catat Hutang Baru</h3>
                <p class="modal-subtitle">Isi data pelanggan dan produk</p>
            </div>
            
            <div class="modal-body">
                <!-- Pilih Produk -->
                <div style="margin-bottom: 20px;">
                    <label class="modal-form-label" style="display: block; font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">Pilih Produk</label>
                    
                    <!-- Custom Dropdown -->
                    <div class="custom-dropdown" style="position: relative;">
                        <div id="dropdownButton" class="dropdown-button modal-form-select" style="
                            width: 100%; 
                            padding: 12px 16px; 
                            border: 2px solid; 
                            border-radius: 12px; 
                            font-size: 1rem;
                            cursor: pointer;
                            display: flex;
                            justify-content: space-between;
                            align-items: center;
                            transition: all 0.3s ease;
                        " onclick="toggleDropdown()">
                            <span id="selectedProduct">-- Pilih Produk --</span>
                            <svg id="dropdownArrow" style="
                                width: 20px; 
                                height: 20px; 
                                transition: transform 0.3s ease;
                                opacity: 0.6;
                            " fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        
                        <div id="dropdownMenu" class="dropdown-menu" style="
                            position: absolute;
                            top: 100%;
                            left: 0;
                            right: 0;
                            background: var(--modal-input-bg);
                            border: 2px solid var(--modal-input-border);
                            border-radius: 12px;
                            margin-top: 4px;
                            max-height: 200px;
                            overflow-y: auto;
                            z-index: 1000;
                            opacity: 0;
                            visibility: hidden;
                            transform: translateY(-10px);
                            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                        ">
                            <div class="dropdown-option" data-value="" style="
                                padding: 12px 16px;
                                cursor: pointer;
                                transition: all 0.2s ease;
                                color: var(--modal-input-text);
                                border-bottom: 1px solid rgba(0,0,0,0.05);
                            " onclick="selectProduct('', '-- Pilih Produk --', 0)">
                                -- Pilih Produk --
                            </div>
                            
                            <!-- Opsi Lain-lain (Hardcoded) -->
                            <div class="dropdown-option" data-value="lain-lain" style="
                                padding: 12px 16px;
                                cursor: pointer;
                                transition: all 0.2s ease;
                                color: var(--modal-input-text);
                                border-bottom: 1px solid rgba(0,0,0,0.05);
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(217, 119, 6, 0.1));
                            " onclick="selectProduct('lain-lain', 'Lain-lain', 0, true)">
                                <span style="font-weight: 700;">Lain-lain</span>
                                <span style="font-size: 0.75rem; opacity: 0.7; font-style: italic;">Harga Custom</span>
                            </div>
                            
                            @foreach($products as $product)
                                @if($product->stock > 0)
                                    <div class="dropdown-option" data-value="{{ $product->id }}" style="
                                        padding: 12px 16px;
                                        cursor: pointer;
                                        transition: all 0.2s ease;
                                        color: var(--modal-input-text);
                                        border-bottom: 1px solid rgba(0,0,0,0.05);
                                        display: flex;
                                        justify-content: space-between;
                                        align-items: center;
                                    " onclick="selectProduct('{{ $product->id }}', '{{ $product->name }}', {{ $product->price }})">
                                        <span>{{ $product->name }}</span>
                                        <span style="font-size: 0.85rem; opacity: 0.7; font-weight: 600;">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Hidden input for form submission -->
                    <input type="hidden" id="debtProductSelect" value="">
                </div>

                <!-- Input Total Hutang (Muncul jika pilih Lain-lain) -->
                <div id="customPriceSection" style="margin-bottom: 20px; display: none;">
                    <label class="modal-form-label" style="display: block; font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">
                        Total Hutang *
                    </label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-weight: 700; color: var(--modal-input-text); opacity: 0.7;">Rp</span>
                        <input type="number" id="customPrice" class="modal-form-input" placeholder="0" min="0" 
                               oninput="updateDebtTotal()"
                               style="width: 100%; padding: 12px 12px 12px 45px; border: 2px solid #f59e0b; border-radius: 12px; font-size: 1rem; font-weight: 600;">
                    </div>
                    <p style="font-size: 0.75rem; color: #6b7280; margin-top: 6px; font-style: italic;">
                        Masukkan total hutang
                    </p>
                </div>

                <!-- Nama Pelanggan dan Jumlah -->
                <div id="customerInfoSection" style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 20px;">
                    <div style="position: relative;">
                        <label class="modal-form-label" style="display: block; font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">Nama Pelanggan *</label>
                        <input type="text" id="customerName" class="modal-form-input" placeholder="Masukkan nama pelanggan" 
                               style="width: 100%; padding: 12px; border: 2px solid; border-radius: 12px; font-size: 1rem;"
                               autocomplete="off" oninput="searchMembers(this.value)" onfocus="showMemberSuggestions()">
                        
                        <!-- Dropdown suggestions -->
                        <div id="memberSuggestions" style="
                            position: absolute;
                            top: 100%;
                            left: 0;
                            right: 0;
                            background: var(--modal-input-bg);
                            border: 2px solid var(--modal-input-border);
                            border-radius: 12px;
                            margin-top: 4px;
                            max-height: 200px;
                            overflow-y: auto;
                            z-index: 1000;
                            display: none;
                            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                        ">
                            <!-- Suggestions will be populated here -->
                        </div>
                    </div>
                    <div id="quantitySection">
                        <label class="modal-form-label" style="display: block; font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">Jumlah</label>
                        <input type="number" id="debtQuantity" class="modal-form-input" value="1" min="1" onchange="updateDebtTotal()" style="width: 100%; padding: 12px; border: 2px solid; border-radius: 12px; font-size: 1rem; text-align: center;">
                    </div>
                </div>

                <!-- No. Telepon -->
                <div style="margin-bottom: 20px;">
                    <label class="modal-form-label" style="display: block; font-size: 0.9rem; font-weight: 700; margin-bottom: 8px;">No. Telepon (Opsional)</label>
                    <input type="text" id="customerPhone" class="modal-form-input" placeholder="08xxxxxxxxxx" style="width: 100%; padding: 12px; border: 2px solid; border-radius: 12px; font-size: 1rem;">
                </div>

                <!-- Total Hutang -->
                <div class="total-section">
                    <div style="text-align: center;">
                        <div class="total-label">Total Hutang</div>
                        <div class="total-amount" id="debtTotalAmount">Rp 0</div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn-modal btn-cancel" onclick="closeDebtModal()">Batal</button>
                <button class="btn-modal btn-confirm" onclick="confirmDebt()">Catat Hutang</button>
            </div>
        </div>
    </div>

    <!-- Modal Bayar Hutang -->
    <div id="payDebtModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <h3 class="modal-title">Bayar Hutang</h3>
                <p class="modal-subtitle" id="payDebtCustomerName">Konfirmasi pembayaran</p>
            </div>
            
            <div class="modal-body">
                <div style="background: #fef3c7; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
                    <div style="text-align: center;">
                        <div style="font-size: 0.8rem; color: #f59e0b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Total Hutang</div>
                        <div id="totalDebtAmount" style="font-size: 1.8rem; font-weight: 900; color: #d97706;">Rp 0</div>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 0.9rem; font-weight: 700; color: #374151; margin-bottom: 8px;">Metode Pembayaran</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="payment-btn selected" data-method="cash" style="padding: 12px; border: 2px solid; border-radius: 12px; cursor: pointer; text-align: center; transition: all 0.3s ease;">
                            <div style="font-size: 1.2rem; margin-bottom: 4px;">🏦</div>
                            <div style="font-size: 0.8rem; font-weight: 700;">TRANSFER BANK</div>
                        </div>
                        <div class="payment-btn" data-method="transfer" style="padding: 12px; border: 2px solid; border-radius: 12px; cursor: pointer; text-align: center; transition: all 0.3s ease;">
                            <div style="font-size: 1.2rem; margin-bottom: 4px;">📱</div>
                            <div style="font-size: 0.8rem; font-weight: 700;">QRIS</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn-modal btn-cancel" onclick="closePayDebtModal()">Batal</button>
                <button class="btn-modal" onclick="confirmPayDebt()" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">Bayar Lunas</button>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalProductName">Konfirmasi Pembayaran</h3>
                <p class="modal-subtitle">Pilih metode pembayaran</p>
            </div>
            
            <div class="modal-body">
                <!-- Member Name Dropdown (Optional) -->
                <div class="member-section" style="margin-bottom: 20px; position: relative;">
                    <div style="text-align: left;">
                        <div style="font-size: 0.8rem; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; font-weight: 600;">
                            Nama Pelanggan
                        </div>
                        <input type="text" id="memberNameInput" placeholder="Cari atau pilih member..." 
                               value="Tamu Kantin"
                               autocomplete="off" 
                               oninput="searchMembersForPayment(this.value)" 
                               onfocus="if(this.value === 'Tamu Kantin') this.value = ''; showMemberSuggestionsForPayment()"
                               class="modal-form-input"
                               style="width: 100%; height: 45px; padding: 0 15px; font-size: 1rem; border: 2px solid; border-radius: 8px; transition: all 0.2s;" 
                               onfocusin="this.style.borderColor='#f59e0b';" 
                               onblur="setTimeout(() => { hideMemberSuggestionsForPayment(); if(!this.value.trim()) this.value='Tamu Kantin'; }, 200); this.style.borderColor=''">
                        
                        <!-- Dropdown suggestions for payment modal -->
                        <div id="memberSuggestionsPayment" style="
                            position: absolute;
                            top: 100%;
                            left: 0;
                            right: 0;
                            background: var(--modal-input-bg);
                            border: 2px solid var(--modal-input-border);
                            border-radius: 8px;
                            margin-top: 4px;
                            max-height: 250px;
                            overflow-y: auto;
                            overflow-x: hidden;
                            z-index: 1000;
                            display: none;
                            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                            color: var(--modal-input-text);
                        ">
                            <!-- Suggestions will be populated here -->
                        </div>
                    </div>
                </div>
                
                <div class="quantity-section" style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                        <button type="button" onclick="changeQuantity(-1)" class="qty-btn" style="width: 40px; height: 40px; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-size: 1.2rem; font-weight: bold;">-</button>
                        <div style="text-align: center;">
                            <div style="font-size: 0.8rem; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px;">Jumlah</div>
                            <input type="number" id="quantityInput" value="1" min="1" max="99" onchange="updateTotal()" style="width: 80px; height: 40px; text-align: center; font-size: 1.5rem; font-weight: bold; border: 2px solid #e5e7eb; border-radius: 8px; background: #f8fafc;">
                        </div>
                        <button type="button" onclick="changeQuantity(1)" class="qty-btn" style="width: 40px; height: 40px; border: 2px solid #e5e7eb; border-radius: 8px; background: white; cursor: pointer; font-size: 1.2rem; font-weight: bold;">+</button>
                    </div>
                </div>
                
                <div class="total-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <div style="text-align: left;">
                            <div class="total-label">Harga Satuan</div>
                            <div style="font-size: 1.2rem; font-weight: 600; color: #374151;" id="unitPrice">Rp 0</div>
                        </div>
                        <div style="text-align: right;">
                            <div class="total-label">Total Pembayaran</div>
                            <div class="total-amount" id="modalTotalAmount">Rp 0</div>
                        </div>
                    </div>
                </div>
                
                <div class="payment-methods">
                    <div class="payment-btn selected" data-method="cash">
                        <div class="payment-icon">🏦</div>
                        <div class="payment-label">Transfer Bank</div>
                    </div>
                    <div class="payment-btn" data-method="transfer">
                        <div class="payment-icon">📱</div>
                        <div class="payment-label">QRIS</div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button class="btn-modal btn-cancel" onclick="closePaymentModal()">Batal</button>
                <button class="btn-modal btn-confirm" onclick="confirmPayment()">Bayar Sekarang</button>
            </div>
        </div>
    </div>

    {{-- Branding footer dinonaktifkan --}}

    <script>
        let selectedProduct = null;
        let selectedPaymentMethod = 'cash';

        function showPaymentModal(productId, productName, price, stock) {
            if (stock <= 0) return;
            
            selectedProduct = {
                id: productId,
                name: productName,
                price: price,
                stock: stock
            };
            
            document.getElementById('modalProductName').textContent = productName;
            document.getElementById('unitPrice').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
            document.getElementById('quantityInput').value = 1;
            document.getElementById('quantityInput').max = stock; // Set max berdasarkan stock
            updateTotal();
            
            // Reset payment method to cash
            selectedPaymentMethod = 'cash';
            console.log('Modal opened, payment method reset to:', selectedPaymentMethod);
            
            // Reset visual selection
            document.querySelectorAll('#paymentModal .payment-btn').forEach(b => b.classList.remove('selected'));
            const cashBtn = document.querySelector('#paymentModal .payment-btn[data-method="cash"]');
            if (cashBtn) {
                cashBtn.classList.add('selected');
                console.log('Cash button selected visually');
            }
            
            // Add class to body and show modal
            document.body.classList.add('modal-open');
            document.getElementById('paymentModal').classList.add('show');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.remove('show');
            document.body.classList.remove('modal-open');
            selectedProduct = null;
            // Reset payment method to default
            selectedPaymentMethod = 'cash';
            // Reset member name input to default
            document.getElementById('memberNameInput').value = 'Tamu Kantin';
        }

        function changeQuantity(change) {
            const input = document.getElementById('quantityInput');
            let newValue = parseInt(input.value) + change;
            
            if (newValue < 1) newValue = 1;
            if (newValue > selectedProduct.stock) newValue = selectedProduct.stock;
            
            input.value = newValue;
            updateTotal();
        }

        function updateTotal() {
            if (!selectedProduct) return;
            
            const quantity = parseInt(document.getElementById('quantityInput').value) || 1;
            const total = selectedProduct.price * quantity;
            
            document.getElementById('modalTotalAmount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        }

        function confirmPayment() {
            if (!selectedProduct) {
                console.error('No product selected!');
                return;
            }
            
            const quantity = parseInt(document.getElementById('quantityInput').value) || 1;
            const memberNameRaw = document.getElementById('memberNameInput').value.trim();
            // Jika "Tamu Kantin" (default), kirim null agar backend set otomatis
            const memberName = (memberNameRaw === 'Tamu Kantin' || !memberNameRaw) ? null : memberNameRaw;
            
            // Debug: log semua data yang akan dikirim
            console.log('=== CONFIRM PAYMENT DEBUG ===');
            console.log('Product ID:', selectedProduct.id);
            console.log('Product Name:', selectedProduct.name);
            console.log('Payment Method:', selectedPaymentMethod);
            console.log('Quantity:', quantity);
            console.log('Member Name:', memberName);
            console.log('Selected button:', document.querySelector('#paymentModal .payment-btn.selected')?.dataset.method);
            console.log('==============================');
            
            // Pastikan payment method tidak undefined
            const finalPaymentMethod = selectedPaymentMethod || 'cash';
            console.log('Final payment method to send:', finalPaymentMethod);
            
            // Call Livewire method with payment method, quantity, and member name
            @this.call('bayarHarian', selectedProduct.id, finalPaymentMethod, quantity, memberName);
            closePaymentModal();
        }

        // Payment method selection for PAYMENT MODAL only
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, setting up payment method listeners');
            
            // Hapus event listener lama jika ada
            const paymentButtons = document.querySelectorAll('#paymentModal .payment-btn');
            paymentButtons.forEach(btn => {
                // Clone node untuk menghapus semua event listener
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
            });
            
            // Tambahkan event listener baru
            document.querySelectorAll('#paymentModal .payment-btn').forEach(btn => {
                console.log('Adding listener to button:', btn.dataset.method);
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log('Payment button clicked:', this.dataset.method);
                    
                    // Remove selected class from all buttons
                    document.querySelectorAll('#paymentModal .payment-btn').forEach(b => {
                        b.classList.remove('selected');
                    });
                    
                    // Add selected class to clicked button
                    this.classList.add('selected');
                    
                    // Update global variable
                    selectedPaymentMethod = this.dataset.method;
                    console.log('Payment method changed to:', selectedPaymentMethod);
                });
            });
        });

        // Close modal when clicking overlay
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePaymentModal();
            }
        });

        // Handle quantity input change
        document.getElementById('quantityInput').addEventListener('input', function() {
            let value = parseInt(this.value);
            if (isNaN(value) || value < 1) {
                this.value = 1;
            } else if (selectedProduct && value > selectedProduct.stock) {
                this.value = selectedProduct.stock;
            }
            updateTotal();
        });

        // MODERN DROPDOWN FUNCTIONS
        let isDropdownOpen = false;
        let membersData = []; // Cache untuk data members

        // Fetch members data saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            fetchMembersData();
        });

        function fetchMembersData() {
            // Panggil method Livewire untuk mengambil data members
            @this.call('getMembersData').then(result => {
                membersData = result;
                console.log('Members data loaded:', membersData.length, 'members');
            });
        }

        function searchMembers(query) {
            const suggestionsDiv = document.getElementById('memberSuggestions');
            
            if (!query || query.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            // Filter members berdasarkan nama
            const filteredMembers = membersData.filter(member => 
                member.name.toLowerCase().includes(query.toLowerCase())
            );

            if (filteredMembers.length === 0) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            // Buat HTML untuk suggestions
            let suggestionsHTML = '';
            filteredMembers.slice(0, 5).forEach(member => { // Maksimal 5 suggestions
                suggestionsHTML += `
                    <div class="member-suggestion" onclick="selectMember('${member.name}', '${member.phone || ''}')">
                        <span class="member-name">${member.name}</span>
                        ${member.phone ? `<span class="member-phone">${member.phone}</span>` : ''}
                    </div>
                `;
            });

            suggestionsDiv.innerHTML = suggestionsHTML;
            suggestionsDiv.style.display = 'block';
        }

        function showMemberSuggestions() {
            const query = document.getElementById('customerName').value;
            if (query.length >= 2) {
                searchMembers(query);
            }
        }

        function selectMember(name, phone) {
            // Set nama pelanggan
            document.getElementById('customerName').value = name;
            
            // Set nomor telepon jika ada
            if (phone) {
                document.getElementById('customerPhone').value = phone;
            }
            
            // Sembunyikan suggestions
            document.getElementById('memberSuggestions').style.display = 'none';
        }

        // Sembunyikan suggestions ketika klik di luar
        document.addEventListener('click', function(event) {
            const customerNameInput = document.getElementById('customerName');
            const suggestionsDiv = document.getElementById('memberSuggestions');
            
            if (customerNameInput && suggestionsDiv && 
                !customerNameInput.contains(event.target) && 
                !suggestionsDiv.contains(event.target)) {
                suggestionsDiv.style.display = 'none';
            }
        });

        function toggleDropdown() {
            const dropdownMenu = document.getElementById('dropdownMenu');
            const dropdownArrow = document.getElementById('dropdownArrow');
            
            isDropdownOpen = !isDropdownOpen;
            
            if (isDropdownOpen) {
                dropdownMenu.classList.add('show');
                dropdownArrow.style.transform = 'rotate(180deg)';
            } else {
                dropdownMenu.classList.remove('show');
                dropdownArrow.style.transform = 'rotate(0deg)';
            }
        }

        function selectProduct(productId, productName, productPrice, isCustom = false) {
            // Update the button text
            document.getElementById('selectedProduct').textContent = productName;
            
            // Update the hidden input
            document.getElementById('debtProductSelect').value = productId;
            
            // Store product data for calculations
            if (productId) {
                document.getElementById('debtProductSelect').dataset.price = productPrice;
                document.getElementById('debtProductSelect').dataset.isCustom = isCustom ? 'true' : 'false';
            }
            
            // Show/hide custom price input and quantity field
            const customPriceSection = document.getElementById('customPriceSection');
            const quantitySection = document.getElementById('quantitySection');
            const customerInfoSection = document.getElementById('customerInfoSection');
            
            if (isCustom) {
                // Untuk produk Lain-lain
                customPriceSection.style.display = 'block';
                quantitySection.style.display = 'none'; // Sembunyikan field jumlah
                customerInfoSection.style.gridTemplateColumns = '1fr'; // Full width untuk nama
                document.getElementById('customPrice').value = '';
                document.getElementById('debtQuantity').value = 1; // Reset ke 1
                document.getElementById('customPrice').focus();
            } else {
                // Untuk produk biasa
                customPriceSection.style.display = 'none';
                quantitySection.style.display = 'block'; // Tampilkan field jumlah
                customerInfoSection.style.gridTemplateColumns = '2fr 1fr'; // Grid normal
                document.getElementById('customPrice').value = '';
                document.getElementById('debtQuantity').value = 1;
            }
            
            // Close dropdown
            isDropdownOpen = false;
            document.getElementById('dropdownMenu').classList.remove('show');
            document.getElementById('dropdownArrow').style.transform = 'rotate(0deg)';
            
            // Update total calculation
            updateDebtTotal();
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.custom-dropdown');
            if (dropdown && !dropdown.contains(event.target) && isDropdownOpen) {
                isDropdownOpen = false;
                document.getElementById('dropdownMenu').classList.remove('show');
                document.getElementById('dropdownArrow').style.transform = 'rotate(0deg)';
            }
        });

        // DEBT MODAL FUNCTIONS
        let selectedDebt = null;

        function showDebtModal() {
            document.body.classList.add('modal-open');
            document.getElementById('debtModal').classList.add('show');
            
            // Reset form
            document.getElementById('debtProductSelect').value = '';
            document.getElementById('debtProductSelect').dataset.price = '';
            document.getElementById('debtProductSelect').dataset.isCustom = 'false';
            document.getElementById('selectedProduct').textContent = '-- Pilih Produk --';
            document.getElementById('customerName').value = '';
            document.getElementById('customerPhone').value = '';
            document.getElementById('debtQuantity').value = 1;
            document.getElementById('debtTotalAmount').textContent = 'Rp 0';
            
            // Hide custom price section
            document.getElementById('customPriceSection').style.display = 'none';
            document.getElementById('customPrice').value = '';
            
            // Show quantity section and reset grid
            document.getElementById('quantitySection').style.display = 'block';
            document.getElementById('customerInfoSection').style.gridTemplateColumns = '2fr 1fr';
            
            // Reset dropdown state
            isDropdownOpen = false;
            document.getElementById('dropdownMenu').classList.remove('show');
            document.getElementById('dropdownArrow').style.transform = 'rotate(0deg)';
            
            // Hide member suggestions
            document.getElementById('memberSuggestions').style.display = 'none';
        }

        function closeDebtModal() {
            document.getElementById('debtModal').classList.remove('show');
            document.body.classList.remove('modal-open');
        }

        function updateDebtTotal() {
            const productSelect = document.getElementById('debtProductSelect');
            const quantity = parseInt(document.getElementById('debtQuantity').value) || 1;
            
            if (productSelect.value) {
                let total = 0;
                
                // Check if this is a custom price product
                if (productSelect.dataset.isCustom === 'true') {
                    // Untuk Lain-lain, langsung ambil total (tidak dikali quantity)
                    total = parseInt(document.getElementById('customPrice').value) || 0;
                } else {
                    // Untuk produk biasa, harga dikali quantity
                    const price = parseInt(productSelect.dataset.price) || 0;
                    total = price * quantity;
                }
                
                document.getElementById('debtTotalAmount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
            } else {
                document.getElementById('debtTotalAmount').textContent = 'Rp 0';
            }
        }

        function confirmDebt() {
            const productSelect = document.getElementById('debtProductSelect');
            const customerName = document.getElementById('customerName').value.trim();
            const customerPhone = document.getElementById('customerPhone').value.trim();
            const quantity = parseInt(document.getElementById('debtQuantity').value) || 1;

            if (!productSelect.value) {
                alert('Pilih produk terlebih dahulu!');
                return;
            }

            if (!customerName) {
                alert('Nama pelanggan wajib diisi!');
                return;
            }

            // Check if custom price is required
            if (productSelect.dataset.isCustom === 'true') {
                const customPrice = parseInt(document.getElementById('customPrice').value) || 0;
                if (customPrice <= 0) {
                    alert('Total hutang wajib diisi dan harus lebih dari 0!');
                    return;
                }
                // Call Livewire method with custom price (quantity selalu 1 untuk lain-lain)
                @this.call('catatHutangCustom', customerName, customerPhone, customPrice, null);
            } else {
                // Call Livewire method for regular product
                @this.call('catatHutang', productSelect.value, customerName, customerPhone, quantity, null);
            }
            
            closeDebtModal();
        }

        function showPayDebtModal(transactionId, customerName, totalAmount) {
            selectedDebt = {
                id: transactionId,
                customerName: customerName,
                totalAmount: totalAmount
            };

            document.getElementById('payDebtCustomerName').textContent = customerName;
            document.getElementById('totalDebtAmount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalAmount);

            // Reset payment method selection
            document.querySelectorAll('#payDebtModal .payment-btn').forEach(btn => {
                btn.classList.remove('selected');
                btn.style.background = 'white';
                btn.style.color = '#374151';
                btn.style.borderColor = '#e5e7eb';
            });
            
            // Select cash by default
            const cashBtn = document.querySelector('#payDebtModal .payment-btn[data-method="cash"]');
            cashBtn.classList.add('selected');
            cashBtn.style.background = '#f59e0b';
            cashBtn.style.color = 'white';
            cashBtn.style.borderColor = '#f59e0b';

            document.body.classList.add('modal-open');
            document.getElementById('payDebtModal').classList.add('show');
        }

        function closePayDebtModal() {
            document.getElementById('payDebtModal').classList.remove('show');
            document.body.classList.remove('modal-open');
            selectedDebt = null;
        }

        function confirmPayDebt() {
            if (!selectedDebt) return;

            // Get selected payment method
            const selectedPaymentBtn = document.querySelector('#payDebtModal .payment-btn.selected');
            const paymentMethod = selectedPaymentBtn ? selectedPaymentBtn.dataset.method : 'cash';

            // Call Livewire method
            @this.call('bayarHutang', selectedDebt.id, paymentMethod);
            closePayDebtModal();
        }

        // === MEMBER SUGGESTIONS FOR PAYMENT MODAL ===
        function searchMembersForPayment(query) {
            const suggestionsDiv = document.getElementById('memberSuggestionsPayment');
            
            if (!query || query.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }

            // Filter members berdasarkan nama
            const filteredMembers = membersData.filter(member => 
                member.name.toLowerCase().includes(query.toLowerCase())
            );

            if (filteredMembers.length === 0) {
                suggestionsDiv.innerHTML = `
                    <div style="padding: 12px 16px; color: #9ca3af; font-size: 0.9rem; text-align: center;">
                        Tidak ada member ditemukan
                    </div>
                `;
                suggestionsDiv.style.display = 'block';
                return;
            }

            // Buat HTML untuk suggestions
            let suggestionsHTML = '';
            filteredMembers.slice(0, 5).forEach(member => { // Maksimal 5 suggestions
                suggestionsHTML += `
                    <div onclick="selectMemberForPayment('${member.name.replace(/'/g, "\\'")}', '${member.phone || ''}')" 
                         class="member-suggestion-payment"
                         style="padding: 12px 16px; cursor: pointer; transition: all 0.2s; border-bottom: 1px solid rgba(0,0,0,0.05);"
                         onmouseover="this.style.background='rgba(245, 158, 11, 0.1)'"
                         onmouseout="this.style.background='transparent'">
                        <div style="font-weight: 600; margin-bottom: 2px;">${member.name}</div>
                        ${member.phone ? `<div style="font-size: 0.8rem; opacity: 0.7;">${member.phone}</div>` : ''}
                    </div>
                `;
            });

            suggestionsDiv.innerHTML = suggestionsHTML;
            suggestionsDiv.style.display = 'block';
        }

        function showMemberSuggestionsForPayment() {
            const query = document.getElementById('memberNameInput').value;
            if (query.length >= 2) {
                searchMembersForPayment(query);
            } else if (query.length === 0 && membersData.length > 0) {
                // Show all members if input is empty
                const suggestionsDiv = document.getElementById('memberSuggestionsPayment');
                let suggestionsHTML = '';
                membersData.slice(0, 5).forEach(member => {
                    suggestionsHTML += `
                        <div onclick="selectMemberForPayment('${member.name.replace(/'/g, "\\'")}', '${member.phone || ''}')" 
                             class="member-suggestion-payment"
                             style="padding: 12px 16px; cursor: pointer; transition: all 0.2s; border-bottom: 1px solid rgba(0,0,0,0.05);"
                             onmouseover="this.style.background='rgba(245, 158, 11, 0.1)'"
                             onmouseout="this.style.background='transparent'">
                            <div style="font-weight: 600; margin-bottom: 2px;">${member.name}</div>
                            ${member.phone ? `<div style="font-size: 0.8rem; opacity: 0.7;">${member.phone}</div>` : ''}
                        </div>
                    `;
                });
                suggestionsDiv.innerHTML = suggestionsHTML;
                suggestionsDiv.style.display = 'block';
            }
        }

        function hideMemberSuggestionsForPayment() {
            const suggestionsDiv = document.getElementById('memberSuggestionsPayment');
            suggestionsDiv.style.display = 'none';
        }

        function selectMemberForPayment(name, phone) {
            // Set nama member
            document.getElementById('memberNameInput').value = name;
            
            // Sembunyikan suggestions
            hideMemberSuggestionsForPayment();
        }

        // Payment method selection for debt modal
        document.querySelectorAll('#payDebtModal .payment-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Reset all buttons
                document.querySelectorAll('#payDebtModal .payment-btn').forEach(b => {
                    b.classList.remove('selected');
                    b.style.background = 'white';
                    b.style.color = '#374151';
                    b.style.borderColor = '#e5e7eb';
                });
                
                // Select clicked button
                this.classList.add('selected');
                this.style.background = '#f59e0b';
                this.style.color = 'white';
                this.style.borderColor = '#f59e0b';
            });
        });

        // Close debt modals when clicking overlay
        document.getElementById('debtModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDebtModal();
            }
        });

        document.getElementById('payDebtModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePayDebtModal();
            }
        });

        // THEME DETECTION & RESPONSIVE DEBT SECTION
        function updateDebtSectionTheme() {
            const debtSection = document.querySelector('.debt-section');
            if (!debtSection) return;
            
            // Force refresh CSS variables by toggling a class
            debtSection.classList.add('theme-updating');
            setTimeout(() => {
                debtSection.classList.remove('theme-updating');
            }, 10);
        }

        // Listen for theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', updateDebtSectionTheme);
        }

        // Listen for Filament theme changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const target = mutation.target;
                    if (target.classList.contains('dark') || target === document.documentElement) {
                        updateDebtSectionTheme();
                    }
                }
            });
        });

        // Observe changes to html and body classes
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });

        // Initial theme update
        updateDebtSectionTheme();
    </script>
</x-filament::page>