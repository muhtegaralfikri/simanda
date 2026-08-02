@extends('admin.layouts.app')

@section('title', 'Laporan Serapan Bulanan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Serapan Anggaran Bulanan</h1>
        <div class="page-subtitle">Rekapitulasi serapan realisasi terverifikasi dari Januari hingga Desember</div>
    </div>
    <div style="display:flex; gap:8px;">
        <button onclick="window.print()" class="btn btn-secondary">&printer; Cetak Browser</button>
        <a href="{{ route('admin.reports.absorption.pdf', request()->query()) }}" target="_blank" class="btn btn-primary">PDF</a>
        <a href="{{ route('admin.reports.absorption.excel', request()->query()) }}" class="btn btn-success" style="background:var(--success); color:white;">Excel</a>
    </div>
</div>

<div class="stats-grid" style="margin-bottom:20px;">
    <div class="stat-card" style="border-left:4px solid var(--accent-color);">
        <div class="stat-label">Total Pagu</div>
        <div class="stat-value">Rp {{ number_format($data['total_pagu'], 0, ',', '.') }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--success);">
        <div class="stat-label">Realisasi Verified Kumulatif</div>
        <div class="stat-value" style="color:var(--success);">Rp {{ number_format($data['total_verified'], 0, ',', '.') }}</div>
    </div>
    <div class="stat-card" style="border-left:4px solid var(--info);">
        <div class="stat-label">Persentase Serapan Final</div>
        <div class="stat-value" style="color:var(--info);">{{ $data['absorption_percentage'] }}%</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th style="text-align:right;">Realisasi Verified Bulan Ini (Rp)</th>
                    <th style="text-align:right;">Realisasi Verified Kumulatif (Rp)</th>
                    <th style="text-align:right;">Total Pagu (Rp)</th>
                    <th style="text-align:center;">Serapan Kumulatif (%)</th>
                    <th style="text-align:right;">Sisa Anggaran (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['rows'] as $r)
                <tr>
                    <td><strong>{{ $r['month_name'] }}</strong></td>
                    <td style="text-align:right;">{{ number_format($r['monthly_verified'], 0, ',', '.') }}</td>
                    <td style="text-align:right; color:var(--success);"><strong>{{ number_format($r['cumulative_verified'], 0, ',', '.') }}</strong></td>
                    <td style="text-align:right;">{{ number_format($r['total_pagu'], 0, ',', '.') }}</td>
                    <td style="text-align:center;"><strong>{{ $r['cumulative_absorption_percentage'] }}%</strong></td>
                    <td style="text-align:right; color:var(--info);">{{ number_format($r['remaining_budget'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
