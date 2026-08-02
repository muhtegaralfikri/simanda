@extends('admin.layouts.app')

@section('title', 'Laporan Kelengkapan Dokumen')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Kelengkapan Dokumen Kegiatan</h1>
        <div class="page-subtitle">Rekap kelengkapan dokumen wajib (Terunggah vs Validasi Sah)</div>
    </div>
    <div style="display:flex; gap:8px;">
        <button onclick="window.print()" class="btn btn-secondary">Cetak Browser</button>
        <a href="{{ route('admin.reports.documents.pdf', request()->query()) }}" target="_blank" class="btn btn-primary">PDF</a>
        <a href="{{ route('admin.reports.documents.excel', request()->query()) }}" class="btn btn-success" style="background:var(--success); color:white;">Excel</a>
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
                    <th>Dokumen Wajib Terunggah</th>
                    <th>Dokumen Wajib Valid</th>
                    <th style="width:200px;">Persentase Valid (%)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                @php $comp = $act->document_completeness; @endphp
                <tr>
                    <td><code>{{ $act->activity_code }}</code></td>
                    <td><strong>{{ $act->activity_name }}</strong></td>
                    <td>{{ $act->unit ? $act->unit->code : '-' }} &bull; {{ $act->personInCharge ? $act->personInCharge->name : '-' }}</td>
                    <td>{{ $comp['fulfilled_required'] }}/{{ $comp['total_required'] }} ({{ $comp['percentage'] }}%)</td>
                    <td><strong style="color:var(--success);">{{ $comp['valid_required'] }}/{{ $comp['total_required'] }}</strong></td>
                    <td>
                        <div style="display:flex; justify-content:space-between; font-size:0.75rem;">
                            <span>Valid</span>
                            <strong>{{ $comp['valid_percentage'] }}%</strong>
                        </div>
                        <div style="background-color:#e2e8f0; height:6px; border-radius:3px; overflow:hidden;">
                            <div style="background-color:var(--success); width:{{ $comp['valid_percentage'] }}%; height:100%;"></div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:var(--text-muted); padding:32px;">Tidak ada data dokumen kegiatan.</td>
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
