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
                <th>No</th>
                <th>Kode</th>
                <th>Nama Kegiatan</th>
                <th>Unit</th>
                <th>PPTK</th>
                <th class="text-right">Pagu (Rp)</th>
                <th class="text-right">Verified (Rp)</th>
                <th class="text-center">Progres (%)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $index => $act)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $act->activity_code }}</td>
                <td>{{ $act->activity_name }}</td>
                <td>{{ $act->unit ? $act->unit->code : '-' }}</td>
                <td>{{ $act->personInCharge ? $act->personInCharge->name : '-' }}</td>
                <td class="text-right">{{ number_format($act->budget_ceiling, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($act->verified_realization_total, 0, ',', '.') }}</td>
                <td class="text-center">{{ $act->progress_percentage }}%</td>
                <td>{{ $act->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">Dokumen ini dihasilkan secara otomatis oleh sistem SIMANDA.</div>
</body>
</html>
