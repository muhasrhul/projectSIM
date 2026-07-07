<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARIFAH Gym - Scan QR Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #000; }
        .font-hero { font-family: 'Poppins', sans-serif; font-weight: 900; font-style: italic; text-transform: uppercase; }
        .bg-gym {
            background-image: linear-gradient(to bottom, rgba(0,0,0,0.88), rgba(0,0,0,0.96)),
                url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1000&auto=format&fit=crop');
            background-size: cover; background-position: center;
        }
        .premium-card {
            background: rgba(15,15,15,0.8);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.06);
        }
        #scanner-box {
            position: relative; width: 100%; max-width: 320px;
            aspect-ratio: 1; border-radius: 1.5rem; overflow: hidden;
            border: 2px solid rgba(9,146,194,0.4);
            box-shadow: 0 0 40px rgba(9,146,194,0.2);
        }
        #video { width: 100%; height: 100%; object-fit: cover; display: block; }
        .scan-line {
            position: absolute; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, #0992C2, transparent);
            animation: scan 2s linear infinite;
        }
        @keyframes scan { 0% { top: 5%; } 50% { top: 90%; } 100% { top: 5%; } }
        .corner { position: absolute; width: 20px; height: 20px; border-color: #0992C2; border-style: solid; }
        .corner-tl { top: 12px; left: 12px; border-width: 3px 0 0 3px; border-radius: 4px 0 0 0; }
        .corner-tr { top: 12px; right: 12px; border-width: 3px 3px 0 0; border-radius: 0 4px 0 0; }
        .corner-bl { bottom: 12px; left: 12px; border-width: 0 0 3px 3px; border-radius: 0 0 0 4px; }
        .corner-br { bottom: 12px; right: 12px; border-width: 0 3px 3px 0; border-radius: 0 0 4px 0; }
        .result-card { animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        @keyframes popIn { 0% { transform: scale(0.8); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body class="bg-gym text-white min-h-screen flex items-center justify-center p-4">
    <canvas id="qrCanvas" style="display:none;position:absolute;"></canvas>

    <div class="w-full max-w-sm">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-hero tracking-tighter">ARIFAH <span class="text-[#0992C2]">GYM</span></h1>
            <div class="flex items-center justify-center gap-3 mt-2">
                <div class="h-px w-8 bg-zinc-800"></div>
                <p class="text-zinc-500 text-[10px] uppercase tracking-[0.5em] font-black italic">SCAN QR ABSENSI</p>
                <div class="h-px w-8 bg-zinc-800"></div>
            </div>
        </div>

        <!-- Result Area -->
        <div id="result-area" class="mb-6 hidden"></div>

        <!-- Scanner Card -->
        <div id="scanner-card" class="premium-card p-6 rounded-[2.5rem]">
            <p class="text-zinc-400 text-xs uppercase tracking-widest text-center mb-5 font-bold">Arahkan kamera ke QR kartu member</p>
            <div class="flex justify-center mb-5">
                <div id="scanner-box">
                    <video id="video" autoplay playsinline muted></video>
                    <div class="scan-line"></div>
                    <div class="corner corner-tl"></div>
                    <div class="corner corner-tr"></div>
                    <div class="corner corner-bl"></div>
                    <div class="corner corner-br"></div>
                </div>
            </div>
            <div id="status-text" class="text-center text-zinc-500 text-xs uppercase tracking-widest font-bold">
                <i class="fa-solid fa-camera mr-1"></i> Memulai kamera...
            </div>
        </div>

        <!-- Mode Toggle -->
        <div class="mt-4 flex items-center justify-center gap-4">
            <button onclick="setMode('camera')" id="btn-camera"
                class="text-[#0992C2] text-xs uppercase tracking-widest font-bold transition-colors">
                <i class="fa-solid fa-camera mr-1"></i> Kamera
            </button>
            <div class="h-3 w-px bg-zinc-700"></div>
            <button onclick="setMode('scanner')" id="btn-scanner"
                class="text-zinc-600 text-xs uppercase tracking-widest font-bold hover:text-zinc-400 transition-colors">
                <i class="fa-solid fa-barcode mr-1"></i> Alat Scanner
            </button>
            <div class="h-3 w-px bg-zinc-700"></div>
            <a href="/absen" class="text-zinc-600 text-xs uppercase tracking-widest hover:text-zinc-400 transition-colors">
                <i class="fa-solid fa-keyboard mr-1"></i> Manual
            </a>
        </div>

        <!-- Scanner Hardware Mode -->
        <div id="hardware-scanner-card" class="premium-card p-6 rounded-[2.5rem] mt-4 hidden">
            <p class="text-zinc-400 text-xs uppercase tracking-widest text-center mb-5 font-bold">
                Scan barcode kartu member dengan alat scanner
            </p>
            <div class="flex flex-col items-center gap-4">
                <div class="w-20 h-20 rounded-full border-2 border-[#0992C2]/40 flex items-center justify-center"
                    style="box-shadow: 0 0 30px rgba(9,146,194,0.15)">
                    <i class="fa-solid fa-barcode text-[#0992C2] text-3xl"></i>
                </div>
                <div id="hw-status" class="text-center text-zinc-500 text-xs uppercase tracking-widest font-bold">
                    <i class="fa-solid fa-circle-dot mr-1 text-green-500"></i> Siap menerima scan...
                </div>
                <!-- Input tersembunyi yang menangkap input scanner -->
                <input id="scanner-input" type="text" autocomplete="off"
                    class="opacity-0 absolute w-px h-px"
                    placeholder="scanner input" />
                <p class="text-zinc-700 text-[10px] text-center">
                    Pastikan alat scanner terhubung dan halaman ini aktif
                </p>
            </div>
        </div>

        <p class="mt-8 text-zinc-900 text-[10px] uppercase tracking-[0.8em] font-black italic text-center">ARIFAH GYM &copy; 2026</p>
    </div>

    <audio id="successSound" src="https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3"></audio>
    <audio id="errorSound" src="https://assets.mixkit.co/active_storage/sfx/2572/2572-preview.mp3"></audio>

    <script>
        const video       = document.getElementById('video');
        const canvas      = document.getElementById('qrCanvas');
        const ctx         = canvas.getContext('2d');
        const statusText  = document.getElementById('status-text');
        const resultArea  = document.getElementById('result-area');

        let cooldown    = false;
        let lastScan    = 0;
        const SCAN_INTERVAL = 150;

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
        }).then(stream => {
            video.srcObject = stream;
            video.play();
            statusText.innerHTML = '<i class="fa-solid fa-qrcode mr-1 text-[#0992C2]"></i> Siap scan...';
            requestAnimationFrame(tick);
        }).catch(() => {
            statusText.innerHTML = '<i class="fa-solid fa-exclamation-triangle mr-1 text-red-500"></i> Kamera tidak bisa diakses';
        });

        function tick(timestamp) {
            requestAnimationFrame(tick);
            if (cooldown) return;
            if (timestamp - lastScan < SCAN_INTERVAL) return;
            if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
            lastScan = timestamp;

            const size = Math.min(video.videoWidth, video.videoHeight) * 0.6;
            const sx = (video.videoWidth - size) / 2;
            const sy = (video.videoHeight - size) / 2;
            canvas.width = size;
            canvas.height = size;
            ctx.drawImage(video, sx, sy, size, size, 0, 0, size, size);

            const imageData = ctx.getImageData(0, 0, size, size);
            const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'attemptBoth' });

            if (code) {
                const memberId = code.data.trim();
                if (/^\d+$/.test(memberId)) {
                    processAbsen(memberId);
                }
            }
        }

        function processAbsen(memberId) {
            cooldown = true;
            statusText.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1 text-[#0992C2]"></i> Memproses...';
            const hwStatus = document.getElementById('hw-status');
            if (hwStatus) hwStatus.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1 text-[#0992C2]"></i> Memproses...';

            fetch('/absen-qr', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ member_id: memberId })
            })
            .then(r => {
                if (r.status === 419) {
                    setTimeout(() => location.reload(), 1000);
                    return null;
                }
                return r.json();
            })
            .then(data => {
                if (!data) return;
                if (data.status === 'success') {
                    document.getElementById('successSound').play().catch(() => {});
                    showSuccess(data);
                } else {
                    document.getElementById('errorSound').play().catch(() => {});
                    showError(data.message);
                }
            })
            .catch(() => showError('Gagal terhubung ke server.'))
            .finally(() => {
                setTimeout(() => {
                    cooldown = false;
                    statusText.innerHTML = '<i class="fa-solid fa-qrcode mr-1 text-[#0992C2]"></i> Siap scan...';
                    const hwStatus = document.getElementById('hw-status');
                    if (hwStatus) hwStatus.innerHTML = '<i class="fa-solid fa-circle-dot mr-1 text-green-500"></i> Siap menerima scan...';
                    if (currentMode === 'scanner') document.getElementById('scanner-input').focus();
                }, 4000);
            });
        }

        function showSuccess(data) {
            const badgeIcon = data.badge === 'ARIFAH WARRIOR' ? 'fa-fire text-[#0992C2]' : 'fa-medal text-blue-400';
            resultArea.innerHTML = `
                <div class="result-card premium-card p-6 rounded-[2.5rem] text-center border border-green-500/20">
                    <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fa-solid fa-check text-black text-xl"></i>
                    </div>
                    <h2 class="text-xl font-hero text-green-400 mb-1">ABSEN BERHASIL</h2>
                    <p class="text-zinc-500 text-[10px] uppercase tracking-widest mb-4 font-bold">
                        ${new Date().toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'})} WITA
                    </p>
                    <h3 class="text-lg font-black uppercase truncate text-white mb-1">${data.member_name}</h3>
                    <p class="text-[10px] text-zinc-500 font-mono mb-4">ID: ${data.member_id} &bull; ${data.paket_nama}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white/[0.03] p-3 rounded-2xl border border-white/5">
                            <p class="text-[8px] text-zinc-500 uppercase font-black mb-1">SESI BULAN INI</p>
                            <p class="text-2xl font-black italic">${data.total_latihan}<span class="text-xs text-green-500 ml-1">X</span></p>
                        </div>
                        <div class="bg-white/[0.03] p-3 rounded-2xl border border-white/5 flex flex-col items-center justify-center">
                            <i class="fa-solid ${badgeIcon} text-lg mb-1"></i>
                            <p class="text-[9px] font-black uppercase">${data.badge}</p>
                        </div>
                    </div>
                </div>`;
            resultArea.classList.remove('hidden');
            setTimeout(() => resultArea.classList.add('hidden'), 4000);
            statusText.innerHTML = '<i class="fa-solid fa-check mr-1 text-green-400"></i> Berhasil! Siap scan berikutnya...';
        }

        function showError(message) {
            resultArea.innerHTML = `
                <div class="result-card premium-card p-5 rounded-[2rem] text-center border border-red-500/20">
                    <div class="w-10 h-10 bg-red-600/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <i class="fa-solid fa-xmark text-red-500"></i>
                    </div>
                    <p class="text-sm font-black text-red-400 uppercase tracking-wide">${message}</p>
                </div>`;
            resultArea.classList.remove('hidden');
            setTimeout(() => resultArea.classList.add('hidden'), 4000);
            statusText.innerHTML = '<i class="fa-solid fa-qrcode mr-1 text-[#0992C2]"></i> Siap scan...';
        }

        // ── MODE TOGGLE ──────────────────────────────────────────────
        let currentMode = 'camera';

        function setMode(mode) {
            currentMode = mode;
            const cameraCard    = document.getElementById('scanner-card');
            const hardwareCard  = document.getElementById('hardware-scanner-card');
            const btnCamera     = document.getElementById('btn-camera');
            const btnScanner    = document.getElementById('btn-scanner');

            if (mode === 'camera') {
                cameraCard.classList.remove('hidden');
                hardwareCard.classList.add('hidden');
                btnCamera.classList.replace('text-zinc-600', 'text-[#0992C2]');
                btnScanner.classList.replace('text-[#0992C2]', 'text-zinc-600');
            } else {
                cameraCard.classList.add('hidden');
                hardwareCard.classList.remove('hidden');
                btnScanner.classList.replace('text-zinc-600', 'text-[#0992C2]');
                btnCamera.classList.replace('text-[#0992C2]', 'text-zinc-600');
                // Fokus ke input tersembunyi agar scanner langsung terbaca
                document.getElementById('scanner-input').focus();
            }
        }

        // ── HARDWARE SCANNER INPUT ───────────────────────────────────
        // Scanner USB/Bluetooth bekerja seperti keyboard: ketik lalu Enter
        let scannerBuffer = '';
        let scannerTimer  = null;

        document.addEventListener('keydown', function (e) {
            if (currentMode !== 'scanner') return;
            if (cooldown) return;

            // Enter = scanner selesai kirim data
            if (e.key === 'Enter') {
                const val = scannerBuffer.trim();
                scannerBuffer = '';
                clearTimeout(scannerTimer);
                if (val && /^\d+$/.test(val)) {
                    processAbsen(val);
                }
                return;
            }

            // Kumpulkan karakter dari scanner
            if (e.key.length === 1) {
                scannerBuffer += e.key;
            }

            // Reset buffer jika tidak ada input selama 500ms
            clearTimeout(scannerTimer);
            scannerTimer = setTimeout(() => { scannerBuffer = ''; }, 500);
        });

        // Jaga fokus input tersembunyi saat mode scanner aktif
        document.addEventListener('click', function () {
            if (currentMode === 'scanner') {
                document.getElementById('scanner-input').focus();
            }
        });
    </script>
</body>
</html>
