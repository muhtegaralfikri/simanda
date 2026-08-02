@extends('admin.layouts.app')

@section('title', 'Kelola Jenis Belanja')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Jenis Belanja</h1>
        <div class="page-subtitle">Kelola kode dan kelompok jenis belanja/pengeluaran</div>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modalAdd').style.display='block'">
        + Tambah Jenis Belanja
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode Rekening</th>
                    <th>Uraian Belanja</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenseTypes as $et)
                <tr>
                    <td><code>{{ $et->code }}</code></td>
                    <td><strong>{{ $et->name }}</strong></td>
                    <td><span class="badge badge-info">{{ $et->category ?? 'Operasional' }}</span></td>
                    <td>
                        @if($et->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-secondary">Non-Aktif</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('master.expense-types.toggle-active', $et->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">
                                {{ $et->is_active ? 'Non-Aktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; color: var(--text-muted);">Belum ada data jenis belanja.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $expenseTypes->links() }}
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalAdd" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Tambah Jenis Belanja</h3>
            <button onclick="document.getElementById('modalAdd').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('master.expense-types.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Kode Akun / Rekening</label>
                <input type="text" name="code" class="form-control" placeholder="contoh: 5.1.02.01" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Uraian Belanja</label>
                <input type="text" name="name" class="form-control" placeholder="contoh: Belanja Bahan ATK" required>
            </div>
            <div class="form-group">
                <label class="form-label">Kategori Belanja</label>
                <input type="text" name="category" class="form-control" placeholder="contoh: Operasional / Modal / Personel">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAdd').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
