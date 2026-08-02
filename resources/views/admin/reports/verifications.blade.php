@extends('admin.layouts.app')

@section('title', 'Laporan Riwayat Verifikasi')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Riwayat Verifikasi</h1>
        <div class="page-subtitle">Log audit keputusan verifikator terhadap kegiatan, realisasi, dan dokumen</div>
    </div>
    <div style="display:flex; gap:8px;">
        <button onclick="window.print()" class="btn btn-secondary">Cetak Browser</button>
        <a href="{{ route('admin.reports.verifications.pdf', request()->query()) }}" target="_blank" class="btn btn-primary">PDF</a>
        <a href="{{ route('admin.reports.verifications.excel', request()->query()) }}" class="btn btn-success" style="background:var(--success); color:white;">Excel</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Putaran</th>
                    <th>Tipe Objek</th>
                    <th>Keputusan</th>
                    <th>Verifier</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($verifications as $v)
                <tr>
                    <td>{{ $v->verified_at ? $v->verified_at->format('d/m/Y H:i:s') : '-' }}</td>
                    <td><span class="badge badge-info">Putaran {{ $v->round }}</span></td>
                    <td>{{ class_basename($v->verifiable_type) }}</td>
                    <td>
                        @switch($v->decision)
                            @case('approved') <span class="badge badge-success">Disetujui / Valid</span> @break
                            @case('revision') <span class="badge badge-secondary" style="background:#f97316;color:white;">Perlu Revisi</span> @break
                            @case('rejected') <span class="badge badge-danger">Ditolak</span> @break
                        @endswitch
                    </td>
                    <td>{{ $v->verifier ? $v->verifier->name : '-' }}</td>
                    <td>{{ $v->notes ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color:var(--text-muted); padding:32px;">Tidak ada riwayat verifikasi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($verifications, 'links'))
    <div style="margin-top:16px;">
        {{ $verifications->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
