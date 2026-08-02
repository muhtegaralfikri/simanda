@extends('admin.layouts.app')

@section('title', 'Status Sistem SIMANDA')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Halaman Status Kesehatan & Infrastruktur Sistem</h1>
        <div class="page-subtitle">Pemeriksaan komponen teknis server VPS 2 GB RAM, database SQLite WAL, direktori privat, dan scheduler</div>
    </div>
    <div>
        <a href="{{ route('admin.system.health') }}" class="btn btn-secondary">&circular_arrows; Refresh Status</a>
    </div>
</div>

@php
    $overallColor = 'var(--success)';
    $overallText = 'Sistem Berjalan Normal & Optimal';
    if ($health['overall'] === 'warning') { $overallColor = 'var(--warning)'; $overallText = 'Perlu Perhatian (Ada Peringatan)'; }
    if ($health['overall'] === 'danger') { $overallColor = 'var(--danger)'; $overallText = 'Terdeteksi Masalah Infrastruktur!'; }
@endphp

<div class="card" style="border-left:6px solid {{ $overallColor }}; margin-bottom:24px;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:16px;">
        <div>
            <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase;">Status Kesehatan Keseluruhan</div>
            <h2 style="margin:4px 0 0 0; color:{{ $overallColor }};">{{ $overallText }}</h2>
        </div>
        <div>
            <span class="badge" style="background:{{ $overallColor }}; color:white; font-size:1rem; padding:8px 16px;">{{ strtoupper($health['overall']) }}</span>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">
    @foreach($health['checks'] as $key => $check)
    @php
        $badgeClass = 'badge-success';
        $borderCol = 'var(--success)';
        if ($check['status'] === 'warning') { $badgeClass = 'badge-warning'; $borderCol = 'var(--warning)'; }
        if ($check['status'] === 'danger') { $badgeClass = 'badge-danger'; $borderCol = 'var(--danger)'; }
    @endphp
    <div class="card" style="border-top:4px solid {{ $borderCol }};">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
            <strong style="font-size:0.95rem;">{{ $check['label'] }}</strong>
            <span class="badge {{ $badgeClass }}">{{ strtoupper($check['status']) }}</span>
        </div>
        <div style="font-size:1.1rem; font-weight:bold; color:var(--text-color); margin-bottom:6px;">
            {{ $check['value'] }}
        </div>
        <div style="font-size:0.8rem; color:var(--text-muted);">
            {{ $check['note'] }}
        </div>
    </div>
    @endforeach
</div>
@endsection
