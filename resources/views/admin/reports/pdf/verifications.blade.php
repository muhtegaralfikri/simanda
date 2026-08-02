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
                <th>Waktu</th>
                <th>Putaran</th>
                <th>Tipe Objek</th>
                <th>Keputusan</th>
                <th>Verifier</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($verifications as $index => $v)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $v->verified_at ? $v->verified_at->format('d/m/Y H:i:s') : '-' }}</td>
                <td>Putaran {{ $v->round }}</td>
                <td>{{ class_basename($v->verifiable_type) }}</td>
                <td>{{ $v->decision }}</td>
                <td>{{ $v->verifier ? $v->verifier->name : '-' }}</td>
                <td>{{ $v->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">Dokumen ini dihasilkan secara otomatis oleh sistem SIMANDA.</div>
</body>
</html>
