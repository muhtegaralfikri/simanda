@extends('admin.layouts.app')

@section('title', 'Laporan Realisasi Anggaran')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Realisasi Transaksi Pengeluaran</h1>
        <div class="page-subtitle">Daftar transaksi kuitansi pengeluaran, penerima dana, dan status verifikasi</div>
    </div>
    <div style="display:flex; gap:8px;">
        <button onclick="window.print()" class="btn btn-secondary">&printer; Cetak Browser</button>
        <a href="{{ route('admin.reports.realization.pdf', request()->query()) }}" target="_blank" class="btn btn-primary">PDF</a>
        <a href="{{ route('admin.reports.realization.excel', request()->query()) }}" class="btn btn-success" style="background:var(--success); color:white;">Excel</a>
    </div>
</div>

<div class="card">
    <div class="card-header" style="border-bottom:none;">
        <form method="GET" action="{{ route('admin.reports.realization.index') }}" style="display:flex; gap:12px; width:100%; flex-wrap:wrap;">
            <div style="min-width:140px;">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Diajukan</option>
                    <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                    <option value="revision" {{ request('status') == 'revision' ? 'selected' : '' }}>Revision</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filter Data</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tgl Transaksi</th>
                    <th>No Bukti</th>
                    <th>Kegiatan & Unit</th>
                    <th>Uraian RAB</th>
                    <th>Penerima</th>
                    <th style="text-align:right;">Bruto (Rp)</th>
                    <th style="text-align:right;">Pajak (Rp)</th>
                    <th style="text-align:right;">Bersih (Rp)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($realizations as $rel)
                <tr>
                    <td>{{ $rel->transaction_date ? $rel->transaction_date->format('d/m/Y') : '-' }}</td>
                    <td><code>{{ $rel->receipt_number }}</code></td>
                    <td><strong>{{ $rel->activity ? $rel->activity->activity_name : '-' }}</strong></td>
                    <td>{{ $rel->budgetPlan ? $rel->budgetPlan->description : '-' }}</td>
                    <td>{{ $rel->recipient_name ?? '-' }}</td>
                    <td style="text-align:right;"><strong>{{ number_format($rel->gross_amount, 0, ',', '.') }}</strong></td>
                    <td style="text-align:right;">{{ number_format($rel->tax_amount, 0, ',', '.') }}</td>
                    <td style="text-align:right; color:var(--success);">{{ number_format($rel->net_amount, 0, ',', '.') }}</td>
                    <td><span class="badge badge-info">{{ $rel->status }}</span></td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; color:var(--text-muted); padding:32px;">Tidak ada data realisasi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($realizations, 'links'))
    <div style="margin-top:16px;">
        {{ $realizations->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
