@extends('admin.layouts.app')

@section('title', 'Pusat Laporan & Pelaporan SIMANDA')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pusat Laporan & Ekspor Data</h1>
        <div class="page-subtitle">Pilih jenis laporan untuk melihat rekapitulasi, mencetak, atau mengunduh format PDF & Excel</div>
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:20px;">
    <!-- 1. Ringkasan Anggaran -->
    <div class="card" style="border-top:4px solid var(--accent-color);">
        <div class="card-header">
            <h3 class="card-title">1. Ringkasan Anggaran</h3>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
            Rekapitulasi pagu kegiatan, alokasi RAB, realisasi aktif, realisasi terverifikasi, dan sisa anggaran final per unit/kegiatan.
        </p>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.reports.budget.index') }}" class="btn btn-primary btn-sm">Tampilkan</a>
            <a href="{{ route('admin.reports.budget.pdf') }}" target="_blank" class="btn btn-secondary btn-sm">PDF</a>
            <a href="{{ route('admin.reports.budget.excel') }}" class="btn btn-secondary btn-sm">Excel</a>
        </div>
    </div>

    <!-- 2. Realisasi Anggaran -->
    <div class="card" style="border-top:4px solid var(--info);">
        <div class="card-header">
            <h3 class="card-title">2. Realisasi Anggaran</h3>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
            Daftar rincian transaksi kuitansi pengeluaran, penerima dana, potongan pajak, nilai bersih, dan status verifikasi.
        </p>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.reports.realization.index') }}" class="btn btn-primary btn-sm">Tampilkan</a>
            <a href="{{ route('admin.reports.realization.pdf') }}" target="_blank" class="btn btn-secondary btn-sm">PDF</a>
            <a href="{{ route('admin.reports.realization.excel') }}" class="btn btn-secondary btn-sm">Excel</a>
        </div>
    </div>

    <!-- 3. Pelaksanaan Kegiatan -->
    <div class="card" style="border-top:4px solid var(--success);">
        <div class="card-header">
            <h3 class="card-title">3. Pelaksanaan Kegiatan</h3>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
            Daftar seluruh kegiatan beserta unit kerja, PPTK penanggung jawab, jadwal pelaksanaan, pagu, dan progres fisik.
        </p>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.reports.activity.index') }}" class="btn btn-primary btn-sm">Tampilkan</a>
            <a href="{{ route('admin.reports.activity.pdf') }}" target="_blank" class="btn btn-secondary btn-sm">PDF</a>
            <a href="{{ route('admin.reports.activity.excel') }}" class="btn btn-secondary btn-sm">Excel</a>
        </div>
    </div>

    <!-- 4. Progres Kegiatan -->
    <div class="card" style="border-top:4px solid #8b5cf6;">
        <div class="card-header">
            <h3 class="card-title">4. Progres & Capaian Fisik</h3>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
            Laporan persentase progres fisik kegiatan, catatan capaian terakhir, dan riwayat perubahannya.
        </p>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.reports.progress.index') }}" class="btn btn-primary btn-sm">Tampilkan</a>
            <a href="{{ route('admin.reports.progress.pdf') }}" target="_blank" class="btn btn-secondary btn-sm">PDF</a>
            <a href="{{ route('admin.reports.progress.excel') }}" class="btn btn-secondary btn-sm">Excel</a>
        </div>
    </div>

    <!-- 5. Kelengkapan Dokumen -->
    <div class="card" style="border-top:4px solid var(--warning);">
        <div class="card-header">
            <h3 class="card-title">5. Kelengkapan Dokumen</h3>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
            Status kelengkapan berkas fisik/digital wajib per kegiatan, jumlah dokumen terunggah, dan persentase keabsahan valid.
        </p>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.reports.documents.index') }}" class="btn btn-primary btn-sm">Tampilkan</a>
            <a href="{{ route('admin.reports.documents.pdf') }}" target="_blank" class="btn btn-secondary btn-sm">PDF</a>
            <a href="{{ route('admin.reports.documents.excel') }}" class="btn btn-secondary btn-sm">Excel</a>
        </div>
    </div>

    <!-- 6. Riwayat Verifikasi -->
    <div class="card" style="border-top:4px solid #ec4899;">
        <div class="card-header">
            <h3 class="card-title">6. Riwayat Verifikasi</h3>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
            Log audit keputusan verifikator terhadap pengajuan kegiatan, transaksi realisasi, dan keabsahan dokumen berkas.
        </p>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.reports.verifications.index') }}" class="btn btn-primary btn-sm">Tampilkan</a>
            <a href="{{ route('admin.reports.verifications.pdf') }}" target="_blank" class="btn btn-secondary btn-sm">PDF</a>
            <a href="{{ route('admin.reports.verifications.excel') }}" class="btn btn-secondary btn-sm">Excel</a>
        </div>
    </div>

    <!-- 7. Serapan Bulanan -->
    <div class="card" style="border-top:4px solid #14b8a6;">
        <div class="card-header">
            <h3 class="card-title">7. Serapan Bulanan</h3>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
            Grafik dan tabel serapan realisasi terverifikasi dari Januari hingga Desember beserta serapan kumulatif.
        </p>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.reports.absorption.index') }}" class="btn btn-primary btn-sm">Tampilkan</a>
            <a href="{{ route('admin.reports.absorption.pdf') }}" target="_blank" class="btn btn-secondary btn-sm">PDF</a>
            <a href="{{ route('admin.reports.absorption.excel') }}" class="btn btn-secondary btn-sm">Excel</a>
        </div>
    </div>

    <!-- 8. Sisa Anggaran -->
    <div class="card" style="border-top:4px solid #f43f5e;">
        <div class="card-header">
            <h3 class="card-title">8. Sisa Anggaran & EFISIENSI</h3>
        </div>
        <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:16px;">
            Laporan sisa anggaran final per kegiatan beserta alasan/catatan sisa dana efisiensi pada penutupan kegiatan.
        </p>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.reports.remaining-budget.index') }}" class="btn btn-primary btn-sm">Tampilkan</a>
            <a href="{{ route('admin.reports.remaining-budget.pdf') }}" target="_blank" class="btn btn-secondary btn-sm">PDF</a>
            <a href="{{ route('admin.reports.remaining-budget.excel') }}" class="btn btn-secondary btn-sm">Excel</a>
        </div>
    </div>
</div>
@endsection
