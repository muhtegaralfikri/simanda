@extends('admin.layouts.app')

@section('title', 'Daftar Realisasi Anggaran')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Realisasi Anggaran</h1>
        <div class="page-subtitle">Daftar transaksi pengeluaran dan serapan dana per kegiatan ({{ $activeYear ? $activeYear->year : '2026' }})</div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="border-bottom:none; margin-bottom:0; padding-bottom:0;">
        <form method="GET" action="{{ route('realizations.index') }}" style="display:flex; gap:12px; width:100%; flex-wrap:wrap;">
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

            <div style="min-width:160px;">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="submitted" {{ request('status') == 'submitted' ? 'selected' : '' }}>Diajukan</option>
                </select>
            </div>

            <div style="flex:1; min-width:200px;">
                <input type="text" name="search" class="form-control" placeholder="Cari no kuitansi / penerima / deskripsi..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
    </div>

    <div class="table-responsive" style="margin-top:16px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Tgl Transaksi</th>
                    <th>No Bukti</th>
                    <th>Kegiatan & Unit</th>
                    <th>Uraian RAB</th>
                    <th>Penerima</th>
                    <th style="text-align:right;">Bruto (Rp)</th>
                    <th style="text-align:right;">Bersih (Rp)</th>
                    <th>Status</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($realizations as $rel)
                <tr>
                    <td>{{ $rel->transaction_date->format('d/m/Y') }}</td>
                    <td><code>{{ $rel->receipt_number }}</code></td>
                    <td>
                        <strong>{{ $rel->activity ? $rel->activity->activity_name : '-' }}</strong><br>
                        <small style="color:var(--text-muted);">{{ $rel->activity && $rel->activity->unit ? $rel->activity->unit->code : '-' }}</small>
                    </td>
                    <td>{{ $rel->budgetPlan ? $rel->budgetPlan->description : '-' }}</td>
                    <td>{{ $rel->recipient_name ?? '-' }}</td>
                    <td style="text-align:right;"><strong>{{ number_format($rel->gross_amount, 0, ',', '.') }}</strong></td>
                    <td style="text-align:right; color:var(--success);">{{ number_format($rel->net_amount, 0, ',', '.') }}</td>
                    <td>
                        @switch($rel->status)
                            @case('draft') <span class="badge badge-secondary">Draft</span> @break
                            @case('submitted') <span class="badge badge-info">Diajukan</span> @break
                        @endswitch
                    </td>
                    <td>
                        <a href="{{ route('activities.show', $rel->activity_id) }}" class="btn btn-secondary btn-sm">Lihat Kegiatan</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; color: var(--text-muted); padding:32px;">Belum ada catatan realisasi transaksi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $realizations->links() }}
    </div>
</div>
@endsection
