@extends('admin.layouts.app')

@section('title', 'Laporan Progres Kegiatan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Progres & Capaian Fisik</h1>
        <div class="page-subtitle">Persentase progres fisik dan catatan capaian kegiatan</div>
    </div>
    <div style="display:flex; gap:8px;">
        <button onclick="window.print()" class="btn btn-secondary">Cetak Browser</button>
        <a href="{{ route('admin.reports.progress.pdf', request()->query()) }}" target="_blank" class="btn btn-primary">PDF</a>
        <a href="{{ route('admin.reports.progress.excel', request()->query()) }}" class="btn btn-success" style="background:var(--success); color:white;">Excel</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kegiatan</th>
                    <th>Unit & PPTK</th>
                    <th style="width:200px;">Progres Fisik (%)</th>
                    <th>Catatan Progres Terakhir</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                <tr>
                    <td><code>{{ $act->activity_code }}</code></td>
                    <td><strong>{{ $act->activity_name }}</strong></td>
                    <td>{{ $act->unit ? $act->unit->code : '-' }} &bull; {{ $act->personInCharge ? $act->personInCharge->name : '-' }}</td>
                    <td>
                        <div style="display:flex; justify-content:space-between; font-size:0.75rem;">
                            <span>Capaian</span>
                            <strong>{{ $act->progress_percentage }}%</strong>
                        </div>
                        <div style="background-color:#e2e8f0; height:6px; border-radius:3px; overflow:hidden;">
                            <div style="background-color:#8b5cf6; width:{{ $act->progress_percentage }}%; height:100%;"></div>
                        </div>
                    </td>
                    <td><small>{{ $act->progress_note ?? '-' }}</small></td>
                    <td><span class="badge badge-info">{{ $act->status }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:var(--text-muted); padding:32px;">Tidak ada data progres kegiatan.</td>
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
