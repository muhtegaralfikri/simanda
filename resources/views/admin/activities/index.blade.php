@extends('admin.layouts.app')

@section('title', 'Data Kegiatan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Data Kegiatan</h1>
        <div class="page-subtitle">Daftar kegiatan, alokasi pagu anggaran, dan status penyusunan RAB</div>
    </div>
    @can('create', App\Models\Activity::class)
    <a href="{{ route('activities.create') }}" class="btn btn-primary">
        + Buat Kegiatan Baru
    </a>
    @endcan
</div>

<div class="card">
    <div class="card-header" style="border-bottom:none; margin-bottom:0; padding-bottom:0;">
        <form method="GET" action="{{ route('activities.index') }}" style="display:flex; gap:12px; width:100%; flex-wrap:wrap;">
            @if(auth()->user()->isAdmin() || auth()->user()->isPimpinan())
            <div style="min-width:180px;">
                <select name="unit_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Unit --</option>
                    @foreach($units as $u)
                        <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->code }} - {{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div style="min-width:180px;">
                <select name="program_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Program --</option>
                    @foreach($programs as $prg)
                        <option value="{{ $prg->id }}" {{ request('program_id') == $prg->id ? 'selected' : '' }}>{{ $prg->program_code }} - {{ $prg->program_name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="min-width:150px;">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Status --</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="planned" {{ request('status') == 'planned' ? 'selected' : '' }}>Direncanakan</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>

            <div style="flex:1; min-width:180px;">
                <input type="text" name="search" class="form-control" placeholder="Cari kode/nama kegiatan..." value="{{ request('search') }}">
            </div>
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if(request()->hasAny(['unit_id', 'program_id', 'status', 'search']))
                <a href="{{ route('activities.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="table-responsive" style="margin-top:16px;">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Kegiatan</th>
                    <th>Unit & Program</th>
                    <th>PPTK</th>
                    <th>Jadwal</th>
                    <th>Pagu Anggaran</th>
                    <th>Total RAB</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                <tr>
                    <td><code>{{ $act->activity_code }}</code></td>
                    <td>
                        <a href="{{ route('activities.show', $act->id) }}" style="font-weight:700; color:var(--accent-color); text-decoration:none;">
                            {{ $act->activity_name }}
                        </a>
                        <br><small style="color:var(--text-muted);">{{ $act->fundingSource ? $act->fundingSource->name : '' }}</small>
                    </td>
                    <td>
                        <strong>{{ $act->unit ? $act->unit->code : '-' }}</strong><br>
                        <small style="color:var(--text-muted);">{{ $act->program ? $act->program->program_name : '-' }}</small>
                    </td>
                    <td><small>{{ $act->personInCharge ? $act->personInCharge->name : '-' }}</small></td>
                    <td><small>{{ $act->start_date->format('d/m/Y') }} - {{ $act->end_date->format('d/m/Y') }}</small></td>
                    <td><strong>Rp {{ number_format($act->budget_ceiling, 0, ',', '.') }}</strong></td>
                    <td>
                        @php $totalRab = $act->total_rab ?? $act->total_budget_plan; @endphp
                        Rp {{ number_format($totalRab, 0, ',', '.') }}
                    </td>
                    <td>
                        @switch($act->status)
                            @case('draft') <span class="badge badge-secondary">Draft</span> @break
                            @case('planned') <span class="badge badge-success">Direncanakan</span> @break
                            @case('cancelled') <span class="badge badge-danger">Dibatalkan</span> @break
                            @default <span class="badge badge-info">{{ $act->status }}</span>
                        @endswitch
                    </td>
                    <td>
                        <div style="display:flex; gap:6px;">
                            <a href="{{ route('activities.show', $act->id) }}" class="btn btn-secondary btn-sm">RAB / Detail</a>
                            @can('update', $act)
                            <a href="{{ route('activities.edit', $act->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; color: var(--text-muted);">Belum ada data kegiatan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $activities->links() }}
    </div>
</div>
@endsection
