@extends('admin.layouts.app')

@section('title', 'Kelola Unit Kerja')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Unit Kerja</h1>
        <div class="page-subtitle">Kelola struktur unit organisasi, dinas, atau departemen</div>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modalAdd').style.display='block'">
        + Tambah Unit Kerja
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode Unit</th>
                    <th>Nama Unit Kerja</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Pengguna</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                <tr>
                    <td><code>{{ $unit->code }}</code></td>
                    <td><strong>{{ $unit->name }}</strong></td>
                    <td>{{ $unit->description ?? '-' }}</td>
                    <td><span class="badge badge-info">{{ $unit->users_count }} Orang</span></td>
                    <td>
                        @if($unit->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-secondary">Non-Aktif</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('master.units.toggle-active', $unit->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">
                                {{ $unit->is_active ? 'Non-Aktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color: var(--text-muted);">Belum ada data unit kerja.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $units->links() }}
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalAdd" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Tambah Unit Kerja</h3>
            <button onclick="document.getElementById('modalAdd').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('master.units.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Kode Unit (Singkatan)</label>
                <input type="text" name="code" class="form-control" placeholder="contoh: DINKES / BAP" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Lengkap Unit Kerja</label>
                <input type="text" name="name" class="form-control" placeholder="contoh: Dinas Kesehatan dan Pelayanan" required>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi Tambahan</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi tugas atau uraian singkat"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAdd').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
