<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARIFAH Gym - Kasir</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@400;900&display=swap');
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #0a0f1c 0%, #1a1f2e 50%, #0f172a 100%);
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .font-hero { 
            font-family: 'Poppins'; 
            font-weight: 900; 
            font-style: italic; 
            text-transform: uppercase; 
        }
        
        /* Animated Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 80%, rgba(9, 146, 194, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(16, 185, 129, 0.1) 0%, transparent 50%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        
        .btn-modern {
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform-style: preserve-3d;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .btn-modern:hover::before {
            left: 100%;
        }
        
        .btn-modern:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0992C2 0%, #06b6d4 50%, #0891b2 100%);
            box-shadow: 0 10px 30px rgba(9, 146, 194, 0.3);
        }
        
        .btn-primary:hover {
            box-shadow: 0 20px 40px rgba(9, 146, 194, 0.5);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }
        
        .btn-secondary:hover {
            box-shadow: 0 20px 40px rgba(16, 185, 129, 0.5);
        }
        
        .logo-glow {
            text-shadow: 0 0 30px rgba(9, 146, 194, 0.6);
        }
        
        .fade-in {
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }
        
        /* Floating particles */
        .particle {
            position: absolute;
            background: rgba(9, 146, 194, 0.3);
            border-radius: 50%;
            animation: floatParticle 8s infinite linear;
        }
        
        @keyframes floatParticle {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="text-white">
    <!-- Floating Particles -->
    <div class="particle" style="left: 5%; width: 3px; height: 3px; animation-delay: 0s;"></div>
    <div class="particle" style="left: 10%; width: 4px; height: 4px; animation-delay: 1s;"></div>
    <div class="particle" style="left: 15%; width: 5px; height: 5px; animation-delay: 2s;"></div>
    <div class="particle" style="left: 20%; width: 6px; height: 6px; animation-delay: 3s;"></div>
    <div class="particle" style="left: 25%; width: 3px; height: 3px; animation-delay: 4s;"></div>
    <div class="particle" style="left: 30%; width: 4px; height: 4px; animation-delay: 5s;"></div>
    <div class="particle" style="left: 35%; width: 5px; height: 5px; animation-delay: 6s;"></div>
    <div class="particle" style="left: 40%; width: 6px; height: 6px; animation-delay: 7s;"></div>
    <div class="particle" style="left: 45%; width: 4px; height: 4px; animation-delay: 0.5s;"></div>
    <div class="particle" style="left: 50%; width: 3px; height: 3px; animation-delay: 1.5s;"></div>
    <div class="particle" style="left: 55%; width: 5px; height: 5px; animation-delay: 2.5s;"></div>
    <div class="particle" style="left: 60%; width: 4px; height: 4px; animation-delay: 3.5s;"></div>
    <div class="particle" style="left: 65%; width: 6px; height: 6px; animation-delay: 4.5s;"></div>
    <div class="particle" style="left: 70%; width: 3px; height: 3px; animation-delay: 5.5s;"></div>
    <div class="particle" style="left: 75%; width: 5px; height: 5px; animation-delay: 6.5s;"></div>
    <div class="particle" style="left: 80%; width: 4px; height: 4px; animation-delay: 7.5s;"></div>
    <div class="particle" style="left: 85%; width: 6px; height: 6px; animation-delay: 1.2s;"></div>
    <div class="particle" style="left: 90%; width: 3px; height: 3px; animation-delay: 2.8s;"></div>
    <div class="particle" style="left: 95%; width: 4px; height: 4px; animation-delay: 4.2s;"></div>
    <div class="particle" style="left: 12%; width: 5px; height: 5px; animation-delay: 6.8s;"></div>

    <div class="min-h-screen flex items-center justify-center p-6 relative z-10">
        <div class="max-w-lg w-full">
            <!-- Main Card -->
            <div class="glass-card rounded-3xl p-8 text-center fade-in">
                <!-- Logo -->
                <div class="mb-10 stagger-1 fade-in">
                    <h1 class="text-5xl font-hero tracking-tighter italic mb-2">ARIFAH <span class="text-[#0992C2]">GYM</span></h1>
                    <div class="flex items-center justify-center gap-3">
                        <div class="h-[1px] w-10 bg-white/20"></div>
                        <p class="text-gray-300 text-[10px] uppercase tracking-[0.5em] font-black italic">KASIR SYSTEM</p>
                        <div class="h-[1px] w-10 bg-white/20"></div>
                    </div>
                </div>

                <!-- Menu Buttons -->
                <div class="space-y-4 stagger-2 fade-in">
                    <!-- Absen Button -->
                    <a href="/absen-gym" class="btn-modern btn-primary block w-full py-5 px-6 rounded-2xl text-white font-bold text-lg group">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-user-check text-lg"></i>
                            </div>
                            <div class="text-left flex-1">
                                <div class="font-black text-lg">ABSEN MEMBER</div>
                                <div class="text-xs opacity-80 font-medium">Check-in member</div>
                            </div>
                        </div>
                    </a>

                    <!-- Registrasi Button -->
                    <a href="/daftar" class="btn-modern btn-secondary block w-full py-5 px-6 rounded-2xl text-white font-bold text-lg group">
                        <div class="flex items-center space-x-4">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-user-plus text-lg"></i>
                            </div>
                            <div class="text-left flex-1">
                                <div class="font-black text-lg">REGISTRASI MEMBER</div>
                                <div class="text-xs opacity-80 font-medium">Daftar member baru</div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Footer -->
                <div class="mt-8 stagger-3 fade-in">
                    <div class="w-full h-px bg-gradient-to-r from-transparent via-white/20 to-transparent mb-4"></div>
                    <p class="text-xs text-gray-400 font-medium">
                        © {{ date('Y') }} ARIFAH Gym Management System
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>