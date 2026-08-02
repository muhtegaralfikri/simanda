@extends('admin.layouts.app')

@section('title', 'Laporan Pelaksanaan Kegiatan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Pelaksanaan Kegiatan</h1>
        <div class="page-subtitle">Daftar kegiatan, jadwal, unit kerja, PPTK, dan progres capaian</div>
    </div>
    <div style="display:flex; gap:8px;">
        <button onclick="window.print()" class="btn btn-secondary">&printer; Cetak Browser</button>
        <a href="{{ route('admin.reports.activity.pdf', request()->query()) }}" target="_blank" class="btn btn-primary">PDF</a>
        <a href="{{ route('admin.reports.activity.excel', request()->query()) }}" class="btn btn-success" style="background:var(--success); color:white;">Excel</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kegiatan</th>
                    <th>Program & Unit</th>
                    <th>PPTK</th>
                    <th>Tgl Pelaksanaan</th>
                    <th style="text-align:right;">Pagu (Rp)</th>
                    <th style="text-align:right;">Realisasi Verified (Rp)</th>
                    <th style="text-align:center;">Progres (%)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                <tr>
                    <td><code>{{ $act->activity_code }}</code></td>
                    <td><strong>{{ $act->activity_name }}</strong></td>
                    <td>{{ $act->unit ? $act->unit->code : '-' }}</td>
                    <td>{{ $act->personInCharge ? $act->personInCharge->name : '-' }}</td>
                    <td>{{ $act->start_date ? $act->start_date->format('d/m/Y') : '-' }} s/d {{ $act->end_date ? $act->end_date->format('d/m/Y') : '-' }}</td>
                    <td style="text-align:right;">{{ number_format($act->budget_ceiling, 0, ',', '.') }}</td>
                    <td style="text-align:right; color:var(--success);">{{ number_format($act->verified_realization_total, 0, ',', '.') }}</td>
                    <td style="text-align:center;"><strong>{{ $act->progress_percentage }}%</strong></td>
                    <td><span class="badge badge-info">{{ $act->status }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; color:var(--text-muted); padding:32px;">Tidak ada data kegiatan.</td>
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
