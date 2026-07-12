<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daftar Member - ARIFAH GYM</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f97316;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f3f4f6;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .status-aktif {
            color: #10b981;
            font-weight: bold;
        }
        .status-pendaftar {
            color: #fbbf24;
            font-weight: bold;
        }
        .status-expired {
            color: #ef4444;
            font-weight: bold;
        }
        .status-nonaktif {
            color: #000000;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
        }
        .summary {
            margin-top: 15px;
            padding: 10px;
            background-color: #f3f4f6;
            border-radius: 4px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>DAFTAR MEMBER</h1>
        <p>ARIFAH GYM MAKASSAR</p>
        <p style="font-size: 12px;">Dicetak: {{ \Carbon\Carbon::now('Asia/Makassar')->format('d F Y, H:i') }} WITA</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">No</th>
                <th style="width: 5%; text-align: center;">ID</th>
                <th style="width: 22%;">Nama</th>
                <th style="width: 15%;">WhatsApp</th>
                <th style="width: 16%;">Tipe Member</th>
                <th style="width: 12%; text-align: center;">Bergabung</th>
                <th style="width: 12%; text-align: center;">Berakhir</th>
                <th style="width: 14%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $no = 1;
                $today = \Carbon\Carbon::now('Asia/Makassar')->startOfDay();
            @endphp
            
            @foreach($data as $member)
                @php
                    // Tentukan status
                    if (!$member->is_active && !$member->expiry_date) {
                        $status = 'Pendaftar Baru';
                        $statusClass = 'status-pendaftar';
                    } elseif (!$member->is_active && $member->expiry_date) {
                        $expiry = \Carbon\Carbon::parse($member->expiry_date)->startOfDay();
                        if ($today->gt($expiry)) {
                            $status = 'Masa Aktif Habis';
                            $statusClass = 'status-expired';
                        } else {
                            $status = 'Non-Aktif';
                            $statusClass = 'status-nonaktif';
                        }
                    } elseif ($member->is_active) {
                        $status = 'Aktif';
                        $statusClass = 'status-aktif';
                    } else {
                        $status = 'Non-Aktif';
                        $statusClass = 'status-nonaktif';
                    }
                @endphp
                
                <tr>
                    <td style="text-align: center;">{{ $no++ }}</td>
                    <td style="text-align: center;">{{ $member->id }}</td>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->phone }}</td>
                    <td>{{ $member->type }}</td>
                    <td style="text-align: center;">
                        {{ $member->join_date ? \Carbon\Carbon::parse($member->join_date)->format('d/m/Y') : '-' }}
                    </td>
                    <td style="text-align: center;">
                        {{ $member->expiry_date ? \Carbon\Carbon::parse($member->expiry_date)->format('d/m/Y') : '-' }}
                    </td>
                    <td style="text-align: center;">
                        <span class="{{ $statusClass }}">{{ $status }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p style="margin: 0;">TOTAL MEMBER: {{ $data->count() }} orang</p>
        <p style="margin: 5px 0 0 0; font-size: 11px; font-weight: normal;">
            Aktif: {{ $data->where('is_active', true)->count() }} | 
            Pendaftar Baru: {{ $data->where('is_active', false)->whereNull('expiry_date')->count() }} | 
            Non-Aktif/Expired: {{ $data->where('is_active', false)->whereNotNull('expiry_date')->count() }}
        </p>
    </div>

    <div class="footer">
        <p>Dokumen ini dicetak secara otomatis dari sistem ARIFAH GYM</p>
        <p>Makassar. Jln Skarda N, No.13</p>
        <br>
        <div style="text-align: right; margin-top: 30px; margin-right: 50px;">
            <p>Makassar, {{ \Carbon\Carbon::now('Asia/Makassar')->translatedFormat('d F Y') }}</p>
            <br><br><br>
            <p><strong>Owner MUSDHALIFAH</strong></p>
        </div>
    </div>

    <script>
        // Auto print saat halaman dibuka
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
