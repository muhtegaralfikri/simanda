@extends('admin.layouts.app')

@section('title', 'Manajemen Backup SIMANDA')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pencadangan & Pemulihan Data (Backup)</h1>
        <div class="page-subtitle">Manajemen backup database SQLite, dokumen private, checksum SHA-256, dan riwayat verifikasi</div>
    </div>
    <div style="display:flex; gap:8px;">
        <form method="POST" action="{{ route('admin.system.backups.run') }}" style="display:flex; gap:8px;">
            @csrf
            <select name="backup_type" class="form-select" style="min-width:120px;">
                <option value="daily">Daily (Harian)</option>
                <option value="weekly">Weekly (Mingguan)</option>
                <option value="monthly">Monthly (Bulanan)</option>
            </select>
            <button type="submit" class="btn btn-primary">&plus; Jalankan Backup Manual</button>
        </form>
    </div>
</div>

<!-- Summary Card -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left:4px solid var(--accent-color);">
        <div class="stat-label">Backup Terakhir</div>
        <div class="stat-value" style="font-size:1.1rem;">
            {{ $latestSuccessful ? $latestSuccessful->completed_at->format('d/m/Y H:i') : 'Belum Ada' }}
        </div>
        <div class="stat-subtext">{{ $latestSuccessful ? "Tipe: {$latestSuccessful->backup_type}" : '-' }}</div>
    </div>

    <div class="stat-card" style="border-left:4px solid var(--success);">
        <div class="stat-label">Ukuran Database Backup</div>
        <div class="stat-value" style="font-size:1.1rem; color:var(--success);">
            {{ $latestSuccessful ? round($latestSuccessful->database_size / 1024, 1) . ' KB' : '0 KB' }}
        </div>
        <div class="stat-subtext">SQLite Online Backup API</div>
    </div>

    <div class="stat-card" style="border-left:4px solid var(--info);">
        <div class="stat-label">Dokumen Private Ter-backup</div>
        <div class="stat-value" style="font-size:1.1rem; color:var(--info);">
            {{ $latestSuccessful ? $latestSuccessful->document_count . ' File (' . round($latestSuccessful->document_size / (1024*1024), 2) . ' MB)' : '0 File' }}
        </div>
        <div class="stat-subtext">documents.zip Archive</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Riwayat & Log Pencadangan Data</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Waktu Mulai</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Ukuran DB</th>
                    <th>Jumlah Dokumen</th>
                    <th>Operator</th>
                    <th>Tindakan Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $b)
                <tr>
                    <td>{{ $b->started_at ? $b->started_at->format('d/m/Y H:i:s') : '-' }}</td>
                    <td><span class="badge badge-info">{{ strtoupper($b->backup_type) }}</span></td>
                    <td>
                        @switch($b->status)
                            @case('success') <span class="badge badge-success">Sukses</span> @break
                            @case('verified') <span class="badge badge-success" style="background:#059669;">Verified (Valid)</span> @break
                            @case('failed') <span class="badge badge-danger">Gagal</span> @break
                            @default <span class="badge badge-warning">Running</span>
                        @endswitch
                    </td>
                    <td>{{ round($b->database_size / 1024, 1) }} KB</td>
                    <td>{{ $b->document_count }} Berkas ({{ round($b->document_size / (1024*1024), 2) }} MB)</td>
                    <td>{{ $b->creator ? $b->creator->name : 'System Scheduler' }}</td>
                    <td>
                        @if($b->status === 'success' || $b->status === 'verified')
                        <form method="POST" action="{{ route('admin.system.backups.verify', $b->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">&shield; Uji Verifikasi SHA-256</button>
                        </form>
                        @else
                        -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:var(--text-muted); padding:32px;">Belum ada riwayat backup.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($backups, 'links'))
    <div style="margin-top:16px;">
        {{ $backups->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
