@extends('admin.layouts.app')

@section('title', 'Detail & Pelaksanaan Kegiatan - ' . $activity->activity_code)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $activity->activity_name }}</h1>
        <div class="page-subtitle">
            Kode: <code>{{ $activity->activity_code }}</code> &bull; Unit: <strong>{{ $activity->unit ? $activity->unit->name : '-' }}</strong> &bull; PPTK: <strong>{{ $activity->personInCharge ? $activity->personInCharge->name : '-' }}</strong>
        </div>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="{{ route('activities.index') }}" class="btn btn-secondary">&larr; Kembali</a>

        @can('submitForVerification', $activity)
        <form action="{{ route('activities.submit-verification', $activity->id) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-success" style="background:var(--success); color:white; font-weight:bold;" onclick="return confirm('Ajukan kegiatan ini untuk verifikasi?');">
                Ajukan untuk Verifikasi
            </button>
        </form>
        @endcan

        <a href="{{ route('admin.verifications.show', $activity->id) }}" class="btn btn-primary">Modul Verifikasi</a>

        @can('update', $activity)
        <a href="{{ route('activities.edit', $activity->id) }}" class="btn btn-secondary">Edit Identitas</a>
        @endcan

        @can('startExecution', $activity)
        <form action="{{ route('activities.start', $activity->id) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-primary" onclick="return confirm('Mulai pelaksanaan kegiatan ini?');">
                Mulai Pelaksanaan
            </button>
        </form>
        @endcan

        @can('updateProgress', $activity)
        <button type="button" class="btn btn-primary" onclick="document.getElementById('modalProgress').style.display='block'">
            Perbarui Progres ({{ $activity->progress_percentage }}%)
        </button>
        @endcan

        @can('changeStatus', $activity)
            @if($activity->status === 'draft')
                <form action="{{ route('activities.plan', $activity->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary" {{ $activity->total_budget_plan !== $activity->budget_ceiling ? 'disabled title=Total_RAB_harus_sama_dengan_Pagu' : '' }}>
                        Tetapkan sebagai Direncanakan
                    </button>
                </form>
            @elseif($activity->status === 'planned')
                <form action="{{ route('activities.return-to-draft', $activity->id) }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary">
                        Kembalikan ke Draft
                    </button>
                </form>
            @endif

            @if(!in_array($activity->status, ['cancelled', 'completed']))
                <button type="button" class="btn btn-danger" onclick="document.getElementById('modalCancel').style.display='block'">
                    Batalkan Kegiatan
                </button>
            @endif
        @endcan
    </div>
</div>

@if($activity->status === 'completed')
<div class="alert alert-success" style="background:#ecfdf5; border-color:#10b981; color:#065f46;">
    <div>
        <strong>Kegiatan Disetujui & Selesai (COMPLETED):</strong> Seluruh dokumen dan realisasi telah diverifikasi. Seluruh data berstatus Read-Only.
        @if($activity->remaining_budget_note)<br><small><strong>Catatan Sisa Anggaran:</strong> {{ $activity->remaining_budget_note }}</small>@endif
    </div>
</div>
@elseif($activity->status === 'waiting_verification')
<div class="alert alert-info">
    <div>
        <strong>Menunggu Verifikasi:</strong> Kegiatan saat ini sedang dalam antrean pemeriksaan oleh tim Verifikator (Putaran {{ $activity->verification_round }}).
    </div>
</div>
@elseif($activity->status === 'revision')
<div class="alert alert-warning" style="background:#fff7ed; border-color:#f97316; color:#9a3412;">
    <div>
        <strong>Perlu Revisi:</strong> Verifikator meminta perbaikan pada realisasi/dokumen kegiatan ini. Silakan perbaiki item bernoda revisi lalu ajukan ulang.
    </div>
</div>
@elseif($activity->status === 'cancelled')
<div class="alert alert-danger">
    <div>
        <strong>Kegiatan Dibatalkan:</strong> {{ $activity->cancellation_reason ?? 'Tidak ada alasan dicatat' }}
    </div>
</div>
@endif

<!-- Indicators Grid -->
<div class="stats-grid">
    <div class="stat-card" style="border-left: 4px solid var(--accent-color);">
        <div class="stat-label">Pagu Anggaran Kegiatan</div>
        <div class="stat-value">Rp {{ number_format($activity->budget_ceiling, 0, ',', '.') }}</div>
        <div class="stat-subtext">Total RAB: <strong>Rp {{ number_format($activity->total_budget_plan, 0, ',', '.') }}</strong></div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--info);">
        <div class="stat-label">Realisasi Aktif Saat Ini</div>
        <div class="stat-value" style="color: var(--info);">Rp {{ number_format($activity->active_realization_total, 0, ',', '.') }}</div>
        <div class="stat-subtext">Verified: <strong>Rp {{ number_format($activity->verified_realization_total, 0, ',', '.') }}</strong></div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--success);">
        <div class="stat-label">Sisa Anggaran Tersedia</div>
        <div class="stat-value" style="color: var(--success);">Rp {{ number_format($activity->remaining_budget, 0, ',', '.') }}</div>
        <div class="stat-subtext">Sisa Final: Rp {{ number_format($activity->final_remaining_budget, 0, ',', '.') }}</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
        <div class="stat-label">Progres & Kelengkapan</div>
        <div class="stat-value" style="color: #8b5cf6;">{{ $activity->progress_percentage }}%</div>
        <div class="stat-subtext">
            Dokumen Valid: <strong>{{ $activity->document_completeness['valid_percentage'] }}% Terpenuhi</strong>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<div style="margin-bottom:20px; border-bottom:2px solid var(--border-color); display:flex; gap:16px; flex-wrap:wrap;">
    <button class="btn btn-secondary btn-tab active" id="tabBtn-rab" onclick="showTab('rab')">Rencana Anggaran (RAB)</button>
    <button class="btn btn-secondary btn-tab" id="tabBtn-realisasi" onclick="showTab('realisasi')">Realisasi Anggaran ({{ $activity->realizations->count() }})</button>
    <button class="btn btn-secondary btn-tab" id="tabBtn-dokumen" onclick="showTab('dokumen')">Dokumen & Checklist ({{ $activity->documents->where('is_current', true)->count() }})</button>
    <button class="btn btn-secondary btn-tab" id="tabBtn-progres" onclick="showTab('progres')">Histori Progres ({{ $activity->progressLogs->count() }})</button>
    <button class="btn btn-secondary btn-tab" id="tabBtn-verifikasi" onclick="showTab('verifikasi')">Riwayat Verifikasi ({{ $activity->verifications->count() }})</button>
</div>

<!-- TAB 1: RAB -->
<div id="tabContent-rab" class="tab-content">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Rencana Anggaran Biaya (RAB)</h2>
                <div style="font-size:0.8rem; color:var(--text-muted);">Uraian Rincian Belanja untuk Kegiatan {{ $activity->activity_name }}</div>
            </div>
            @can('manageBudgetPlan', $activity)
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalAddRab').style.display='block'">
                + Tambah Rincian RAB
            </button>
            @endcan
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Jenis Belanja</th>
                        <th>Kode Akun</th>
                        <th>Uraian Belanja</th>
                        <th style="text-align:center;">Vol</th>
                        <th>Satuan</th>
                        <th style="text-align:right;">Harga Satuan (Rp)</th>
                        <th style="text-align:right;">Alokasi RAB (Rp)</th>
                        <th style="text-align:right;">Realisasi (Rp)</th>
                        <th style="text-align:right;">Sisa RAB (Rp)</th>
                        @can('manageBudgetPlan', $activity)
                        <th style="text-align:center;">Aksi</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($activity->budgetPlans as $index => $bp)
                    @php
                        $realizedGross = $bp->realizations()->whereIn('status', ['draft', 'submitted', 'verified', 'revision'])->sum('gross_amount');
                        $sisaRab = max(0, $bp->total - $realizedGross);
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><span class="badge badge-info">{{ $bp->expenseType ? $bp->expenseType->name : '-' }}</span></td>
                        <td><code>{{ $bp->account_code }}</code></td>
                        <td>
                            <strong>{{ $bp->description }}</strong>
                            @if($bp->notes)<br><small style="color:var(--text-muted);">Ket: {{ $bp->notes }}</small>@endif
                        </td>
                        <td style="text-align:center;">{{ $bp->volume }}</td>
                        <td>{{ $bp->unit }}</td>
                        <td style="text-align:right;">{{ number_format($bp->unit_price, 0, ',', '.') }}</td>
                        <td style="text-align:right;"><strong>{{ number_format($bp->total, 0, ',', '.') }}</strong></td>
                        <td style="text-align:right; color:var(--info);">{{ number_format($realizedGross, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:var(--success);">{{ number_format($sisaRab, 0, ',', '.') }}</td>
                        @can('manageBudgetPlan', $activity)
                        <td style="text-align:center;">
                            <div style="display:flex; justify-content:center; gap:4px;">
                                <button class="btn btn-secondary btn-sm" onclick="editRab({{ json_encode($bp) }})">Edit</button>
                                <form action="{{ route('budget-plans.destroy', $bp->id) }}" method="POST" onsubmit="return confirm('Hapus item RAB ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                        @endcan
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" style="text-align:center; color: var(--text-muted); padding:32px;">Belum ada rincian RAB diinput.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 2: REALISASI -->
<div id="tabContent-realisasi" class="tab-content" style="display:none;">
    <div class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Realisasi Anggaran Pengeluaran</h2>
                <div style="font-size:0.8rem; color:var(--text-muted);">Catatan pengeluaran transaksi dan kuitansi pembayaran</div>
            </div>
            @can('create', [App\Models\Realization::class, $activity])
            <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalAddRealisasi').style.display='block'">
                + Catat Realisasi Baru
            </button>
            @endcan
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tgl Transaksi</th>
                        <th>No Bukti / Kuitansi</th>
                        <th>Rincian RAB & Jenis Belanja</th>
                        <th>Penerima / Penyedia</th>
                        <th style="text-align:right;">Bruto (Rp)</th>
                        <th style="text-align:right;">Pajak (Rp)</th>
                        <th style="text-align:right;">Bersih (Rp)</th>
                        <th>Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activity->realizations as $rel)
                    <tr>
                        <td>{{ $rel->transaction_date->format('d/m/Y') }}</td>
                        <td><code>{{ $rel->receipt_number }}</code></td>
                        <td>
                            <strong>{{ $rel->budgetPlan ? $rel->budgetPlan->description : '-' }}</strong><br>
                            <small style="color:var(--text-muted);">{{ $rel->expenseType ? $rel->expenseType->name : '-' }}</small>
                        </td>
                        <td>
                            {{ $rel->recipient_name ?? '-' }}
                            @if($rel->vendor_name)<br><small style="color:var(--text-muted);">Vendor: {{ $rel->vendor_name }}</small>@endif
                        </td>
                        <td style="text-align:right;"><strong>{{ number_format($rel->gross_amount, 0, ',', '.') }}</strong></td>
                        <td style="text-align:right;">{{ number_format($rel->tax_amount, 0, ',', '.') }}</td>
                        <td style="text-align:right; color:var(--success);"><strong>{{ number_format($rel->net_amount, 0, ',', '.') }}</strong></td>
                        <td>
                            @switch($rel->status)
                                @case('draft') <span class="badge badge-secondary">Draft</span> @break
                                @case('submitted') <span class="badge badge-info">Diajukan</span> @break
                                @case('verified') <span class="badge badge-success">Terverifikasi</span> @break
                                @case('revision') <span class="badge badge-secondary" style="background:#f97316;color:white;">Perlu Revisi</span> @break
                                @case('rejected') <span class="badge badge-danger">Ditolak</span> @break
                            @endswitch
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; justify-content:center; gap:4px;">
                                @if(in_array($rel->status, ['draft', 'revision']))
                                    @can('submit', $rel)
                                    <form action="{{ route('realizations.submit', $rel->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">Ajukan</button>
                                    </form>
                                    @endcan
                                    @if($rel->status === 'draft')
                                    @can('delete', $rel)
                                    <form action="{{ route('realizations.destroy', $rel->id) }}" method="POST" onsubmit="return confirm('Hapus realisasi ini?');" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                    @endcan
                                    @endif
                                @else
                                    <small style="color:var(--text-muted);">Catatan: {{ $rel->verification_note ?? '-' }}</small>
                                @endif
                            </div>
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
    </div>
</div>

<!-- TAB 3: DOKUMEN & CHECKLIST -->
<div id="tabContent-dokumen" class="tab-content" style="display:none;">
    <div style="display:grid; grid-template-columns:1fr 2fr; gap:20px;">
        <!-- Checklist Summary -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Checklist Dokumen Wajib</h3>
            </div>
            <div style="margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:4px;">
                    <strong>Kelengkapan Dokumen Valid:</strong>
                    <span>{{ $activity->document_completeness['valid_percentage'] }}%</span>
                </div>
                <div style="background-color:#e2e8f0; height:8px; border-radius:4px; overflow:hidden;">
                    <div style="background-color:var(--success); width:{{ $activity->document_completeness['valid_percentage'] }}%; height:100%;"></div>
                </div>
            </div>

            @php
                $allDocTypes = \App\Models\DocumentType::where('is_active', true)->orderBy('stage')->get();
                $currentDocs = $activity->documents->where('is_current', true)->keyBy('document_type_id');
            @endphp

            <div style="display:flex; flex-direction:column; gap:10px;">
                @foreach($allDocTypes as $dt)
                @php $doc = $currentDocs->get($dt->id); @endphp
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.82rem; padding:8px; background:#f8fafc; border-radius:6px; border:1px solid #e2e8f0;">
                    <div>
                        <strong>{{ $dt->name }}</strong>
                        @if($dt->is_required) <span style="color:red;">*</span> @endif
                        <br><small style="color:var(--text-muted);">Tahap: {{ strtoupper($dt->stage) }}</small>
                    </div>
                    <div>
                        @if($doc)
                            @switch($doc->status)
                                @case('uploaded') <span class="badge badge-secondary">Terunggah</span> @break
                                @case('submitted') <span class="badge badge-info">Diajukan</span> @break
                                @case('valid') <span class="badge badge-success">&check; Valid</span> @break
                                @case('revision') <span class="badge badge-secondary" style="background:#f97316;color:white;">Perlu Revisi</span> @break
                                @case('rejected') <span class="badge badge-danger">Ditolak</span> @break
                            @endswitch
                        @else
                            <span class="badge badge-secondary">&times; Belum Ada</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Document List & Upload Card -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Berkas Dokumen Terunggah</h3>
                    <div style="font-size:0.8rem; color:var(--text-muted);">Dokumen fisik disimpan secara privat & aman</div>
                </div>
                @can('uploadDocument', $activity)
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('modalUploadDoc').style.display='block'">
                    + Unggah Dokumen
                </button>
                @endcan
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jenis Dokumen</th>
                            <th>Nama Berkas Asli</th>
                            <th>Versi</th>
                            <th>Ukuran</th>
                            <th>Status</th>
                            <th style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activity->documents->where('is_current', true) as $doc)
                        <tr>
                            <td><strong>{{ $doc->documentType ? $doc->documentType->name : '-' }}</strong></td>
                            <td>{{ $doc->original_name }}</td>
                            <td><span class="badge badge-info">v{{ $doc->version }}</span></td>
                            <td>{{ round($doc->file_size / 1024, 1) }} KB</td>
                            <td>
                                @switch($doc->status)
                                    @case('uploaded') <span class="badge badge-secondary">Uploaded</span> @break
                                    @case('submitted') <span class="badge badge-info">Diajukan</span> @break
                                    @case('valid') <span class="badge badge-success">Valid</span> @break
                                    @case('revision') <span class="badge badge-secondary" style="background:#f97316;color:white;">Perlu Revisi</span> @break
                                    @case('rejected') <span class="badge badge-danger">Ditolak</span> @break
                                @endswitch
                            </td>
                            <td style="text-align:center;">
                                <div style="display:flex; justify-content:center; gap:4px;">
                                    @can('downloadDocument', $doc)
                                    <a href="{{ route('documents.preview', $doc->id) }}" target="_blank" class="btn btn-secondary btn-sm" title="Lihat Inline">Preview</a>
                                    <a href="{{ route('documents.download', $doc->id) }}" class="btn btn-secondary btn-sm" title="Unduh Berkas">Unduh</a>
                                    @endcan

                                    @can('deleteDocument', $doc)
                                    <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?');">
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
                            <td colspan="6" style="text-align:center; color: var(--text-muted); padding:32px;">Belum ada dokumen diunggah.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- TAB 4: HISTORI PROGRES -->
<div id="tabContent-progres" class="tab-content" style="display:none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Riwayat Pembaruan Progres Kegiatan</h3>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu Pembaruan</th>
                        <th>Persentase Progres</th>
                        <th>Pengupdate</th>
                        <th>Catatan / Uraian Progres</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activity->progressLogs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        <td><span class="badge badge-info">{{ $log->progress_percentage }}%</span></td>
                        <td><strong>{{ $log->updater ? $log->updater->name : '-' }}</strong></td>
                        <td>{{ $log->note ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align:center; color: var(--text-muted); padding:32px;">Belum ada riwayat progres tercatat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 5: RIWAYAT VERIFIKASI -->
<div id="tabContent-verifikasi" class="tab-content" style="display:none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Riwayat Keputusan Verifikasi</h3>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Putaran</th>
                        <th>Objek Verifikasi</th>
                        <th>Keputusan</th>
                        <th>Verifier</th>
                        <th>Catatan Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activity->verifications as $v)
                    <tr>
                        <td>{{ $v->created_at->format('d/m/Y H:i:s') }}</td>
                        <td><span class="badge badge-info">Putaran {{ $v->round }}</span></td>
                        <td>
                            @if($v->verifiable_type === 'App\Models\Activity')
                                <strong>Kegiatan Utama</strong>
                            @elseif($v->verifiable_type === 'App\Models\Realization')
                                <strong>Realisasi:</strong> {{ $v->verifiable ? $v->verifiable->receipt_number : '-' }}
                            @elseif($v->verifiable_type === 'App\Models\ActivityDocument')
                                <strong>Dokumen:</strong> {{ $v->verifiable ? $v->verifiable->original_name : '-' }}
                            @endif
                        </td>
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
                        <td colspan="6" style="text-align:center; color: var(--text-muted); padding:32px;">Belum ada riwayat keputusan verifikasi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modals (Progress, Realisasi, Document, RAB, Cancel) -->
<!-- Modal Update Progres -->
<div id="modalProgress" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Perbarui Progres Pelaksanaan</h3>
            <button onclick="document.getElementById('modalProgress').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('activities.progress.update', $activity->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Persentase Progres Saat Ini (0 - 100%)</label>
                <input type="number" name="progress_percentage" class="form-control" value="{{ $activity->progress_percentage }}" min="0" max="100" required>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan Keterangan Progres</label>
                <textarea name="note" class="form-control" rows="3" placeholder="Uraikan hasil fisik/capaian kegiatan saat ini..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalProgress').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Progres</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Catat Realisasi Baru -->
<div id="modalAddRealisasi" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:600px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Catat Realisasi Transaksi Pengeluaran</h3>
            <button onclick="document.getElementById('modalAddRealisasi').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('activities.realizations.store', $activity->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Alokasi Rincian RAB</label>
                <select name="budget_plan_id" class="form-select" required>
                    @foreach($activity->budgetPlans as $bp)
                    @php
                        $used = $bp->realizations()->whereIn('status', ['draft', 'submitted', 'verified', 'revision'])->sum('gross_amount');
                        $sisa = max(0, $bp->total - $used);
                    @endphp
                        <option value="{{ $bp->id }}">{{ $bp->account_code }} — {{ $bp->description }} (Sisa RAB: Rp {{ number_format($sisa, 0, ',', '.') }})</option>
                    @endforeach
                </select>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label">Tanggal Transaksi</label>
                    <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nomor Bukti / Kuitansi</label>
                    <input type="text" name="receipt_number" class="form-control" placeholder="contoh: KW/2026/001" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label">Penerima Dana</label>
                    <input type="text" name="recipient_name" class="form-control" placeholder="contoh: Budi Santoso">
                </div>
                <div class="form-group">
                    <label class="form-label">Nama Penyedia / Toko / Vendor</label>
                    <input type="text" name="vendor_name" class="form-control" placeholder="contoh: CV Atk Jaya">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label">Nominal Bruto (Rp)</label>
                    <input type="number" name="gross_amount" id="rel_gross" class="form-control" min="1" oninput="calcNet()" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Pajak Potongan (Rp)</label>
                    <input type="number" name="tax_amount" id="rel_tax" class="form-control" value="0" min="0" oninput="calcNet()">
                </div>
                <div class="form-group">
                    <label class="form-label">Nilai Bersih (Rp)</label>
                    <input type="text" id="rel_net_display" class="form-control" value="Rp 0" disabled style="background-color:#f1f5f9; font-weight:bold; color:var(--success);">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Metode Pembayaran</label>
                <select name="payment_method" class="form-select">
                    <option value="transfer">Transfer Bank / CMS</option>
                    <option value="cash">Tunai / Kas Bendahara</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Uraian Keterangan Transaksi</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Keterangan keperluan pembayaran..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAddRealisasi').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Realisasi Draft</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Upload Dokumen -->
<div id="modalUploadDoc" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:550px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Unggah Dokumen Berkas Kegiatan</h3>
            <button onclick="document.getElementById('modalUploadDoc').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('activities.documents.store', $activity->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Jenis Dokumen</label>
                <select name="document_type_id" class="form-select" required>
                    @foreach($allDocTypes as $dt)
                        <option value="{{ $dt->id }}">{{ $dt->name }} (Stage: {{ strtoupper($dt->stage) }})</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Pilih Berkas File (PDF, Image, Office Doc)</label>
                <input type="file" name="file" class="form-control" required>
                <small style="color:var(--text-muted);">Maksimal 10 MB per berkas</small>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalUploadDoc').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Unggah Berkas</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah RAB -->
<div id="modalAddRab" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:550px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Tambah Item Rincian RAB</h3>
            <button onclick="document.getElementById('modalAddRab').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('activities.budget-plans.store', $activity->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Jenis Belanja</label>
                <select name="expense_type_id" class="form-select" required>
                    @foreach(\App\Models\ExpenseType::where('is_active', true)->orderBy('code')->get() as $et)
                        <option value="{{ $et->id }}">{{ $et->code }} - {{ $et->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Uraian Rincian Belanja</label>
                <input type="text" name="description" class="form-control" required>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label">Volume</label>
                    <input type="number" name="volume" id="add_vol" class="form-control" value="1" min="1" oninput="calcAddTotal()" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Satuan</label>
                    <input type="text" name="unit" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Harga Satuan (Rp)</label>
                <input type="number" name="unit_price" id="add_price" class="form-control" min="0" oninput="calcAddTotal()" required>
            </div>
            <div class="form-group">
                <label class="form-label">Kalkulasi Total (Rp)</label>
                <input type="text" id="add_total_display" class="form-control" value="Rp 0" disabled style="background-color:#f1f5f9; font-weight:bold; color:var(--accent-color);">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalAddRab').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Item RAB</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pembatalan -->
<div id="modalCancel" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Batalkan Kegiatan</h3>
            <button onclick="document.getElementById('modalCancel').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('activities.cancel', $activity->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Alasan Pembatalan (Wajib)</label>
                <textarea name="cancellation_reason" class="form-control" rows="4" required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalCancel').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-danger">Batalkan Kegiatan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function showTab(name) {
        document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.btn-tab').forEach(el => el.classList.remove('active'));
        
        document.getElementById('tabContent-' + name).style.display = 'block';
        document.getElementById('tabBtn-' + name).classList.add('active');
    }

    function calcNet() {
        const gross = parseInt(document.getElementById('rel_gross').value) || 0;
        const tax = parseInt(document.getElementById('rel_tax').value) || 0;
        const net = Math.max(0, gross - tax);
        document.getElementById('rel_net_display').value = 'Rp ' + net.toLocaleString('id-ID');
    }

    function calcAddTotal() {
        const vol = parseInt(document.getElementById('add_vol').value) || 0;
        const price = parseInt(document.getElementById('add_price').value) || 0;
        document.getElementById('add_total_display').value = 'Rp ' + (vol * price).toLocaleString('id-ID');
    }
</script>
@endsection
