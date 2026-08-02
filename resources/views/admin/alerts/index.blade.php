@extends('admin.layouts.app')

@section('title', 'Pusat Peringatan Sistem SIMANDA')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pusat Peringatan Internal</h1>
        <div class="page-subtitle">Daftar pemberitahuan tenggat waktu, revisi, dan status tindakan kegiatan</div>
    </div>
    <div>
        <form method="POST" action="{{ route('admin.alerts.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-secondary">&check; Tandai Semua Sudah Dibaca</button>
        </form>
    </div>
</div>

<!-- Filter Bar -->
<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('admin.alerts.index') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
        <div style="min-width:140px;">
            <select name="severity" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Tingkat --</option>
                <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>Info</option>
                <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Peringatan (Warning)</option>
                <option value="danger" {{ request('severity') == 'danger' ? 'selected' : '' }}>Bahaya (Danger/Terlambat)</option>
            </select>
        </div>

        <div style="min-width:160px;">
            <select name="unread" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Status Baca --</option>
                <option value="1" {{ request('unread') == '1' ? 'selected' : '' }}>Belum Dibaca</option>
            </select>
        </div>

        <div style="min-width:160px;">
            <select name="resolved" class="form-select" onchange="this.form.submit()">
                <option value="0" {{ request('resolved') == '0' || !request()->has('resolved') ? 'selected' : '' }}>Aktif (Perlu Tindakan)</option>
                <option value="1" {{ request('resolved') == '1' ? 'selected' : '' }}>Selesai (Resolved)</option>
            </select>
        </div>

        <button type="submit" class="btn btn-secondary">Filter</button>
        <a href="{{ route('admin.alerts.index') }}" class="btn btn-secondary" style="color:var(--text-muted);">Reset</a>
    </form>
</div>

<div class="card">
    <div style="display:flex; flex-direction:column; gap:12px; padding:16px;">
        @forelse($alerts as $alert)
        @php
            $borderColor = 'var(--accent-color)';
            $badgeClass = 'badge-info';
            if ($alert->severity === 'warning') { $borderColor = 'var(--warning)'; $badgeClass = 'badge-warning'; }
            if ($alert->severity === 'danger') { $borderColor = 'var(--danger)'; $badgeClass = 'badge-danger'; }
        @endphp
        <div style="border-left: 4px solid {{ $borderColor }}; background: {{ $alert->read_at ? '#f8fafc' : '#ffffff' }}; border-radius:6px; padding:14px 18px; border-top:1px solid #e2e8f0; border-right:1px solid #e2e8f0; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
            <div style="flex:1;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                    <span class="badge {{ $badgeClass }}">{{ strtoupper($alert->severity) }}</span>
                    <strong style="font-size:1rem;">{{ $alert->title }}</strong>
                    @if($alert->resolved_at)
                        <span class="badge badge-success">Selesai</span>
                    @endif
                    @if(! $alert->read_at)
                        <span class="badge badge-info" style="background:#3b82f6;">Baru</span>
                    @endif
                </div>
                <div style="font-size:0.9rem; color:#334155; margin-bottom:6px;">{{ $alert->message }}</div>
                <div style="font-size:0.75rem; color:var(--text-muted);">
                    Tenggat: <strong>{{ $alert->due_date ? $alert->due_date->format('d/m/Y') : '-' }}</strong> &bull; Dibuat: {{ $alert->created_at->diffForHumans() }}
                </div>
            </div>

            <div style="display:flex; gap:8px; align-items:center;">
                @if($alert->action_url)
                    <a href="{{ $alert->action_url }}" class="btn btn-primary btn-sm">Buka Tindakan</a>
                @endif
                @if(! $alert->read_at)
                    <form method="POST" action="{{ route('admin.alerts.read', $alert->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">&check; Dibaca</button>
                    </form>
                @endif
            </div>
        </div>
        @empty
        <div style="text-align:center; padding:40px; color:var(--text-muted);">
            Tidak ada peringatan sistem saat ini.
        </div>
        @endforelse
    </div>

    @if(method_exists($alerts, 'links'))
    <div style="margin-top:16px;">
        {{ $alerts->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
