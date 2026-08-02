@extends('admin.layouts.app')

@section('title', 'Progres Kegiatan Pelaksanaan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Progres Kegiatan Pelaksanaan</h1>
        <div class="page-subtitle">Pemantauan progres fisik kegiatan yang sedang berjalan ({{ $activeYear ? $activeYear->year : '2026' }})</div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="border-bottom:none; margin-bottom:0; padding-bottom:0;">
        <form method="GET" action="{{ route('realizations.progress') }}" style="display:flex; gap:12px; width:100%; flex-wrap:wrap;">
            @if(auth()->user()->isAdmin() || auth()->user()->isPimpinan())
            <div style="min-width:180px;">
                <select name="unit_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Unit --</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->code }} - {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div style="flex:1; min-width:200px;">
                <input type="text" name="search" class="form-control" placeholder="Cari kegiatan..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn btn-secondary">Cari</button>
        </form>
    </div>

    <div class="table-responsive" style="margin-top:16px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kegiatan</th>
                    <th>Unit & PPTK</th>
                    <th>Status Hari Ini</th>
                    <th style="width:250px;">Progres Fisik (%)</th>
                    <th>Catatan Terakhir</th>
                    <th>Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                <tr>
                    <td><code>{{ $act->activity_code }}</code></td>
                    <td>
                        <a href="{{ route('activities.show', $act->id) }}" style="font-weight:700; color:var(--accent-color); text-decoration:none;">
                            {{ $act->activity_name }}
                        </a>
                    </td>
                    <td>
                        <strong>{{ $act->unit ? $act->unit->code : '-' }}</strong><br>
                        <small style="color:var(--text-muted);">{{ $act->personInCharge ? $act->personInCharge->name : '-' }}</small>
                    </td>
                    <td>
                        @switch($act->status)
                            @case('planned') <span class="badge badge-success">Direncanakan</span> @break
                            @case('ongoing') <span class="badge badge-info">Sedang Berjalan</span> @break
                        @endswitch
                    </td>
                    <td>
                        <div style="display:flex; justify-content:space-between; font-size:0.78rem; margin-bottom:4px;">
                            <span>Capaian</span>
                            <strong>{{ $act->progress_percentage }}%</strong>
                        </div>
                        <div style="background-color:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                            <div style="background-color:#8b5cf6; width:{{ $act->progress_percentage }}%; height:100%;"></div>
                        </div>
                    </td>
                    <td><small>{{ $act->progress_note ?? 'Belum ada catatan' }}</small></td>
                    <td>
                        <a href="{{ route('activities.show', $act->id) }}" class="btn btn-secondary btn-sm">Kelola Progres</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; color: var(--text-muted); padding:32px;">Tidak ada kegiatan sedang berjalan/direncanakan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $activities->links() }}
    </div>
</div>
@endsection
