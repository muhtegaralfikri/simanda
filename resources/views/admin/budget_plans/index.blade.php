@extends('admin.layouts.app')

@section('title', 'Rencana Anggaran Biaya (RAB)')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Rencana Anggaran Biaya (RAB) Kegiatan</h1>
        <div class="page-subtitle">Monitoring penyusunan rincian belanja dan kesesuaian pagu anggaran kegiatan</div>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('budget-plans.index') }}" style="display:flex; gap:12px; flex-wrap:wrap; width:100%; align-items:center;">
        @if(auth()->user()->isAdmin() || auth()->user()->isPimpinan() || auth()->user()->isVerifier())
        <div class="form-group" style="min-width:180px;">
            <select name="unit_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Unit Kerja --</option>
                @foreach($units as $u)
                    <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->code }} - {{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="form-group" style="flex:1; min-width:200px;">
            <input type="text" name="search" class="form-control" placeholder="Cari Kode atau Nama Kegiatan..." value="{{ request('search') }}">
        </div>

        <button type="submit" class="btn btn-secondary">Cari</button>
        <a href="{{ route('budget-plans.index') }}" class="btn btn-secondary" style="color:var(--text-muted);">Reset</a>
    </form>
</div>

<!-- Table Activity Budget Plans -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Alokasi RAB Kegiatan (TA {{ $activeYear ? $activeYear->year : date('Y') }})</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode & Kegiatan</th>
                    <th>Unit Kerja</th>
                    <th style="text-align:right;">Pagu Anggaran</th>
                    <th style="text-align:right;">Total RAB Disusun</th>
                    <th style="text-align:right;">Sisa Pagu</th>
                    <th>Status RAB</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                    @php
                        $totalPlan = $act->total_budget_plan;
                        $remaining = $act->remaining_ceiling;
                        $percentage = $act->rab_percentage;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $act->activity_code }}</strong><br>
                            <span style="font-size:0.85rem; color:var(--text-main);">{{ $act->activity_name }}</span>
                        </td>
                        <td><span class="badge badge-secondary">{{ $act->unit->code }}</span></td>
                        <td style="text-align:right; font-weight:600;">Rp {{ number_format($act->budget_ceiling, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color:var(--accent-color);">Rp {{ number_format($totalPlan, 0, ',', '.') }}</td>
                        <td style="text-align:right; font-weight:600; color: {{ $remaining === 0 ? 'var(--success)' : 'var(--warning)' }};">
                            Rp {{ number_format($remaining, 0, ',', '.') }}
                        </td>
                        <td>
                            @if($totalPlan === $act->budget_ceiling && $act->budget_ceiling > 0)
                                <span class="badge badge-success">✓ 100% Sesuai Pagu</span>
                            @elseif($totalPlan > $act->budget_ceiling)
                                <span class="badge badge-danger">! Melebihi Pagu</span>
                            @else
                                <span class="badge badge-warning">Belum Sesuai ({{ $percentage }}%)</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('activities.show', $act->id) }}#rab" class="btn btn-secondary btn-sm">
                                Kelola RAB &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--text-muted); padding:32px;">
                            Tidak ada data kegiatan perencanaan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding:16px;">
        {{ $activities->withQueryString()->links() }}
    </div>
</div>
@endsection
