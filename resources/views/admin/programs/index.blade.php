@extends('admin.layouts.app')

@section('title', 'Program Kegiatan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Program Kegiatan</h1>
        <div class="page-subtitle">Kelola program kerja tahunan per unit kerja ({{ $activeYear ? $activeYear->year : '2026' }})</div>
    </div>
    @can('create', App\Models\Program::class)
    <button class="btn btn-primary" onclick="document.getElementById('modalAdd').style.display='block'">
        + Tambah Program
    </button>
    @endcan
</div>

<div class="card">
    <div class="card-header" style="border-bottom:none; margin-bottom:0; padding-bottom:0;">
        <form method="GET" action="{{ route('programs.index') }}" style="display:flex; gap:12px; width:100%; flex-wrap:wrap;">
            @if(auth()->user()->isAdmin() || auth()->user()->isPimpinan())
            <div style="min-width:200px;">
                <select name="unit_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Unit Kerja --</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->code }} - {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div style="flex:1; min-width:200px;">
                <input type="text" name="search" class="form-control" placeholder="Cari kode atau nama program..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn btn-secondary">Cari</button>
            @if(request()->hasAny(['unit_id', 'search']))
                <a href="{{ route('programs.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="table-responsive" style="margin-top:16px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode Program</th>
                    <th>Nama Program</th>
                    <th>Unit Kerja</th>
                    <th>Jumlah Kegiatan</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $p)
                <tr>
                    <td><code>{{ $p->program_code }}</code></td>
                    <td><strong>{{ $p->program_name }}</strong></td>
                    <td>{{ $p->unit ? $p->unit->name : '-' }}</td>
                    <td><span class="badge badge-info">{{ $p->activities_count }} Kegiatan</span></td>
                    <td>
                        @if($p->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-secondary">Non-Aktif</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            @can('update', $p)
                            <button class="btn btn-secondary btn-sm" onclick="editProgram({{ json_encode($p) }})">Edit</button>
                            @endcan
                            @can('delete', $p)
                            <form action="{{ route('programs.destroy', $p->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus program ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color: var(--text-muted);">Belum ada data program kegiatan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $programs->links() }}
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalAdd" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:520px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Tambah Program Baru</h3>
            <button onclick="document.getElementById('modalAdd').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('programs.store') }}" method="POST">
            @csrf
            @if(auth()->user()->isAdmin())
            <div class="form-group">
                <label class="form-label">Unit Kerja</label>
                <select name="unit_id" class="form-select" required>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}">{{ $u->code }} - {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="unit_id" value="{{ auth()->user()->unit_id }}">
            <div class="form-group">
                <label class="form-label">Unit Kerja</label>
                <input type="text" class="form-control" value="{{ auth()->user()->unit ? auth()->user()->unit->name : '' }}" disabled>
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">Kode Program</label>
                <input type="text" name="program_code" class="form-control" placeholder="contoh: PRG.01" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Program</label>
                <input type="text" name="program_name" class="form-control" placeholder="contoh: Program Peningkatan Pelayanan Publik" required>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi Opsional</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAdd').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Program</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="modalEdit" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:520px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Edit Program</h3>
            <button onclick="document.getElementById('modalEdit').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form id="formEdit" method="POST">
            @csrf
            @method('PUT')
            @if(auth()->user()->isAdmin())
            <div class="form-group">
                <label class="form-label">Unit Kerja</label>
                <select name="unit_id" id="edit_unit_id" class="form-select" required>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}">{{ $u->code }} - {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="unit_id" value="{{ auth()->user()->unit_id }}">
            @endif

            <div class="form-group">
                <label class="form-label">Kode Program</label>
                <input type="text" name="program_code" id="edit_program_code" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Program</label>
                <input type="text" name="program_name" id="edit_program_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Status Program</label>
                <select name="is_active" id="edit_is_active" class="form-select">
                    <option value="1">Aktif</option>
                    <option value="0">Non-Aktif</option>
                </select>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalEdit').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editProgram(p) {
        document.getElementById('formEdit').action = '/programs/' + p.id;
        document.getElementById('edit_program_code').value = p.program_code;
        document.getElementById('edit_program_name').value = p.program_name;
        document.getElementById('edit_description').value = p.description || '';
        document.getElementById('edit_is_active').value = p.is_active ? '1' : '0';
        if (document.getElementById('edit_unit_id')) {
            document.getElementById('edit_unit_id').value = p.unit_id;
        }
        document.getElementById('modalEdit').style.display = 'block';
    }
</script>
@endsection
