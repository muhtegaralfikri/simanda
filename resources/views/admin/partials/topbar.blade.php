<header class="topbar">
    <div class="topbar-left">
        <!-- Mobile Drawer Toggle Button -->
        <button type="button" class="mobile-toggle-btn" @click="sidebarOpen = !sidebarOpen" title="Buka Menu Navigasi">
            <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        @php
            $activeYear = \App\Models\BudgetYear::where('is_active', true)->first();
            $unreadAlertsQuery = \App\Models\SystemAlert::query()
                ->whereNull('read_at')
                ->whereNull('resolved_at');
            if (! auth()->user()->isAdmin()) {
                $unreadAlertsQuery->where(function ($q) {
                    $q->where('user_id', auth()->id())->orWhereNull('user_id');
                });
            }
            $unreadAlertsCount = $unreadAlertsQuery->count();
        @endphp
        <div class="budget-year-badge">
            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Tahun Anggaran: <strong>{{ $activeYear ? $activeYear->year : 'Belum Set' }}</strong></span>
        </div>
        @if(auth()->user()->unit)
            <span class="desktop-only-unit" style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">
                Unit: <strong>{{ auth()->user()->unit->name }}</strong>
            </span>
        @else
            <span class="desktop-only-unit" style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">
                Akses: <strong>Seluruh Unit Kerja</strong>
            </span>
        @endif
    </div>

    <div class="topbar-right">
        <!-- Notification Bell Icon -->
        <a href="{{ route('admin.alerts.index') }}" class="btn btn-secondary btn-sm" style="position:relative; display:inline-flex; align-items:center; gap:4px;" title="Pusat Peringatan">
            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            <span style="display:none;" class="sm-visible-inline">Peringatan</span>
            @if($unreadAlertsCount > 0)
                <span class="badge badge-danger" style="border-radius:10px; padding:2px 6px; font-size:0.7rem; background:var(--danger); color:white;">{{ $unreadAlertsCount }}</span>
            @endif
        </a>

        <div class="user-profile-menu">
            <div class="user-info">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">
                    @switch(auth()->user()->role)
                        @case('admin') <span class="badge badge-info">Administrator</span> @break
                        @case('pimpinan') <span class="badge badge-success">Pimpinan</span> @break
                        @case('pptk') <span class="badge badge-warning">Penanggung Jawab</span> @break
                        @case('verifier') <span class="badge badge-secondary">Verifikator</span> @break
                    @endswitch
                </div>
            </div>
            <div class="avatar-circle" title="{{ auth()->user()->name }}">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <form action="{{ route('logout') }}" method="POST" style="margin-left: 4px;">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm" title="Keluar">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </button>
            </form>
        </div>
    </div>
</header>
