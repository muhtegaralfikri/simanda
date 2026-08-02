@extends('admin.layouts.app')

@section('title', 'Laporan Sisa Anggaran')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Sisa Anggaran & Efisiensi</h1>
        <div class="page-subtitle">Rincian sisa anggaran final dan catatan efisiensi kegiatan</div>
    </div>
    <div style="display:flex; gap:8px;">
        <button onclick="window.print()" class="btn btn-secondary">Cetak Browser</button>
        <a href="{{ route('admin.reports.remaining-budget.pdf', request()->query()) }}" target="_blank" class="btn btn-primary">PDF</a>
        <a href="{{ route('admin.reports.remaining-budget.excel', request()->query()) }}" class="btn btn-success" style="background:var(--success); color:white;">Excel</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kegiatan</th>
                    <th>Unit</th>
                    <th style="text-align:right;">Pagu (Rp)</th>
                    <th style="text-align:right;">Realisasi Verified (Rp)</th>
                    <th style="text-align:right;">Sisa Final (Rp)</th>
                    <th>Catatan Sisa Anggaran</th>
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
                    <td style="text-align:right; color:var(--success);">{{ number_format($act->verified_realization_total, 0, ',', '.') }}</td>
                    <td style="text-align:right; color:var(--info);"><strong>{{ number_format($act->final_remaining_budget, 0, ',', '.') }}</strong></td>
                    <td><small>{{ $act->remaining_budget_note ?? '-' }}</small></td>
                    <td><span class="badge badge-info">{{ $act->status }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; color:var(--text-muted); padding:32px;">Tidak ada data sisa anggaran.</td>
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
