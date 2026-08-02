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
        .text-center { text-align: center; }
        .footer { margin-top: 30px; font-size: 9px; text-align: right; color: #777; }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>SIMANDA — Sistem Monitoring Anggaran & Dokumen Kegiatan</h2>
        <h3>{{ $reportTitle }}</h3>
    </div>
    <div class="meta">
        <strong>Tanggal Cetak:</strong> {{ $exportedAt }} &bull; <strong>Pencetak:</strong> {{ $exportedBy }}
    </div>
    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th class="text-right">Realisasi Verified Bulan Ini (Rp)</th>
                <th class="text-right">Realisasi Verified Kumulatif (Rp)</th>
                <th class="text-right">Total Pagu (Rp)</th>
                <th class="text-center">Serapan Kumulatif (%)</th>
                <th class="text-right">Sisa Anggaran (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['rows'] as $r)
            <tr>
                <td>{{ $r['month_name'] }}</td>
                <td class="text-right">{{ number_format($r['monthly_verified'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($r['cumulative_verified'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($r['total_pagu'], 0, ',', '.') }}</td>
                <td class="text-center">{{ $r['cumulative_absorption_percentage'] }}%</td>
                <td class="text-right">{{ number_format($r['remaining_budget'], 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">Dokumen ini dihasilkan secara otomatis oleh sistem SIMANDA.</div>
</body>
</html>
