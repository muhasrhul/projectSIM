<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ARIFAH Gym - Absensi Member</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #000; overflow-x: hidden; }
        .font-hero { font-family: 'Poppins'; font-weight: 900; font-style: italic; text-transform: uppercase; }
        
        .bg-gym {
            background-image: linear-gradient(to bottom, rgba(0,0,0,0.85), rgba(0,0,0,0.95)), 
                              url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1000&auto=format&fit=crop');
            background-size: cover; background-position: center;
        }

        .premium-card {
            background: rgba(15, 15, 15, 0.75);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.8);
        }

        .input-premium {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.4s ease;
        }

        .input-premium:focus {
            border-color: #ea580c;
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 0 30px rgba(234, 88, 12, 0.15);
        }

        .shake { animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both; }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        #finalCanvas { display: none; }
        .font-loader { position: absolute; visibility: hidden; height: 0; width: 0; font-family: 'Poppins'; }
    </style>
</head>
<body class="bg-gym text-white flex items-center justify-center min-h-screen p-4">

    <div class="font-loader" style="font-weight: 400;">Poppins 400</div>
    <div class="font-loader" style="font-weight: 900; font-style: italic;">Poppins 900i</div>

    <audio id="successSound" src="https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3"></audio>

    <div class="w-full max-w-md">
        
        <div class="text-center mb-10">
            <h1 class="text-5xl font-hero tracking-tighter italic">ARIFAH <span class="text-[#F59E0B]">GYM</span></h1>
            <div class="flex items-center justify-center gap-3 mt-2">
                <div class="h-[1px] w-10 bg-zinc-800"></div>
                <p class="text-zinc-500 text-[10px] uppercase tracking-[0.5em] font-black italic">MAKASSAR</p>
                <div class="h-[1px] w-10 bg-zinc-800"></div>
            </div>
        </div>

        @if(session('success'))
            <script>document.getElementById('successSound').play();</script>

            <div class="card-pop flex flex-col items-center premium-card p-8 rounded-[3.5rem] text-center">
                <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mb-4 shadow-xl">
                    <i class="fa-solid fa-check text-2xl text-black"></i>
                </div>
                
                <h1 class="text-3xl font-hero text-green-500 mb-1 italic">ABSEN BERHASIL</h1>
                <p class="text-zinc-500 text-[10px] uppercase tracking-widest mb-8 font-bold">{{ now()->format('H:i') }} WITA</p>

                <div class="grid grid-cols-2 gap-4 w-full mb-8 text-center">
                    <div class="bg-white/[0.03] p-5 rounded-3xl border border-white/5">
                        <p class="text-[9px] text-zinc-500 uppercase font-black mb-1">SESI BULAN INI</p>
                        <h3 class="text-3xl font-black italic">{{ session('total_latihan') }}<span class="text-sm text-green-500 ml-1">X</span></h3>
                    </div>
                    <div class="bg-white/[0.03] p-5 rounded-3xl border border-white/5 flex flex-col justify-center items-center">
                        <i class="fa-solid {{ session('badge') == 'ARIFAH WARRIOR' ? 'fa-fire text-[#F59E0B]' : 'fa-medal text-blue-400' }} text-2xl mb-1"></i>
                        <p class="text-[10px] font-black uppercase tracking-tighter">{{ session('badge') }}</p>
                    </div>
                </div>

                <!-- Digital Member Card -->
                <div class="relative w-full rounded-2xl overflow-hidden mb-8 mx-auto transition-all duration-500 hover:scale-[1.02] group"
                     style="max-width: 520px;
                            background: linear-gradient(135deg, #1e1410 0%, #0f0905 50%, #1e1410 100%);
                            border: 1px solid rgba(245,158,11,0.25);
                            box-shadow: 0 0 0 1px rgba(245,158,11,0.1), 0 20px 60px -10px rgba(0,0,0,0.8), 0 0 40px -5px rgba(245,158,11,0.15);">

                    <!-- Dumbbell Watermark -->
                    <div class="absolute inset-0 flex items-center pointer-events-none overflow-hidden opacity-[0.04]">
                        <i class="fa-solid fa-dumbbell -rotate-12" style="font-size:130px; margin-left:24px;"></i>
                    </div>

                    <!-- Orange glow top-right -->
                    <div class="absolute top-0 right-0 pointer-events-none"
                         style="width:200px; height:200px; opacity:0.2;
                                background: radial-gradient(circle at top right, #F59E0B 0%, transparent 70%);
                                filter: blur(20px);"></div>

                    <div class="relative z-10 flex" style="padding: 18px 16px 18px 20px; gap: 12px; text-align: left;">

                        <!-- Left: Info — mirrors canvas layout -->
                        <div class="flex flex-col overflow-hidden" style="flex:1; min-width:0; justify-content:space-between;">

                            <!-- Badge -->
                            <div>
                                <span class="inline-flex items-center uppercase font-black rounded-full"
                                      style="font-size:8px; letter-spacing:0.12em; padding: 4px 10px; line-height:1;
                                             border: 1.5px solid #F59E0B; color: #F59E0B;
                                             background: rgba(245,158,11,0.1);">
                                    {{ session('paket_nama') }}
                                </span>
                            </div>

                            <!-- Gym name -->
                            <div style="margin-top:10px;">
                                <p class="font-black italic truncate"
                                   style="font-family:'Poppins'; font-size:22px; color:#F59E0B; line-height:1.1;
                                          text-shadow: 0 0 18px rgba(245,158,11,0.6);">
                                    ARIFAH GYM
                                </p>
                                <p style="font-size:7px; letter-spacing:0.18em; color:rgba(255,255,255,0.45);
                                          font-weight:700; text-transform:uppercase; margin-top:3px;">
                                    OFFICIAL MEMBER
                                </p>
                            </div>

                            <!-- Member name + ID -->
                            <div style="margin-top:10px;">
                                <p class="font-black uppercase truncate"
                                   style="font-family:'Poppins'; font-size:18px; color:#fff; line-height:1.1;">
                                    {{ session('member_name') }}
                                </p>
                                <p style="font-size:8px; font-family:monospace; color:rgba(255,255,255,0.35);
                                          letter-spacing:0.12em; margin-top:3px;">
                                    ID: {{ session('member_id') }}
                                </p>
                            </div>

                            <!-- Divider + Expiry -->
                            <div style="margin-top:10px; border-top:1px solid rgba(255,255,255,0.08); padding-top:8px;">
                                <p style="font-size:7px; letter-spacing:0.16em; color:rgba(255,255,255,0.38);
                                          font-weight:700; text-transform:uppercase;">
                                    BERLAKU HINGGA
                                </p>
                                <p class="font-black uppercase truncate"
                                   style="font-family:'Poppins'; font-size:16px; color:#F59E0B;
                                          letter-spacing:0.04em; margin-top:3px;">
                                    {{ session('expiry_date') }}
                                </p>
                            </div>

                        </div>

                        <!-- Right: QR -->
                        <div class="flex-shrink-0 flex items-center justify-center">
                            <div class="rounded-xl transition-all duration-500 group-hover:scale-105"
                                 style="padding:7px; background:#fff;
                                        box-shadow: 0 0 0 2px rgba(245,158,11,0.5), 0 0 28px rgba(245,158,11,0.45);">
                                <img id="qrSrc"
                                     src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ session('member_id') }}"
                                     style="width:115px; height:115px; display:block;"
                                     crossorigin="anonymous">
                            </div>
                        </div>

                    </div>
                </div>

                <style>
                    @keyframes shimmer {
                        0% { transform: translateX(-100%); }
                        100% { transform: translateX(100%); }
                    }
                </style>

                <canvas id="finalCanvas" width="1050" height="630"></canvas>

                <div class="grid grid-cols-2 gap-4 w-full">
                    <button id="btnDownload" onclick="drawAndDownload()" class="group/btn bg-gradient-to-r from-gray-800 to-gray-900 hover:from-[#F59E0B] hover:to-[#F59E0B] py-5 rounded-[2rem] text-[11px] font-black uppercase tracking-widest transition-all duration-300 border border-white/10 hover:border-[#F59E0B] shadow-lg hover:shadow-[0_10px_30px_-10px_rgba(245,158,11,0.5)] transform hover:scale-[1.02] active:scale-[0.98] relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent translate-x-[-200%] group-hover/btn:translate-x-[200%] transition-transform duration-700"></div>
                        <span class="relative flex items-center justify-center gap-2">
                            <i class="fa-solid fa-download text-[#F59E0B] group-hover/btn:text-black transition-colors duration-300"></i> 
                            <span class="group-hover/btn:text-black transition-colors duration-300">SIMPAN</span>
                        </span>
                    </button>
                    <a href="/absen" class="bg-gradient-to-r from-[#F59E0B] to-[#F59E0B] hover:from-[#F59E0B] hover:to-[#F59E0B] py-5 rounded-[2rem] text-[11px] font-black text-black uppercase italic tracking-widest text-center flex items-center justify-center transition-all duration-300 shadow-lg hover:shadow-[0_10px_30px_-10px_rgba(245,158,11,0.5)] transform hover:scale-[1.02] active:scale-[0.98]">SELESAI</a>
                </div>
            </div>
        @else
            <div class="premium-card p-12 rounded-[4rem] relative overflow-hidden">
                <div class="absolute -top-24 -right-24 w-56 h-56 bg-[#F59E0B]/10 rounded-full blur-[80px]"></div>
                
                <div class="relative z-10 text-left">
                    <div class="mb-12">
                        <h2 class="text-2xl font-hero italic text-white tracking-widest">ABSEN</h2>
                        <div class="h-1 w-12 bg-[#F59E0B] mt-2"></div>
                        <p class="text-[11px] text-zinc-500 uppercase font-bold tracking-[0.2em] mt-4 italic">Welcome back, athlete.</p>
                    </div>

                    @if(session('error'))
                        <div class="shake mb-8 p-5 bg-red-600/10 border border-red-600/20 rounded-[2rem] flex items-center gap-4">
                            <div class="w-10 h-10 bg-red-600/20 rounded-full flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-xmark text-red-600"></i>
                            </div>
                            <p class="text-[11px] font-black text-red-500 uppercase tracking-wider leading-relaxed">{{ session('error') }}</p>
                        </div>
                    @endif

                    <form action="/absen" method="POST" class="space-y-10" id="absenForm">
                        @csrf
                        <div class="relative group">
                            <div class="absolute left-8 top-1/2 -translate-y-1/2 text-zinc-700 group-focus-within:text-[#F59E0B] transition-all text-xl">
                                <i class="fa-solid fa-fingerprint"></i>
                            </div>
                            <input type="tel" name="phone" id="phone" required placeholder="NOMOR WHATSAPP" autocomplete="tel" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                                   class="input-premium w-full pl-20 pr-10 py-7 rounded-[2.5rem] outline-none text-2xl font-black text-[#F59E0B] placeholder:text-zinc-800 placeholder:text-sm placeholder:tracking-[0.4em]">
                        </div>

                        <button type="submit" id="submitBtn" class="w-full bg-[#F59E0B] hover:bg-[#F59E0B] text-black font-black py-7 rounded-[2.5rem] text-sm uppercase italic tracking-[0.3em] shadow-[0_20px_40px_-10px_rgba(245,158,11,0.4)] active:scale-95 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="btnText">TAP-IN NOW <i class="fa-solid fa-arrow-right-long ml-3"></i></span>
                            <span id="btnLoading" class="hidden">
                                <i class="fa-solid fa-spinner fa-spin"></i> PROCESSING...
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        @endif
        
        <p class="mt-12 text-zinc-900 text-[10px] uppercase tracking-[0.8em] font-black italic text-center">ARIFAH GYM &copy; 2026</p>
    </div>

    <script>
        WebFont.load({ google: { families: ['Poppins:400,700,900,900i'] } });

        // Prevent double submit dan tampilkan loading state
        const absenForm = document.getElementById('absenForm');
        if (absenForm) {
            let formSubmitted = false;
            
            absenForm.addEventListener('submit', function(e) {
                if (formSubmitted) {
                    e.preventDefault();
                    return false;
                }
                
                formSubmitted = true;
                const submitBtn = document.getElementById('submitBtn');
                const btnText = document.getElementById('btnText');
                const btnLoading = document.getElementById('btnLoading');
                
                // Disable button dan tampilkan loading
                submitBtn.disabled = true;
                btnText.classList.add('hidden');
                btnLoading.classList.remove('hidden');
                
                // Set timeout untuk re-enable button jika terlalu lama (10 detik)
                // Jika lebih dari 10 detik, kemungkinan ada masalah
                setTimeout(function() {
                    if (submitBtn.disabled && formSubmitted) {
                        // Reload halaman jika stuck
                        console.warn('Form submission timeout, reloading page...');
                        window.location.reload();
                    }
                }, 10000);
            });
        }

        async function drawAndDownload() {
            const btn = document.getElementById('btnDownload');
            const canvas = document.getElementById('finalCanvas');
            const ctx = canvas.getContext('2d');
            // Canvas: 1050x550
            canvas.width = 1050; canvas.height = 550;
            btn.innerHTML = "RENDERING...";
            btn.disabled = true;

            await document.fonts.ready;

            const W = 1050, H = 550;
            const R = 48;

            // 1. Background: dark brown-black gradient
            ctx.save();
            ctx.beginPath();
            ctx.roundRect(0, 0, W, H, R);
            ctx.clip();

            const grad = ctx.createLinearGradient(0, 0, W, H);
            grad.addColorStop(0, '#1e1410');
            grad.addColorStop(0.5, '#0f0905');
            grad.addColorStop(1, '#1e1410');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, W, H);

            // 2. Orange glow top-right
            const flare = ctx.createRadialGradient(W, 0, 0, W, 0, 500);
            flare.addColorStop(0, 'rgba(245,158,11,0.25)');
            flare.addColorStop(1, 'transparent');
            ctx.fillStyle = flare;
            ctx.fillRect(0, 0, W, H);

            // 3. Orange glow bottom-left subtle
            const flare2 = ctx.createRadialGradient(0, H, 0, 0, H, 350);
            flare2.addColorStop(0, 'rgba(245,158,11,0.10)');
            flare2.addColorStop(1, 'transparent');
            ctx.fillStyle = flare2;
            ctx.fillRect(0, 0, W, H);

            // 4. Dumbbell watermark — drawn manually with canvas, rotated -12deg, low opacity
            ctx.save();
            ctx.globalAlpha = 0.055;
            ctx.translate(160, H / 2 + 30);
            ctx.rotate(-12 * Math.PI / 180);
            ctx.fillStyle = "#ffffff";
            // Dumbbell: bar + 2 plates each side
            const bar = { w: 160, h: 18 };
            const plate = { w: 28, h: 80 };
            const grip = { w: 18, h: 50 };
            // Center bar
            ctx.fillRect(-bar.w/2, -bar.h/2, bar.w, bar.h);
            // Left plates
            ctx.fillRect(-bar.w/2 - grip.w - plate.w, -plate.h/2, plate.w, plate.h);
            ctx.fillRect(-bar.w/2 - grip.w, -grip.h/2, grip.w, grip.h);
            // Right plates
            ctx.fillRect(bar.w/2 + grip.w, -plate.h/2, plate.w, plate.h);
            ctx.fillRect(bar.w/2, -grip.h/2, grip.w, grip.h);
            ctx.restore();

            ctx.restore();

            // 5. Card border glow
            ctx.save();
            ctx.beginPath();
            ctx.roundRect(0, 0, W, H, R);
            ctx.strokeStyle = 'rgba(245,158,11,0.3)';
            ctx.lineWidth = 2;
            ctx.stroke();
            ctx.restore();

            const lx = 50;
            const maxLW = 490;
            const qx = 560, qy = 55, qs = 440, qr = 22;

            // Distribute left col evenly across QR height: qy=55 → qy+qs=495 (440px total)
            // 7 text blocks, 6 gaps between them
            // Block heights: pill=36, gymName=46, official=18, memberName=42, id=18, label=16, expiry=38 → total=214
            // Remaining: 440-214=226, per gap=226/6≈37px
            const gap = 30;
            let y = qy;

            // 1. Badge pill (h=36)
            const paket = "{{ session('paket_nama') }}".toUpperCase();
            ctx.font = "700 17px Poppins";
            const pw = ctx.measureText(paket).width;
            const pillPad = 13, pillH = 36, pillR = 18;
            const pillW = pw + pillPad * 2;
            ctx.strokeStyle = "#F59E0B"; ctx.lineWidth = 2;
            ctx.beginPath(); ctx.roundRect(lx, y, pillW, pillH, pillR); ctx.stroke();
            ctx.fillStyle = "rgba(245,158,11,0.12)";
            ctx.beginPath(); ctx.roundRect(lx, y, pillW, pillH, pillR); ctx.fill();
            ctx.fillStyle = "#F59E0B";
            ctx.textBaseline = "middle";
            ctx.fillText(paket, lx + pillPad, y + pillH / 2);
            ctx.textBaseline = "top";
            y += pillH + gap + 34; // extra space after badge before gym name

            // 2. Gym name (h=46)
            ctx.fillStyle = "#F59E0B";
            ctx.font = "italic 900 55px Poppins";
            ctx.fillText("ARIFAH GYM", lx, y);
            y += 55 + gap;

            // 3. Official member (h=18)
            ctx.fillStyle = "rgba(255,255,255,0.50)";
            ctx.font = "700 18px Poppins";
            ctx.fillText("OFFICIAL MEMBER", lx, y);
            y += 18 + gap;

            // 4. Member name (h=42)
            ctx.fillStyle = "#ffffff";
            ctx.font = "900 42px Poppins";
            ctx.fillText("{{ session('member_name') }}".toUpperCase(), lx, y, maxLW);
            y += 42 + gap;

            // 5. Member ID (h=18)
            ctx.fillStyle = "rgba(255,255,255,0.35)";
            ctx.font = "400 18px monospace";
            ctx.fillText("ID: {{ session('member_id') }}", lx, y);
            y += 18 + gap;

            // 6. Berlaku hingga label (h=16)
            ctx.strokeStyle = "rgba(255,255,255,0.08)"; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(lx, y - 8); ctx.lineTo(lx + maxLW, y - 8); ctx.stroke();
            ctx.fillStyle = "rgba(255,255,255,0.40)";
            ctx.font = "700 16px Poppins";
            ctx.fillText("BERLAKU HINGGA", lx, y);
            y += 16 + gap;

            // 7. Expiry date (h=38) — should land at ≈ 495 = qy+qs ✓
            ctx.fillStyle = "#F59E0B";
            ctx.font = "900 38px Poppins";
            ctx.fillText("{{ session('expiry_date') }}".toUpperCase(), lx, y);

            // ── Right: QR ──
            const img = new Image();
            img.crossOrigin = "anonymous";
            img.src = document.getElementById('qrSrc').src;
            img.onload = function() {
                // White box glow — x=620..1030, y=50..460
                ctx.save();
                ctx.shadowColor = 'rgba(245,158,11,0.65)';
                ctx.shadowBlur = 45;
                ctx.fillStyle = "#ffffff";
                ctx.beginPath(); ctx.roundRect(qx, qy, qs, qs, qr); ctx.fill();
                ctx.restore();

                ctx.strokeStyle = 'rgba(245,158,11,0.55)'; ctx.lineWidth = 3;
                ctx.beginPath(); ctx.roundRect(qx, qy, qs, qs, qr); ctx.stroke();

                const pad = 20;
                ctx.drawImage(img, qx + pad, qy + pad, qs - pad*2, qs - pad*2);

                const link = document.createElement('a');
                link.download = 'ArifahGym-{{ session("member_name") }}.png';
                link.href = canvas.toDataURL('image/png', 1.0);
                link.click();
                btn.innerHTML = '<span class="relative flex items-center justify-center gap-2"><i class="fa-solid fa-download text-[#F59E0B] group-hover/btn:text-black transition-colors duration-300"></i><span class="group-hover/btn:text-black transition-colors duration-300">SIMPAN</span></span>';
                btn.disabled = false;
            };
        }
    </script>
</body>
</html>