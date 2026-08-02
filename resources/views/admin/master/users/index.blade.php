@extends('admin.layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pengguna Sistem</h1>
        <div class="page-subtitle">Manajemen pengguna, peran akses (Admin, Pimpinan, PPTK, Verifikator) & penugasan unit kerja</div>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modalAdd').style.display='block'">
        Tambah Pengguna
    </button>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="{{ route('master.users.index') }}" style="display:flex; gap:12px; flex-wrap:wrap; width:100%; align-items:center;">
        <div class="form-group" style="flex:1; min-width:200px;">
            <input type="text" name="search" class="form-control" placeholder="Cari Nama, Email, atau Telepon..." value="{{ request('search') }}">
        </div>

        <div class="form-group" style="min-width:160px;">
            <select name="role" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Peran (Role) --</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                <option value="pimpinan" {{ request('role') == 'pimpinan' ? 'selected' : '' }}>Pimpinan Executive</option>
                <option value="pptk" {{ request('role') == 'pptk' ? 'selected' : '' }}>Penanggung Jawab (PPTK)</option>
                <option value="verifier" {{ request('role') == 'verifier' ? 'selected' : '' }}>Verifikator / Keuangan</option>
            </select>
        </div>

        <div class="form-group" style="min-width:180px;">
            <select name="unit_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Unit Kerja --</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->code }} - {{ $unit->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group" style="min-width:140px;">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Status --</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Non-Aktif</option>
            </select>
        </div>

        <button type="submit" class="btn btn-secondary">Cari</button>
        <a href="{{ route('master.users.index') }}" class="btn btn-secondary" style="color:var(--text-muted);">Reset</a>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama User</th>
                    <th>Email / Telepon</th>
                    <th>Peran (Role)</th>
                    <th>Unit Kerja</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td><strong>{{ $u->name }}</strong></td>
                    <td>
                        {{ $u->email }}<br>
                        <small style="color:var(--text-muted);">{{ $u->phone ?? '-' }}</small>
                    </td>
                    <td>
                        @switch($u->role)
                            @case('admin') <span class="badge badge-info">Administrator</span> @break
                            @case('pimpinan') <span class="badge badge-success">Pimpinan</span> @break
                            @case('pptk') <span class="badge badge-warning">Penanggung Jawab</span> @break
                            @case('verifier') <span class="badge badge-secondary">Verifikator</span> @break
                        @endswitch
                    </td>
                    <td>{{ $u->unit ? $u->unit->name : 'Seluruh Unit' }}</td>
                    <td>
                        @if($u->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-secondary">Non-Aktif</span>
                        @endif
                    </td>
                    <td>{{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Belum Pernah' }}</td>
                    <td style="text-align:center;">
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary btn-sm" onclick='openEditModal(@json($u))'>
                                Edit
                            </button>

                            @if($u->id !== auth()->id())
                            <form action="{{ route('master.users.toggle-active', $u->id) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    {{ $u->is_active ? 'Non-Aktifkan' : 'Aktifkan' }}
                                </button>
                            </form>

                            <form action="{{ route('master.users.destroy', $u->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun {{ $u->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    Hapus
                                </button>
                            </form>
                            @else
                            <small style="color:var(--text-muted); padding:4px 8px;">(Akun Saya)</small>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; color: var(--text-muted); padding:32px;">Belum ada data pengguna yang sesuai.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $users->links() }}
    </div>
</div>

<!-- Modal Tambah Pengguna -->
<div id="modalAdd" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:550px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Tambah Pengguna Baru</h3>
            <button onclick="document.getElementById('modalAdd').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('master.users.store') }}" method="POST">
            @csrf
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Nama Lengkap & Gelar</label>
                <input type="text" name="name" class="form-control" placeholder="contoh: Budi Santoso, S.STP" required>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Alamat Email</label>
                <input type="email" name="email" class="form-control" placeholder="contoh: budi@simanda.go.id" required>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Nomor Telepon / WA</label>
                <input type="text" name="phone" class="form-control" placeholder="contoh: 08123456789">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Peran Akses (Role)</label>
                <select name="role" class="form-select" required>
                    <option value="pptk">Penanggung Jawab Kegiatan (PPTK)</option>
                    <option value="verifier">Verifikator / Keuangan</option>
                    <option value="pimpinan">Pimpinan Executive</option>
                    <option value="admin">Administrator Sistem</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Unit Kerja Terkait</label>
                <select name="unit_id" class="form-select">
                    <option value="">-- Seluruh Unit (Khusus Admin/Pimpinan) --</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->code }} - {{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Kata Sandi Awal</label>
                <input type="password" name="password" class="form-control" required placeholder="Minimal 8 karakter">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAdd').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Pengguna</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pengguna -->
<div id="modalEdit" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:550px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Edit Data Pengguna</h3>
            <button onclick="document.getElementById('modalEdit').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form id="formEditUser" method="POST">
            @csrf
            @method('PUT')
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Nama Lengkap & Gelar</label>
                <input type="text" id="edit_name" name="name" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Alamat Email</label>
                <input type="email" id="edit_email" name="email" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Nomor Telepon / WA</label>
                <input type="text" id="edit_phone" name="phone" class="form-control">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Peran Akses (Role)</label>
                <select id="edit_role" name="role" class="form-select" required>
                    <option value="pptk">Penanggung Jawab Kegiatan (PPTK)</option>
                    <option value="verifier">Verifikator / Keuangan</option>
                    <option value="pimpinan">Pimpinan Executive</option>
                    <option value="admin">Administrator Sistem</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label class="form-label">Unit Kerja Terkait</label>
                <select id="edit_unit_id" name="unit_id" class="form-select">
                    <option value="">-- Seluruh Unit (Khusus Admin/Pimpinan) --</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->code }} - {{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label">Ubah Kata Sandi (Opsional)</label>
                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalEdit').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Perbarui Pengguna</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openEditModal(user) {
    var form = document.getElementById('formEditUser');
    form.action = "{{ url('/master/users') }}/" + user.id;

    document.getElementById('edit_name').value = user.name || '';
    document.getElementById('edit_email').value = user.email || '';
    document.getElementById('edit_phone').value = user.phone || '';
    document.getElementById('edit_role').value = user.role || 'pptk';
    document.getElementById('edit_unit_id').value = user.unit_id || '';

    document.getElementById('modalEdit').style.display = 'block';
}
</script>
@endpush
@endsection
