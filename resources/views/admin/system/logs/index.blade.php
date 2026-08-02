@extends('admin.layouts.app')

@section('title', 'Log Aktivitas Sistem')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Log Aktivitas Sistem</h1>
        <div class="page-subtitle">Riwayat pencatatan audit trail aksi pengguna dalam sistem</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Waktu</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>Modul</th>
                    <th>Uraian Aktivitas</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td><small>{{ $log->created_at->format('d/m/Y H:i:s') }}</small></td>
                    <td>
                        <strong>{{ $log->user ? $log->user->name : 'Sistem' }}</strong>
                        @if($log->user)
                            <br><small style="color:var(--text-muted);">{{ $log->user->email }}</small>
                        @endif
                    </td>
                    <td><span class="badge badge-info">{{ $log->action }}</span></td>
                    <td><strong>{{ $log->module }}</strong></td>
                    <td>{{ $log->description }}</td>
                    <td><code>{{ $log->ip_address }}</code></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center; color: var(--text-muted);">Belum ada log aktivitas tercatat.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px;">
        {{ $logs->links() }}
    </div>
</div>
@endsection
