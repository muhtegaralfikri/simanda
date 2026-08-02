@extends('admin.layouts.app')

@section('title', 'Kelola Sumber Dana')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Sumber Dana</h1>
        <div class="page-subtitle">Kelola kategori asal sumber pendanaan anggaran (APBD, APBN, BLUD, Hibah)</div>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modalAdd').style.display='block'">
        + Tambah Sumber Dana
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Sumber Dana</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fundingSources as $fs)
                <tr>
                    <td><code>{{ $fs->code }}</code></td>
                    <td><strong>{{ $fs->name }}</strong></td>
                    <td>{{ $fs->description ?? '-' }}</td>
                    <td>
                        @if($fs->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-secondary">Non-Aktif</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('master.funding-sources.toggle-active', $fs->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">
                                {{ $fs->is_active ? 'Non-Aktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center; color: var(--text-muted);">Belum ada data sumber dana.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $fundingSources->links() }}
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalAdd" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Tambah Sumber Dana</h3>
            <button onclick="document.getElementById('modalAdd').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('master.funding-sources.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Kode (Singkatan)</label>
                <input type="text" name="code" class="form-control" placeholder="contoh: APBD / APBN" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Sumber Dana</label>
                <input type="text" name="name" class="form-control" placeholder="contoh: Anggaran Pendapatan dan Belanja Daerah" required>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAdd').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
