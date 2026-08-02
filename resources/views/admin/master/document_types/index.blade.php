@extends('admin.layouts.app')

@section('title', 'Kelola Jenis Dokumen')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Jenis Dokumen Kegiatan</h1>
        <div class="page-subtitle">Master kelengkapan dokumen per tahapan (Perencanaan, Pelaksanaan, Keuangan)</div>
    </div>
    <button class="btn btn-primary" onclick="document.getElementById('modalAdd').style.display='block'">
        + Tambah Jenis Dokumen
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Dokumen</th>
                    <th>Tahapan</th>
                    <th>Wajib?</th>
                    <th>Format Izin</th>
                    <th>Max Size</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documentTypes as $dt)
                <tr>
                    <td><code>{{ $dt->code }}</code></td>
                    <td><strong>{{ $dt->name }}</strong></td>
                    <td>
                        @switch($dt->stage)
                            @case('planning') <span class="badge badge-info">Perencanaan</span> @break
                            @case('execution') <span class="badge badge-warning">Pelaksanaan</span> @break
                            @case('financial') <span class="badge badge-success">Keuangan</span> @break
                        @endswitch
                    </td>
                    <td>
                        @if($dt->is_required)
                            <span class="badge badge-danger">Wajib</span>
                        @else
                            <span class="badge badge-secondary">Opsional</span>
                        @endif
                    </td>
                    <td><code>{{ $dt->allowed_extensions }}</code></td>
                    <td>{{ round($dt->maximum_size / 1024, 1) }} MB</td>
                    <td>
                        @if($dt->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-secondary">Non-Aktif</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('master.document-types.toggle-active', $dt->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-sm">
                                {{ $dt->is_active ? 'Non-Aktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; color: var(--text-muted);">Belum ada data jenis dokumen.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $documentTypes->links() }}
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalAdd" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:550px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Tambah Jenis Dokumen</h3>
            <button onclick="document.getElementById('modalAdd').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('master.document-types.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Kode Singkatan Dokumen</label>
                <input type="text" name="code" class="form-control" placeholder="contoh: TOR / PRESENSI / KUITANSI" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nama Dokumen Laporan</label>
                <input type="text" name="name" class="form-control" placeholder="contoh: Kuitansi & Bukti Pembayaran" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tahapan Kegiatan</label>
                <select name="stage" class="form-select" required>
                    <option value="planning">Perencanaan (TOR, RAB, Surat Tugas)</option>
                    <option value="execution">Pelaksanaan (Presensi, Notulen, Dokumentasi)</option>
                    <option value="financial">Keuangan (Kuitansi, Invoice, Pajak)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tingkat Kewajiban Dokumen</label>
                <select name="is_required" class="form-select">
                    <option value="1">Wajib Diunggah (Harus Ada)</option>
                    <option value="0">Opsional / Pendukung</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Format Ekstensi yang Diizinkan (pisahkan koma)</label>
                <input type="text" name="allowed_extensions" class="form-control" value="pdf,jpg,png,doc,docx" required>
            </div>
            <div class="form-group">
                <label class="form-label">Ukuran Maksimal File (dalam KB)</label>
                <input type="number" name="maximum_size" class="form-control" value="5120" required>
                <small style="color:var(--text-muted);">5120 KB = 5 MB</small>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAdd').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
