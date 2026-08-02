@extends('admin.layouts.app')

@section('title', 'Laporan Ringkasan Anggaran')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Ringkasan Anggaran</h1>
        <div class="page-subtitle">Rekapitulasi pagu, alokasi RAB, realisasi aktif, realisasi terverifikasi, dan sisa anggaran</div>
    </div>
    <div style="display:flex; gap:8px;">
        <button onclick="window.print()" class="btn btn-secondary">Cetak Browser</button>
        <a href="{{ route('admin.reports.budget.pdf', request()->query()) }}" target="_blank" class="btn btn-primary">PDF</a>
        <a href="{{ route('admin.reports.budget.excel', request()->query()) }}" class="btn btn-success" style="background:var(--success); color:white;">Excel</a>
    </div>
</div>

<div class="card">
    <div class="card-header" style="border-bottom:none;">
        <form method="GET" action="{{ route('admin.reports.budget.index') }}" style="display:flex; gap:12px; width:100%; flex-wrap:wrap;">
            <div style="min-width:140px;">
                <select name="budget_year_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua TA --</option>
                    @foreach($budgetYears as $by)
                        <option value="{{ $by->id }}" {{ request('budget_year_id') == $by->id ? 'selected' : '' }}>TA {{ $by->year }}</option>
                    @endforeach
                </select>
            </div>

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

            <div style="min-width:160px;">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Sedang Berjalan</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">Filter Data</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kegiatan</th>
                    <th>Unit</th>
                    <th style="text-align:right;">Pagu (Rp)</th>
                    <th style="text-align:right;">Total RAB (Rp)</th>
                    <th style="text-align:right;">Realisasi Verified (Rp)</th>
                    <th style="text-align:right;">Sisa Final (Rp)</th>
                    <th style="text-align:center;">Serapan (%)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                <tr>
                    <td><code>{{ $act->activity_code }}</code></td>
                    <td><strong>{{ $act->activity_name }}</strong></td>
                    <td>{{ $act->unit ? $act->unit->code : '-' }}</td>
                    <td style="text-align:right;">{{ number_format($act->budget_ceiling, 0, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($act->total_budget_plan, 0, ',', '.') }}</td>
                    <td style="text-align:right; color:var(--success);"><strong>{{ number_format($act->verified_realization_total, 0, ',', '.') }}</strong></td>
                    <td style="text-align:right; color:var(--info);">{{ number_format($act->final_remaining_budget, 0, ',', '.') }}</td>
                    <td style="text-align:center;"><strong>{{ $act->realization_percentage }}%</strong></td>
                    <td><span class="badge badge-info">{{ $act->status }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; color:var(--text-muted); padding:32px;">Tidak ada data laporan ringkasan anggaran.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($activities, 'links'))
    <div style="margin-top:16px;">
        {{ $activities->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
