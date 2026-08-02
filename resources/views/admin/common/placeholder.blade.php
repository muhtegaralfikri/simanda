@extends('admin.layouts.app')

@section('title', $title)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        <div class="page-subtitle">Modul Tahapan Pengerjaan Selanjutnya</div>
    </div>
</div>

<div class="card" style="text-align: center; padding: 48px 24px;">
    <div style="font-size: 3rem; margin-bottom: 16px; color: var(--accent-color);">🚀</div>
    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 8px;">Fondasi Tahap 1 Selesai Digarap!</h2>
    <p style="font-size: 0.875rem; color: var(--text-muted); max-width: 500px; margin: 0 auto 20px auto;">
        Modul <strong>{{ $title }}</strong> telah disiapkan dalam struktur routing & controller, dan akan dikembangkan penuh sesuai alur tahapan pengembangan berikutnya.
    </p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary">Kembali ke Dashboard Overview</a>
</div>
@endsection
