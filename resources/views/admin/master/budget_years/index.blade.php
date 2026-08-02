@extends('admin.layouts.app')

@section('title', 'Kelola Tahun Anggaran')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tahun Anggaran</h1>
        <div class="page-subtitle">Pengaturan siklus tahun anggaran aktif & penutupan periode</div>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modalAdd').style.display='block'">
        + Tambah Tahun Anggaran
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tahun</th>
                    <th>Nama Keterangan</th>
                    <th>Periode Mulai</th>
                    <th>Periode Selesai</th>
                    <th>Status Aktif</th>
                    <th>Status Kunci</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($budgetYears as $by)
                <tr>
                    <td><strong>{{ $by->year }}</strong></td>
                    <td>{{ $by->name }}</td>
                    <td>{{ $by->start_date->format('d/m/Y') }}</td>
                    <td>{{ $by->end_date->format('d/m/Y') }}</td>
                    <td>
                        @if($by->is_active)
                            <span class="badge badge-success">Sistem Aktif</span>
                        @else
                            <span class="badge badge-secondary">Non-Aktif</span>
                        @endif
                    </td>
                    <td>
                        @if($by->is_closed)
                            <span class="badge badge-danger">Ditutup</span>
                        @else
                            <span class="badge badge-info">Terbuka</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            @if(!$by->is_active)
                            <form action="{{ route('master.budget-years.toggle-active', $by->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm">Set Aktif</button>
                            </form>
                            @endif

                            <form action="{{ route('master.budget-years.toggle-closed', $by->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    {{ $by->is_closed ? 'Buka Kunci' : 'Tutup Periode' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; color: var(--text-muted);">Belum ada data tahun anggaran.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $budgetYears->links() }}
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalAdd" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Tambah Tahun Anggaran</h3>
            <button onclick="document.getElementById('modalAdd').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('master.budget-years.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Tahun (Angka)</label>
                <input type="number" name="year" class="form-control" placeholder="contoh: 2026" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Keterangan</label>
                <input type="text" name="name" class="form-control" placeholder="contoh: Tahun Anggaran 2026" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control" required value="{{ date('Y-01-01') }}">
            </div>
            <div class="form-group">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" name="end_date" class="form-control" required value="{{ date('Y-12-31') }}">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAdd').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
