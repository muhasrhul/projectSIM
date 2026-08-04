<!DOCTYPE html>
<html>
<head>
    <title>Laporan Arus Kas - ARIFAH GYM</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 15mm;
        }
        body { 
            font-family: sans-serif; 
            padding: 20px; 
            color: #333; 
            font-size: 11px; 
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px; 
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px; 
            text-align: left; 
            font-size: 10px; 
        }
        th { 
            background-color: #f97316; 
            color: white; 
            font-weight: bold; 
            text-align: center;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #444; 
            padding-bottom: 10px; 
        }
        .summary { 
            margin-top: 20px; 
            display: flex; 
            justify-content: space-between; 
        }
        .summary-box {
            border: 1px solid #ddd;
            padding: 10px;
            width: 30%;
            text-align: center;
        }
        .income { 
            background-color: #dcfce7; 
            color: #166534; 
        }
        .expense { 
            background-color: #fee2e2; 
            color: #991b1b; 
        }
        .balance { 
            background-color: #dbeafe; 
            color: #1e40af; 
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }
        .text-primary { color: #2563eb; }
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-member { background-color: #e5e7eb; color: #374151; }
        .badge-kasir { background-color: #e5e7eb; color: #374151; }
        .badge-expense { background-color: #e5e7eb; color: #374151; }
        @media print { 
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">ARIFAH GYM MAKASSAR</h1>
        <h2 style="margin: 5px 0; color: #555; font-size: 18px;">LAPORAN ARUS KAS</h2>
        <h3 style="margin: 5px 0; color: #f97316; font-size: 16px;">{{ $periodLabel }}</h3>
        <p style="margin: 0; font-size: 12px;">Tanggal Cetak: {{ $generatedAt }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 10%;">Sumber</th>
                <th style="width: 8%;">Tipe</th>
                <th style="width: 30%;">Keterangan</th>
                <th style="width: 12%;">Pemasukan</th>
                <th style="width: 12%;">Pengeluaran</th>
                <th style="width: 11%;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @forelse($data as $record)
            <tr>
                <td class="text-center">{{ $no++ }}</td>
                <td class="text-center">{{ \Carbon\Carbon::parse($record->date)->format('d/m/Y H:i') }}</td>
                <td class="text-center">
                    <span class="badge 
                        @if($record->source === 'member') badge-member
                        @elseif($record->source === 'kasir') badge-kasir  
                        @else badge-expense
                        @endif">
                        @if($record->source === 'member') Member
                        @elseif($record->source === 'kasir') Kasir
                        @else Cat. Pengeluaran
                        @endif
                    </span>
                </td>
                <td class="text-center">
                    @if($record->type === 'income')
                        <span style="color: #059669; font-weight: bold;">Pemasukan</span>
                    @else
                        <span style="color: #dc2626; font-weight: bold;">Pengeluaran</span>
                    @endif
                </td>
                <td>{{ $record->description }}</td>
                <td class="text-center">
                    @if($record->type === 'income')
                        <span class="text-success">Rp {{ number_format($record->amount, 0, ',', '.') }}</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($record->type === 'expense')
                        <span class="text-danger">Rp {{ number_format($record->amount, 0, ',', '.') }}</span>
                    @endif
                </td>
                <td class="text-center">
                    <span class="text-primary" style="font-weight: bold;">
                        Rp {{ number_format($record->running_balance, 0, ',', '.') }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center" style="padding: 20px; color: #666;">
                    Tidak ada data untuk periode ini
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="summary">
        <div class="summary-box income">
            <h4 style="margin: 0 0 5px 0;">TOTAL PEMASUKAN</h4>
            <p style="margin: 0; font-size: 16px; font-weight: bold;">
                Rp {{ number_format($totalIncome, 0, ',', '.') }}
            </p>
        </div>
        
        <div class="summary-box expense">
            <h4 style="margin: 0 0 5px 0;">TOTAL PENGELUARAN</h4>
            <p style="margin: 0; font-size: 16px; font-weight: bold;">
                Rp {{ number_format($totalExpense, 0, ',', '.') }}
            </p>
        </div>
        
        <div class="summary-box balance">
            <h4 style="margin: 0 0 5px 0;">SALDO AKHIR</h4>
            <p style="margin: 0; font-size: 18px; font-weight: bold;">
                Rp {{ number_format($finalBalance, 0, ',', '.') }}
            </p>
        </div>
    </div>
    
    <div style="margin-top: 40px; text-align: right; font-size: 11px;">
        <p>Makassar, {{ \Carbon\Carbon::now('Asia/Makassar')->format('d F Y') }}</p>
        <br><br><br>
        <p><strong>Owner MUSDHALIFAH</strong></p>
    </div>

    <p class="no-print" style="color: red; margin-top: 20px; font-size: 12px;">
        *Gunakan <strong>Ctrl+P</strong> jika kotak dialog printer tidak muncul otomatis.
    </p>
</body>
</html>