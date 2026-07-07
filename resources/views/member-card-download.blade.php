<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Member - {{ $member->name }}</title>
    <script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js"></script>
    <style>
        body { margin: 0; background: #000; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Poppins', sans-serif; }
        #status { color: rgba(255,255,255,0.4); font-size: 11px; letter-spacing: 0.2em; text-transform: uppercase; text-align: center; }
        .font-loader { position: absolute; visibility: hidden; height: 0; width: 0; font-family: 'Poppins'; }
    </style>
</head>
<body>
    <div class="font-loader" style="font-weight: 400;">.</div>
    <div class="font-loader" style="font-weight: 900; font-style: italic;">.</div>

    <div>
        <p id="status">⏳ Menyiapkan kartu...</p>
    </div>

    <img id="qrSrc"
         src="{{ $qrBase64 }}"
         style="display:none;">

    <canvas id="finalCanvas" width="1050" height="550" style="display:none;"></canvas>

    <script>
        WebFont.load({
            google: { families: ['Poppins:400,700,900,900i'] },
            active: function() { generateAndDownload(); },
            inactive: function() { generateAndDownload(); }
        });

        async function generateAndDownload() {
            const status = document.getElementById('status');
            const canvas = document.getElementById('finalCanvas');
            const ctx = canvas.getContext('2d');

            status.textContent = '⏳ Membuat kartu...';

            canvas.width = 1050; canvas.height = 550;

            await document.fonts.ready;

            const W = 1050, H = 550, R = 48;

            // 1. Background
            ctx.save();
            ctx.beginPath();
            ctx.roundRect(0, 0, W, H, R);
            ctx.clip();

            const grad = ctx.createLinearGradient(0, 0, W, H);
            grad.addColorStop(0, '#0d1b2a');
            grad.addColorStop(0.5, '#0a1628');
            grad.addColorStop(1, '#0d1b2a');
            ctx.fillStyle = grad;
            ctx.fillRect(0, 0, W, H);

            // 2. Blue glow top-right
            const flare = ctx.createRadialGradient(W, 0, 0, W, 0, 500);
            flare.addColorStop(0, 'rgba(9,146,194,0.25)');
            flare.addColorStop(1, 'transparent');
            ctx.fillStyle = flare;
            ctx.fillRect(0, 0, W, H);

            // 3. Blue glow bottom-left
            const flare2 = ctx.createRadialGradient(0, H, 0, 0, H, 350);
            flare2.addColorStop(0, 'rgba(9,146,194,0.10)');
            flare2.addColorStop(1, 'transparent');
            ctx.fillStyle = flare2;
            ctx.fillRect(0, 0, W, H);

            // 4. Dumbbell watermark
            ctx.save();
            ctx.globalAlpha = 0.055;
            ctx.translate(160, H / 2 + 30);
            ctx.rotate(-12 * Math.PI / 180);
            ctx.fillStyle = "#ffffff";
            const bar = { w: 160, h: 18 };
            const plate = { w: 28, h: 80 };
            const grip = { w: 18, h: 50 };
            ctx.fillRect(-bar.w/2, -bar.h/2, bar.w, bar.h);
            ctx.fillRect(-bar.w/2 - grip.w - plate.w, -plate.h/2, plate.w, plate.h);
            ctx.fillRect(-bar.w/2 - grip.w, -grip.h/2, grip.w, grip.h);
            ctx.fillRect(bar.w/2 + grip.w, -plate.h/2, plate.w, plate.h);
            ctx.fillRect(bar.w/2, -grip.h/2, grip.w, grip.h);
            ctx.restore();

            ctx.restore();

            // 5. Card border
            ctx.save();
            ctx.beginPath();
            ctx.roundRect(0, 0, W, H, R);
            ctx.strokeStyle = 'rgba(9,146,194,0.3)';
            ctx.lineWidth = 2;
            ctx.stroke();
            ctx.restore();

            const lx = 50;
            const maxLW = 490;
            const qx = 560, qy = 55, qs = 440, qr = 22;
            const gap = 30;
            let y = qy;

            // Badge pill
            const paket = "{{ strtoupper($member->type ?? 'MEMBER') }}";
            ctx.font = "700 17px Poppins";
            const pw = ctx.measureText(paket).width;
            const pillPad = 13, pillH = 36, pillR = 18;
            const pillW = pw + pillPad * 2;
            ctx.strokeStyle = "#0992C2"; ctx.lineWidth = 2;
            ctx.beginPath(); ctx.roundRect(lx, y, pillW, pillH, pillR); ctx.stroke();
            ctx.fillStyle = "rgba(9,146,194,0.12)";
            ctx.beginPath(); ctx.roundRect(lx, y, pillW, pillH, pillR); ctx.fill();
            ctx.fillStyle = "#0992C2";
            ctx.textBaseline = "middle";
            ctx.fillText(paket, lx + pillPad, y + pillH / 2);
            ctx.textBaseline = "top";
            y += pillH + gap + 34;

            // Gym name
            ctx.fillStyle = "#0992C2";
            ctx.font = "italic 900 55px Poppins";
            ctx.fillText("{{ strtoupper(config('app.name', 'GYM')) }}", lx, y);
            y += 55 + gap;

            // Official member
            ctx.fillStyle = "rgba(255,255,255,0.50)";
            ctx.font = "700 18px Poppins";
            ctx.fillText("OFFICIAL MEMBER", lx, y);
            y += 18 + gap;

            // Member name
            ctx.fillStyle = "#ffffff";
            ctx.font = "900 42px Poppins";
            ctx.fillText("{{ strtoupper($member->name) }}", lx, y, maxLW);
            y += 42 + gap;

            // Member ID
            ctx.fillStyle = "rgba(255,255,255,0.35)";
            ctx.font = "400 18px monospace";
            ctx.fillText("ID: {{ $member->id }}", lx, y);
            y += 18 + gap;

            // Divider + berlaku hingga label
            ctx.strokeStyle = "rgba(255,255,255,0.08)"; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(lx, y - 8); ctx.lineTo(lx + maxLW, y - 8); ctx.stroke();
            ctx.fillStyle = "rgba(255,255,255,0.40)";
            ctx.font = "700 16px Poppins";
            ctx.fillText("BERLAKU HINGGA", lx, y);
            y += 16 + gap;

            // Expiry date
            ctx.fillStyle = "{{ $isExpired ? '#ef4444' : '#0992C2' }}";
            ctx.font = "900 38px Poppins";
            ctx.fillText("{{ strtoupper(\Carbon\Carbon::parse($member->expiry_date)->translatedFormat('d F Y')) }}", lx, y);

            @if($isExpired)
            // Stempel EXPIRED
            ctx.save();
            ctx.translate(W / 2, H / 2);
            ctx.rotate(-15 * Math.PI / 180);
            ctx.strokeStyle = "#ef4444"; ctx.lineWidth = 8;
            ctx.beginPath(); ctx.roundRect(-180, -55, 360, 110, 12); ctx.stroke();
            ctx.fillStyle = "rgba(239,68,68,0.12)"; ctx.fill();
            ctx.fillStyle = "#ef4444";
            ctx.font = "900 72px Poppins";
            ctx.textAlign = "center"; ctx.textBaseline = "middle";
            ctx.fillText("EXPIRED", 0, 0);
            ctx.restore();
            @endif

            // QR sudah base64, langsung render
            status.textContent = '⏳ Menambahkan QR code...';
            const img = document.getElementById('qrSrc');

            function doDownload() {
                ctx.save();
                ctx.shadowColor = 'rgba(9,146,194,0.65)';
                ctx.shadowBlur = 45;
                ctx.fillStyle = "#ffffff";
                ctx.beginPath(); ctx.roundRect(qx, qy, qs, qs, qr); ctx.fill();
                ctx.restore();

                ctx.strokeStyle = 'rgba(9,146,194,0.55)'; ctx.lineWidth = 3;
                ctx.beginPath(); ctx.roundRect(qx, qy, qs, qs, qr); ctx.stroke();

                const pad = 20;
                ctx.drawImage(img, qx + pad, qy + pad, qs - pad*2, qs - pad*2);

                const link = document.createElement('a');
                link.download = 'Kartu-Member-{{ $member->name }}.png';
                link.href = canvas.toDataURL('image/png', 1.0);
                link.click();

                status.textContent = '✅ Kartu berhasil didownload!';
                setTimeout(() => window.close(), 1500);
            }

            // QR sudah base64, langsung eksekusi tanpa tunggu onload
            doDownload();
        }
    </script>
</body>
</html>
