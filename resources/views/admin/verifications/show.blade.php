@extends('admin.layouts.app')

@section('title', 'Pemeriksaan & Verifikasi Kegiatan - ' . $activity->activity_code)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $activity->activity_name }}</h1>
        <div class="page-subtitle">
            Kode: <code>{{ $activity->activity_code }}</code> &bull; Unit: <strong>{{ $activity->unit ? $activity->unit->name : '-' }}</strong> &bull; PPTK: <strong>{{ $activity->personInCharge ? $activity->personInCharge->name : '-' }}</strong> &bull; Putaran Verifikasi: <span class="badge badge-info">Putaran {{ $activity->verification_round }}</span>
        </div>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('admin.verifications.index') }}" class="btn btn-secondary">&larr; Kembali ke Antrean</a>
        @if(auth()->user()->isVerifier() && $activity->status === 'waiting_verification')
            @if($activity->submission_status !== 'under_review')
            <form action="{{ route('admin.verifications.start', $activity->id) }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-primary">&play; Mulai Pemeriksaan</button>
            </form>
            @endif
        @endif
    </div>
</div>

<!-- Indicators Grid -->
<div class="stats-grid">
    <div class="stat-card" style="border-left: 4px solid var(--accent-color);">
        <div class="stat-label">Pagu Anggaran Kegiatan</div>
        <div class="stat-value">Rp {{ number_format($activity->budget_ceiling, 0, ',', '.') }}</div>
        <div class="stat-subtext">RAB Teralokasi: Rp {{ number_format($activity->total_budget_plan, 0, ',', '.') }}</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--success);">
        <div class="stat-label">Realisasi Terverifikasi (Final)</div>
        <div class="stat-value" style="color:var(--success);">Rp {{ number_format($activity->verified_realization_total, 0, ',', '.') }}</div>
        <div class="stat-subtext">Aktif/Diajukan: Rp {{ number_format($activity->active_realization_total, 0, ',', '.') }}</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--info);">
        <div class="stat-label">Sisa Anggaran Final</div>
        <div class="stat-value" style="color:var(--info);">Rp {{ number_format($activity->final_remaining_budget, 0, ',', '.') }}</div>
        <div class="stat-subtext">Pagu minus Realisasi Verified</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
        <div class="stat-label">Kelengkapan Dokumen Valid</div>
        <div class="stat-value" style="color:#8b5cf6;">{{ $activity->document_completeness['valid_percentage'] }}%</div>
        <div class="stat-subtext">{{ $activity->document_completeness['valid_required'] }}/{{ $activity->document_completeness['total_required'] }} Dokumen Wajib Valid</div>
    </div>
</div>

<!-- SECTION 1: REALISASI ANGGARAN REVIEW -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3 class="card-title">1. Verifikasi Realisasi Anggaran Transaksi</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No Bukti</th>
                    <th>Tanggal</th>
                    <th>Uraian RAB & Jenis Belanja</th>
                    <th>Penerima / Vendor</th>
                    <th style="text-align:right;">Bruto (Rp)</th>
                    <th style="text-align:right;">Pajak (Rp)</th>
                    <th style="text-align:right;">Bersih (Rp)</th>
                    <th>Status Saat Ini</th>
                    <th style="text-align:center;">Keputusan Verifikator</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activity->realizations as $rel)
                <tr>
                    <td><code>{{ $rel->receipt_number }}</code></td>
                    <td>{{ $rel->transaction_date->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $rel->budgetPlan ? $rel->budgetPlan->description : '-' }}</strong><br>
                        <small style="color:var(--text-muted);">{{ $rel->expenseType ? $rel->expenseType->name : '-' }}</small>
                    </td>
                    <td>{{ $rel->recipient_name ?? '-' }}</td>
                    <td style="text-align:right;"><strong>{{ number_format($rel->gross_amount, 0, ',', '.') }}</strong></td>
                    <td style="text-align:right;">{{ number_format($rel->tax_amount, 0, ',', '.') }}</td>
                    <td style="text-align:right; color:var(--success);">{{ number_format($rel->net_amount, 0, ',', '.') }}</td>
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
                        @if(auth()->user()->isVerifier() && $activity->status === 'waiting_verification')
                        <button class="btn btn-secondary btn-sm" onclick="openVerifyRelModal({{ json_encode($rel) }})">
                            &edit; Verifikasi
                        </button>
                        @else
                        <small style="color:var(--text-muted);">Catatan: {{ $rel->verification_note ?? '-' }}</small>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; color:var(--text-muted); padding:24px;">Belum ada realisasi transaksi diajukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- SECTION 2: DOKUMEN KEGIATAN REVIEW -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3 class="card-title">2. Verifikasi Dokumen & Berkas Kegiatan</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Jenis Dokumen</th>
                    <th>Nama Berkas Asli</th>
                    <th>Versi</th>
                    <th>Ukuran</th>
                    <th>Status Saat Ini</th>
                    <th>Catatan Verifikasi</th>
                    <th style="text-align:center;">Aksi Verifikator</th>
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
                    <td><small>{{ $doc->verification_note ?? '-' }}</small></td>
                    <td style="text-align:center;">
                        <div style="display:flex; justify-content:center; gap:4px;">
                            <a href="{{ route('documents.preview', $doc->id) }}" target="_blank" class="btn btn-secondary btn-sm">Preview</a>
                            @if(auth()->user()->isVerifier() && $activity->status === 'waiting_verification')
                            <button class="btn btn-primary btn-sm" onclick="openVerifyDocModal({{ json_encode($doc) }})">Verifikasi</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center; color:var(--text-muted); padding:24px;">Belum ada dokumen diunggah.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- SECTION 3: VERIFIER DECISION ACTIONS (HANYA UNTUK ROLE VERIFIER) -->
@if(auth()->user()->isVerifier() && $activity->status === 'waiting_verification')
<div class="card" style="border: 2px solid var(--accent-color); margin-bottom:24px; background:#faf5ff;">
    <div class="card-header" style="background:transparent;">
        <h3 class="card-title" style="color:var(--accent-color);">3. Keputusan Akhir Verifikasi Kegiatan</h3>
    </div>
    <div style="padding:16px; display:flex; gap:12px; flex-wrap:wrap;">
        <button class="btn btn-secondary" onclick="document.getElementById('modalReqRev').style.display='block'">
            &circlearrowleft; Kembalikan untuk Revisi Kegiatan
        </button>

        <button class="btn btn-danger" onclick="document.getElementById('modalRejectSub').style.display='block'">
            &times; Tolak Pengajuan Kegiatan
        </button>

        <button class="btn btn-success" onclick="document.getElementById('modalCloseAct').style.display='block'" style="background:var(--success); color:white; font-weight:bold;">
            &check; Setujui & Tutup Kegiatan
        </button>
    </div>
</div>
@endif

<!-- SECTION 4: RIWAYAT KEPUTUSAN VERIFIKASI (READ-ONLY LOG) -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">4. Riwayat Keputusan Verifikasi</h3>
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
                    <td colspan="6" style="text-align:center; color:var(--text-muted); padding:24px;">Belum ada riwayat keputusan verifikasi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Verifikasi Realisasi -->
<div id="modalVerifyRel" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Verifikasi Realisasi Transaksi</h3>
            <button onclick="document.getElementById('modalVerifyRel').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form id="formVerifyRel" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Keputusan Verifikasi</label>
                <select name="decision" class="form-select" required>
                    <option value="verified">Setujui (Verified)</option>
                    <option value="revision">Kembalikan (Perlu Revisi)</option>
                    <option value="rejected">Tolak (Rejected)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan Verifikasi (Wajib jika Revisi/Ditolak)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Keterangan hasil pemeriksaan..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalVerifyRel').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Keputusan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Verifikasi Dokumen -->
<div id="modalVerifyDoc" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Verifikasi Dokumen Berkas</h3>
            <button onclick="document.getElementById('modalVerifyDoc').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form id="formVerifyDoc" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Keputusan Verifikasi Dokumen</label>
                <select name="decision" class="form-select" required>
                    <option value="valid">Setujui & Validasi (Valid)</option>
                    <option value="revision">Kembalikan (Perlu Revisi)</option>
                    <option value="rejected">Tolak (Rejected)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Catatan Verifikasi (Wajib jika Revisi/Ditolak)</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Keterangan hasil pemeriksaan fisik berkas..."></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalVerifyDoc').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Keputusan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Minta Revisi Kegiatan -->
<div id="modalReqRev" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Kembalikan Kegiatan untuk Revisi</h3>
            <button onclick="document.getElementById('modalReqRev').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('admin.verifications.request-revision', $activity->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Catatan Revisi untuk PPTK (Wajib)</label>
                <textarea name="notes" class="form-control" rows="4" placeholder="Jelaskan bagian realisasi atau dokumen yang harus diperbaiki..." required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalReqRev').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-warning" style="background:#f97316;color:white;">Kirim Catatan Revisi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tolak Pengajuan -->
<div id="modalRejectSub" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:500px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Tolak Pengajuan Kegiatan</h3>
            <button onclick="document.getElementById('modalRejectSub').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('admin.verifications.reject', $activity->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Alasan Penolakan Pengajuan (Wajib)</label>
                <textarea name="notes" class="form-control" rows="4" placeholder="Alasan penolakan pengajuan awal..." required></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalRejectSub').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Setujui & Tutup Kegiatan -->
<div id="modalCloseAct" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:100; padding:40px 20px; overflow-y:auto;">
    <div class="card" style="max-width:550px; margin:0 auto; background:white;">
        <div class="card-header">
            <h3 class="card-title">Setujui & Tutup Kegiatan Final</h3>
            <button onclick="document.getElementById('modalCloseAct').style.display='none'" style="background:none;border:none;font-size:1.2rem;cursor:pointer;">&times;</button>
        </div>
        <form action="{{ route('admin.verifications.close', $activity->id) }}" method="POST">
            @csrf
            <div class="alert alert-info">
                <strong>Peringatan:</strong> Setelah ditutup, status kegiatan menjadi <strong>COMPLETED</strong> dan seluruh data (RAB, Realisasi, Dokumen) menjadi <strong>READ-ONLY</strong>.
            </div>
            
            @if($activity->final_remaining_budget > 0)
            <div class="form-group">
                <label class="form-label">Catatan Sisa Anggaran (Wajib: Sisa Rp {{ number_format($activity->final_remaining_budget, 0, ',', '.') }})</label>
                <textarea name="remaining_budget_note" class="form-control" rows="3" placeholder="Penjelasan mengenai efisiensi / sisa dana yang tidak terpakai..." required></textarea>
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">Catatan Penutupan Kegiatan</label>
                <textarea name="closing_note" class="form-control" rows="3" placeholder="Catatan evaluasi penutupan kegiatan..."></textarea>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalCloseAct').style.display='none'">Batal</button>
                <button type="submit" class="btn btn-success" style="background:var(--success); color:white;">Setujui & Lock Completed</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openVerifyRelModal(rel) {
        document.getElementById('formVerifyRel').action = '/realizations/' + rel.id + '/verify';
        document.getElementById('modalVerifyRel').style.display = 'block';
    }

    function openVerifyDocModal(doc) {
        document.getElementById('formVerifyDoc').action = '/documents/' + doc.id + '/verify';
        document.getElementById('modalVerifyDoc').style.display = 'block';
    }
</script>
@endsection
