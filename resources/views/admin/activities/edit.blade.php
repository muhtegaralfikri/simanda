@extends('admin.layouts.app')

@section('title', 'Edit Kegiatan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Kegiatan</h1>
        <div class="page-subtitle">Ubah identitas, pagu anggaran, atau jadwal kegiatan <code>{{ $activity->activity_code }}</code></div>
    </div>
    <a href="{{ route('activities.show', $activity->id) }}" class="btn btn-secondary">&larr; Batal & Kembali</a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="{{ route('activities.update', $activity->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            @if(auth()->user()->isAdmin())
            <div class="form-group">
                <label class="form-label">Unit Kerja</label>
                <select name="unit_id" class="form-select" required>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" {{ old('unit_id', $activity->unit_id) == $u->id ? 'selected' : '' }}>{{ $u->code }} - {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="unit_id" value="{{ $activity->unit_id }}">
            <div class="form-group">
                <label class="form-label">Unit Kerja</label>
                <input type="text" class="form-control" value="{{ $activity->unit ? $activity->unit->name : '' }}" disabled style="background-color:#f1f5f9;">
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">Program Kegiatan</label>
                <select name="program_id" class="form-select" required>
                    @foreach($programs as $prg)
                        <option value="{{ $prg->id }}" {{ old('program_id', $activity->program_id) == $prg->id ? 'selected' : '' }}>{{ $prg->program_code }} - {{ $prg->program_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label">Kode Kegiatan</label>
                <input type="text" name="activity_code" class="form-control" value="{{ old('activity_code', $activity->activity_code) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Nama Kegiatan</label>
                <input type="text" name="activity_name" class="form-control" value="{{ old('activity_name', $activity->activity_name) }}" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            @if(auth()->user()->isAdmin())
            <div class="form-group">
                <label class="form-label">Penanggung Jawab (PPTK)</label>
                <select name="person_in_charge_id" class="form-select" required>
                    @foreach($pptkUsers as $pu)
                        <option value="{{ $pu->id }}" {{ old('person_in_charge_id', $activity->person_in_charge_id) == $pu->id ? 'selected' : '' }}>{{ $pu->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="person_in_charge_id" value="{{ $activity->person_in_charge_id }}">
            <div class="form-group">
                <label class="form-label">Penanggung Jawab (PPTK)</label>
                <input type="text" class="form-control" value="{{ $activity->personInCharge ? $activity->personInCharge->name : '' }}" disabled style="background-color:#f1f5f9;">
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">Sumber Dana</label>
                <select name="funding_source_id" class="form-select" required>
                    @foreach($fundingSources as $fs)
                        <option value="{{ $fs->id }}" {{ old('funding_source_id', $activity->funding_source_id) == $fs->id ? 'selected' : '' }}>{{ $fs->code }} - {{ $fs->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $activity->start_date->format('Y-m-d')) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $activity->end_date->format('Y-m-d')) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Pagu Anggaran (Rp)</label>
                <input type="number" name="budget_ceiling" class="form-control" value="{{ old('budget_ceiling', $activity->budget_ceiling) }}" min="0" required>
                <small style="color:var(--text-muted);">Minimal sama dengan RAB: Rp {{ number_format($activity->total_budget_plan, 0, ',', '.') }}</small>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label">Lokasi Pelaksanaan</label>
                <input type="text" name="location" class="form-control" value="{{ old('location', $activity->location) }}">
            </div>

            <div class="form-group">
                <label class="form-label">Target Output / Sasaran</label>
                <input type="text" name="target" class="form-control" value="{{ old('target', $activity->target) }}">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Deskripsi Tambahan</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $activity->description) }}</textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
            <a href="{{ route('activities.show', $activity->id) }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection
