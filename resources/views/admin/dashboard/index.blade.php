@extends('admin.layouts.app')

@section('title', 'Dashboard Analitik SIMANDA')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Monitoring & Analitik</h1>
        <div class="page-subtitle">Selamat datang kembali, <strong>{{ auth()->user()->name }}</strong> ({{ strtoupper(auth()->user()->role) }})</div>
    </div>
</div>

<!-- Global Filter Form -->
<div class="card" style="margin-bottom:24px;">
    <form method="GET" action="{{ route('dashboard') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
        <div style="min-width:140px;">
            <select name="budget_year_id" class="form-select" onchange="this.form.submit()">
                @foreach($budgetYears as $by)
                    <option value="{{ $by->id }}" {{ ($analytics['active_year_id'] == $by->id) ? 'selected' : '' }}>TA {{ $by->year }} {{ $by->is_active ? '(Aktif)' : '' }}</option>
                @endforeach
            </select>
        </div>

        @if(auth()->user()->isAdmin() || auth()->user()->isPimpinan() || auth()->user()->isVerifier())
        <div style="min-width:180px;">
            <select name="unit_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Unit --</option>
                @foreach($units as $u)
                    <option value="{{ $u->id }}" {{ request('unit_id') == $u->id ? 'selected' : '' }}>{{ $u->code }} - {{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        <div style="min-width:160px;">
            <select name="funding_source_id" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Sumber Dana --</option>
                @foreach($fundingSources as $fs)
                    <option value="{{ $fs->id }}" {{ request('funding_source_id') == $fs->id ? 'selected' : '' }}>{{ $fs->code }}</option>
                @endforeach
            </select>
        </div>

        <div style="min-width:160px;">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">-- Semua Status --</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="planned" {{ request('status') == 'planned' ? 'selected' : '' }}>Direncanakan</option>
                <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Sedang Berjalan</option>
                <option value="waiting_verification" {{ request('status') == 'waiting_verification' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                <option value="revision" {{ request('status') == 'revision' ? 'selected' : '' }}>Perlu Revisi</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
            </select>
        </div>

        <button type="submit" class="btn btn-secondary">Terapkan Filter</button>
        <a href="{{ route('dashboard') }}" class="btn btn-secondary" style="color:var(--text-muted);">Reset</a>
    </form>
</div>

<!-- Indicator Cards -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card" style="border-left: 4px solid var(--accent-color);">
        <div class="stat-label">Total Pagu Anggaran</div>
        <div class="stat-value">Rp {{ number_format($analytics['budget_cards']['total_ceiling'], 0, ',', '.') }}</div>
        <div class="stat-subtext">RAB: Rp {{ number_format($analytics['budget_cards']['total_rab'], 0, ',', '.') }}</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--info);">
        <div class="stat-label">Realisasi Aktif</div>
        <div class="stat-value" style="color:var(--info);">Rp {{ number_format($analytics['budget_cards']['active_realization_total'], 0, ',', '.') }}</div>
        <div class="stat-subtext">Draft + Submitted + Verified</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--success);">
        <div class="stat-label">Realisasi Terverifikasi (Final)</div>
        <div class="stat-value" style="color:var(--success);">Rp {{ number_format($analytics['budget_cards']['verified_realization_total'], 0, ',', '.') }}</div>
        <div class="stat-subtext">Serapan: <strong>{{ $analytics['budget_cards']['absorption_percentage'] }}%</strong></div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
        <div class="stat-label">Sisa Anggaran Final</div>
        <div class="stat-value" style="color:#8b5cf6;">Rp {{ number_format($analytics['budget_cards']['final_remaining_budget'], 0, ',', '.') }}</div>
        <div class="stat-subtext">Pagu minus Realisasi Verified</div>
    </div>
</div>

<!-- Activity Status Badges Count -->
<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3 class="card-title">Ringkasan Status Pelaksanaan Kegiatan (Total: {{ $analytics['status_counts']['total'] }})</h3>
    </div>
    <div style="display:flex; gap:12px; flex-wrap:wrap; padding:16px;">
        <span class="badge badge-secondary" style="font-size:0.9rem; padding:8px 12px;">Draft: <strong>{{ $analytics['status_counts']['draft'] }}</strong></span>
        <span class="badge badge-success" style="font-size:0.9rem; padding:8px 12px;">Direncanakan: <strong>{{ $analytics['status_counts']['planned'] }}</strong></span>
        <span class="badge badge-info" style="font-size:0.9rem; padding:8px 12px;">Sedang Berjalan: <strong>{{ $analytics['status_counts']['ongoing'] }}</strong></span>
        <span class="badge badge-warning" style="font-size:0.9rem; padding:8px 12px;">Menunggu Verifikasi: <strong>{{ $analytics['status_counts']['waiting_verification'] }}</strong></span>
        <span class="badge badge-secondary" style="font-size:0.9rem; padding:8px 12px; background:#f97316; color:white;">Perlu Revisi: <strong>{{ $analytics['status_counts']['revision'] }}</strong></span>
        <span class="badge badge-success" style="font-size:0.9rem; padding:8px 12px; background:var(--success);">Selesai (Completed): <strong>{{ $analytics['status_counts']['completed'] }}</strong></span>
        <span class="badge badge-danger" style="font-size:0.9rem; padding:8px 12px;">Dibatalkan: <strong>{{ $analytics['status_counts']['cancelled'] }}</strong></span>
    </div>
</div>

<!-- PPTK Action Panel (If user is PPTK) -->
@if(auth()->user()->isPPTK() && count($analytics['action_items']) > 0)
<div class="card" style="border:2px solid var(--accent-color); margin-bottom:24px;">
    <div class="card-header">
        <h3 class="card-title" style="color:var(--accent-color);">⚡ Tindakan yang Perlu Anda Selesaikan</h3>
    </div>
    <div style="display:flex; flex-direction:column; gap:8px; padding:16px;">
        @foreach($analytics['action_items'] as $item)
        <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:10px 16px; border-radius:6px; border:1px solid #e2e8f0;">
            <div>
                <strong>{{ $item['title'] }}</strong>
                <span class="badge badge-warning" style="margin-left:8px;">{{ $item['badge'] }}</span>
            </div>
            <a href="{{ route('activities.show', $item['activity_id']) }}" class="btn btn-primary btn-sm">Buka Kegiatan</a>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Charts Carousel / Slide Container -->
<div x-data="{ 
    activeSlide: 0, 
    totalSlides: 4,
    titles: [
        'Pagu vs Realisasi Terverifikasi per Unit',
        'Serapan Realisasi Terverifikasi Bulanan',
        'Distribusi Status Kegiatan',
        'Serapan Realisasi per Sumber Dana'
    ],
    next() { this.activeSlide = (this.activeSlide + 1) % this.totalSlides; },
    prev() { this.activeSlide = (this.activeSlide - 1 + this.totalSlides) % this.totalSlides; }
}" class="card" style="margin-bottom:24px;">
    
    <!-- Carousel Header & Navigation Controls -->
    <div class="card-header" style="flex-wrap:nowrap; gap:8px;">
        <div>
            <h3 class="card-title" x-text="titles[activeSlide]">Grafik Analitik</h3>
            <div style="font-size:0.78rem; color:var(--text-muted);">Gunakan tombol slide untuk beralih antar grafik</div>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <button type="button" @click="prev()" class="btn btn-secondary btn-sm" title="Slide Sebelumnya">
                &larr; Prev
            </button>
            <span style="font-size:0.8rem; font-weight:600; color:var(--text-muted); min-width:48px; text-align:center;">
                <span x-text="activeSlide + 1"></span> / 4
            </span>
            <button type="button" @click="next()" class="btn btn-secondary btn-sm" title="Slide Selanjutnya">
                Next &rarr;
            </button>
        </div>
    </div>

    <!-- Slide Indicators / Tab Pills -->
    <div style="display:flex; gap:6px; overflow-x:auto; padding:8px 16px; background:#f8fafc; border-bottom:1px solid var(--border-color);">
        <template x-for="(title, idx) in titles" :key="idx">
            <button type="button" 
                @click="activeSlide = idx" 
                class="btn btn-sm" 
                :class="activeSlide === idx ? 'btn-primary' : 'btn-secondary'"
                style="white-space:nowrap; font-size:0.75rem;">
                <span x-text="(idx+1) + '. ' + title"></span>
            </button>
        </template>
    </div>

    <!-- Carousel Body -->
    <div style="padding:16px; position:relative;">
        <!-- Chart 1: Pagu vs Realisasi per Unit -->
        <div x-show="activeSlide === 0" style="height:320px; position:relative;">
            <canvas id="chartUnit"></canvas>
        </div>

        <!-- Chart 2: Realisasi Serapan Bulanan -->
        <div x-show="activeSlide === 1" style="height:320px; position:relative;">
            <canvas id="chartMonthly"></canvas>
        </div>

        <!-- Chart 3: Distribusi Status Kegiatan -->
        <div x-show="activeSlide === 2" style="height:320px; position:relative;">
            <canvas id="chartStatus"></canvas>
        </div>

        <!-- Chart 4: Serapan per Sumber Dana -->
        <div x-show="activeSlide === 3" style="height:320px; position:relative;">
            <canvas id="chartFunding"></canvas>
        </div>
    </div>
</div>

<!-- Table Delayed Activities -->
@if(count($analytics['delayed_activities']) > 0)
<div class="card" style="border-left:4px solid var(--danger);">
    <div class="card-header">
        <h3 class="card-title" style="color:var(--danger);">⚠️ Kegiatan Terlambat Melewati Tenggat Waktu</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Kegiatan</th>
                    <th>Unit Kerja</th>
                    <th>Tenggat Waktu</th>
                    <th>Progres</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analytics['delayed_activities'] as $delay)
                <tr>
                    <td><strong>{{ $delay->activity_code }}</strong></td>
                    <td>{{ $delay->activity_name }}</td>
                    <td><span class="badge badge-secondary">{{ $delay->unit ? $delay->unit->name : '-' }}</span></td>
                    <td style="color:var(--danger); font-weight:600;">{{ $delay->end_date ? \Carbon\Carbon::parse($delay->end_date)->translatedFormat('d F Y') : '-' }}</td>
                    <td><span class="badge badge-warning">{{ $delay->progress_percentage }}%</span></td>
                    <td>
                        <a href="{{ route('activities.show', $delay->id) }}" class="btn btn-secondary btn-sm">Lihat Detail</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Chart Unit
    const ctxUnit = document.getElementById('chartUnit').getContext('2d');
    new Chart(ctxUnit, {
        type: 'bar',
        data: {
            labels: {!! json_encode($analytics['charts']['unit_chart']['labels']) !!},
            datasets: [
                {
                    label: 'Pagu Anggaran',
                    data: {!! json_encode($analytics['charts']['unit_chart']['ceilings']) !!},
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                },
                {
                    label: 'Realisasi Verified (Rp)',
                    data: {!! json_encode($analytics['charts']['unit_chart']['verified']) !!},
                    backgroundColor: '#16a34a',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // 2. Chart Monthly
    const ctxMonthly = document.getElementById('chartMonthly').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'line',
        data: {
            labels: {!! json_encode($analytics['charts']['monthly_chart']['labels']) !!},
            datasets: [{
                label: 'Realisasi Verified (Rp)',
                data: {!! json_encode($analytics['charts']['monthly_chart']['totals']) !!},
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // 3. Chart Status
    const ctxStatus = document.getElementById('chartStatus').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($analytics['charts']['status_chart']['labels']) !!},
            datasets: [{
                data: {!! json_encode($analytics['charts']['status_chart']['data']) !!},
                backgroundColor: ['#94a3b8', '#3b82f6', '#0284c7', '#d97706', '#f97316', '#16a34a', '#dc2626']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'right' } }
        }
    });

    // 4. Chart Funding
    const ctxFunding = document.getElementById('chartFunding').getContext('2d');
    new Chart(ctxFunding, {
        type: 'bar',
        data: {
            labels: {!! json_encode($analytics['charts']['funding_chart']['labels']) !!},
            datasets: [
                {
                    label: 'Pagu Anggaran',
                    data: {!! json_encode($analytics['charts']['funding_chart']['ceilings']) !!},
                    backgroundColor: '#8b5cf6',
                    borderRadius: 4
                },
                {
                    label: 'Realisasi Verified',
                    data: {!! json_encode($analytics['charts']['funding_chart']['verified']) !!},
                    backgroundColor: '#16a34a',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
@endsection
