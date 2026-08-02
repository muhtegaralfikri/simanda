@extends('admin.layouts.app')

@section('title', 'Buat Kegiatan Baru')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Buat Kegiatan Baru</h1>
        <div class="page-subtitle">Input data identitas kegiatan, penanggung jawab, jadwal, dan pagu anggaran ({{ $activeYear->year }})</div>
    </div>
    <a href="{{ route('activities.index') }}" class="btn btn-secondary">&larr; Kembali</a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="{{ route('activities.store') }}" method="POST">
        @csrf

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            @if(auth()->user()->isAdmin())
            <div class="form-group">
                <label class="form-label">Unit Kerja</label>
                <select name="unit_id" id="unit_id" class="form-select" required>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->code }} - {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="unit_id" value="{{ auth()->user()->unit_id }}">
            <div class="form-group">
                <label class="form-label">Unit Kerja</label>
                <input type="text" class="form-control" value="{{ auth()->user()->unit ? auth()->user()->unit->name : '' }}" disabled style="background-color:#f1f5f9;">
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">Program Kegiatan</label>
                <select name="program_id" class="form-select" required>
                    <option value="">-- Pilih Program --</option>
                    @foreach($programs as $prg)
                        <option value="{{ $prg->id }}" {{ old('program_id') == $prg->id ? 'selected' : '' }}>{{ $prg->program_code }} - {{ $prg->program_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label">Kode Kegiatan</label>
                <input type="text" name="activity_code" class="form-control" placeholder="contoh: KGT-001" value="{{ old('activity_code') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Nama Kegiatan</label>
                <input type="text" name="activity_name" class="form-control" placeholder="contoh: Pelatihan Manajemen Risiko" value="{{ old('activity_name') }}" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            @if(auth()->user()->isAdmin())
            <div class="form-group">
                <label class="form-label">Penanggung Jawab (PPTK)</label>
                <select name="person_in_charge_id" class="form-select" required>
                    @foreach($pptkUsers as $pu)
                        <option value="{{ $pu->id }}" {{ old('person_in_charge_id') == $pu->id ? 'selected' : '' }}>{{ $pu->name }} ({{ $pu->unit ? $pu->unit->code : 'SKR' }})</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" name="person_in_charge_id" value="{{ auth()->user()->id }}">
            <div class="form-group">
                <label class="form-label">Penanggung Jawab (PPTK)</label>
                <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled style="background-color:#f1f5f9;">
            </div>
            @endif

            <div class="form-group">
                <label class="form-label">Sumber Dana</label>
                <select name="funding_source_id" class="form-select" required>
                    @foreach($fundingSources as $fs)
                        <option value="{{ $fs->id }}" {{ old('funding_source_id') == $fs->id ? 'selected' : '' }}>{{ $fs->code }} - {{ $fs->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', date('Y-m-d')) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', date('Y-m-d', strtotime('+30 days'))) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Pagu Anggaran (Rp)</label>
                <input type="number" name="budget_ceiling" class="form-control" placeholder="contoh: 50000000" value="{{ old('budget_ceiling') }}" min="0" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label">Lokasi Pelaksanaan</label>
                <input type="text" name="location" class="form-control" placeholder="contoh: Aula Lantai 3 / Hotel Santika" value="{{ old('location') }}">
            </div>

            <div class="form-group">
                <label class="form-label">Target Outut / Sasaran</label>
                <input type="text" name="target" class="form-control" placeholder="contoh: 50 Orang Peserta Terlatih" value="{{ old('target') }}">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Deskripsi Tambahan / Uraian Singkat</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Uraian ringkas tujuan dan latar belakang kegiatan">{{ old('description') }}</textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border-color);">
            <a href="{{ route('activities.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan & Lanjut ke Input RAB &rarr;</button>
        </div>
    </form>
</div>
@endsection
