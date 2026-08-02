@extends('admin.layouts.app')

@section('title', 'Antrean & Modul Verifikasi')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Antrean & Modul Verifikasi</h1>
        <div class="page-subtitle">Verifikasi pengajuan kegiatan, realisasi transaksi, dan keabsahan dokumen ({{ $activeYear ? $activeYear->year : '2026' }})</div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="border-bottom:none; margin-bottom:0; padding-bottom:0;">
        <form method="GET" action="{{ route('admin.verifications.index') }}" style="display:flex; gap:12px; width:100%; flex-wrap:wrap;">
            @if(auth()->user()->isAdmin() || auth()->user()->isPimpinan() || auth()->user()->isVerifier())
            <div style="min-width:180px;">
                <select name="unit_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Unit --</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->code }} - {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="min-width:200px;">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="waiting_verification" {{ request('status') == 'waiting_verification' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="revision" {{ request('status') == 'revision' ? 'selected' : '' }}>Perlu Revisi</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai / Completed</option>
                </select>
            </div>

            <div style="flex:1; min-width:200px;">
                <input type="text" name="search" class="form-control" placeholder="Cari kode / nama kegiatan..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-responsive" style="margin-top:16px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kegiatan</th>
                    <th>Unit & PPTK</th>
                    <th>Pagu & Realisasi Verified</th>
                    <th>Putaran</th>
                    <th>Kelengkapan Valid</th>
                    <th>Status Pengajuan</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                @php $comp = $act->document_completeness; @endphp
                <tr>
                    <td><code>{{ $act->activity_code }}</code></td>
                    <td>
                        <a href="{{ route('admin.verifications.show', $act->id) }}" style="font-weight:700; color:var(--accent-color); text-decoration:none;">
                            {{ $act->activity_name }}
                        </a>
                    </td>
                    <td>
                        <strong>{{ $act->unit ? $act->unit->code : '-' }}</strong><br>
                        <small style="color:var(--text-muted);">{{ $act->personInCharge ? $act->personInCharge->name : '-' }}</small>
                    </td>
                    <td>
                        <strong>Rp {{ number_format($act->budget_ceiling, 0, ',', '.') }}</strong><br>
                        <small style="color:var(--success);">Verified: Rp {{ number_format($act->verified_realization_total, 0, ',', '.') }}</small>
                    </td>
                    <td><span class="badge badge-info">Putaran {{ $act->verification_round }}</span></td>
                    <td>
                        <div style="display:flex; justify-content:space-between; font-size:0.75rem;">
                            <span>{{ $comp['valid_required'] }}/{{ $comp['total_required'] }} Valid</span>
                            <strong>{{ $comp['valid_percentage'] }}%</strong>
                        </div>
                        <div style="background-color:#e2e8f0; height:6px; border-radius:3px; overflow:hidden; margin-top:2px;">
                            <div style="background-color:{{ $comp['valid_percentage'] == 100 ? 'var(--success)' : 'var(--warning)' }}; width:{{ $comp['valid_percentage'] }}%; height:100%;"></div>
                        </div>
                    </td>
                    <td>
                        @switch($act->status)
                            @case('waiting_verification') <span class="badge badge-warning">Menunggu Verifikasi</span> @break
                            @case('revision') <span class="badge badge-secondary" style="background:#f97316;color:white;">Perlu Revisi</span> @break
                            @case('completed') <span class="badge badge-success">Disetujui & Selesai</span> @break
                        @endswitch
                    </td>
                    <td style="text-align:center;">
                        <a href="{{ route('admin.verifications.show', $act->id) }}" class="btn btn-primary btn-sm">
                            &eye; Periksa
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; color: var(--text-muted); padding:32px;">Tidak ada kegiatan dalam antrean verifikasi.</td>
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
