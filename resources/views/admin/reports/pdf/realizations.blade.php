<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; }
        .meta { margin-bottom: 15px; font-size: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; font-size: 9px; text-align: right; color: #777; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>SIMANDA — Sistem Monitoring Anggaran & Dokumen Kegiatan</h2>
        <h3>{{ $reportTitle }}</h3>
    </div>

    <div class="meta">
        <strong>Tanggal Cetak:</strong> {{ $exportedAt }} &bull; 
        <strong>Pencetak:</strong> {{ $exportedBy }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tgl Transaksi</th>
                <th>No Bukti</th>
                <th>Kegiatan & Unit</th>
                <th>Uraian RAB</th>
                <th>Penerima</th>
                <th class="text-right">Bruto (Rp)</th>
                <th class="text-right">Pajak (Rp)</th>
                <th class="text-right">Bersih (Rp)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($realizations as $index => $rel)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $rel->transaction_date ? $rel->transaction_date->format('d/m/Y') : '-' }}</td>
                <td>{{ $rel->receipt_number }}</td>
                <td>{{ $rel->activity ? $rel->activity->activity_name : '-' }}</td>
                <td>{{ $rel->budgetPlan ? $rel->budgetPlan->description : '-' }}</td>
                <td>{{ $rel->recipient_name ?? '-' }}</td>
                <td class="text-right">{{ number_format($rel->gross_amount, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($rel->tax_amount, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($rel->net_amount, 0, ',', '.') }}</td>
                <td>{{ $rel->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh sistem SIMANDA.
    </div>
</body>
</html>
