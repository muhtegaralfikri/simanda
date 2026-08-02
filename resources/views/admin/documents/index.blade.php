@extends('admin.layouts.app')

@section('title', 'Dokumen Kegiatan & Kelengkapan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Dokumen Kegiatan & Kelengkapan</h1>
        <div class="page-subtitle">Pemantauan persentase kelengkapan dokumen per kegiatan ({{ $activeYear ? $activeYear->year : '2026' }})</div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="border-bottom:none; margin-bottom:0; padding-bottom:0;">
        <form method="GET" action="{{ route('documents.index') }}" style="display:flex; gap:12px; width:100%; flex-wrap:wrap;">
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
                    <th style="width:250px;">Kelengkapan Dokumen Wajib (%)</th>
                    <th>Status Berkas</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                @php $comp = $act->document_completeness; @endphp
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
                        <div style="display:flex; justify-content:space-between; font-size:0.78rem; margin-bottom:4px;">
                            <span>{{ $comp['fulfilled_required'] }}/{{ $comp['total_required'] }} Dokumen Wajib</span>
                            <strong>{{ $comp['percentage'] }}%</strong>
                        </div>
                        <div style="background-color:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                            <div style="background-color:{{ $comp['percentage'] == 100 ? 'var(--success)' : 'var(--warning)' }}; width:{{ $comp['percentage'] }}%; height:100%;"></div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-info">{{ $act->documents->where('is_current', true)->count() }} Berkas Terunggah</span>
                    </td>
                    <td>
                        <a href="{{ route('activities.show', $act->id) }}" class="btn btn-secondary btn-sm">Kelola Dokumen</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color: var(--text-muted); padding:32px;">Belum ada kegiatan untuk kelengkapan dokumen.</td>
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
