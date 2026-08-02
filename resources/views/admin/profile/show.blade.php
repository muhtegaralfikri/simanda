@extends('admin.layouts.app')

@section('title', 'Profil Pengguna')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Profil Pengguna</h1>
        <div class="page-subtitle">Kelola informasi akun dan kata sandi Anda</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Informasi Akun</h2>
        </div>
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="form-group">
                <label class="form-label">Alamat Email (Tetap)</label>
                <input type="email" class="form-control" value="{{ $user->email }}" disabled style="background-color: #f1f5f9;">
            </div>

            <div class="form-group">
                <label class="form-label">Nomor Telepon / WhatsApp</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
            </div>

            <div class="form-group">
                <label class="form-label">Peran Sistem</label>
                <input type="text" class="form-control" value="{{ strtoupper($user->role) }}" disabled style="background-color: #f1f5f9;">
            </div>

            <div class="form-group">
                <label class="form-label">Unit Kerja</label>
                <input type="text" class="form-control" value="{{ $user->unit ? $user->unit->name : 'Seluruh Unit (Administrator/Pimpinan)' }}" disabled style="background-color: #f1f5f9;">
            </div>

            <button type="submit" class="btn btn-primary">Simpan Perubahan Profil</button>
        </form>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Ubah Kata Sandi</h2>
        </div>
        <form action="{{ route('profile.password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Kata Sandi Saat Ini</label>
                <input type="password" name="current_password" class="form-control" required placeholder="••••••••">
            </div>

            <div class="form-group">
                <label class="form-label">Kata Sandi Baru</label>
                <input type="password" name="password" class="form-control" required placeholder="Minimal 8 karakter">
            </div>

            <div class="form-group">
                <label class="form-label">Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="password_confirmation" class="form-control" required placeholder="Ulangi kata sandi baru">
            </div>

            <button type="submit" class="btn btn-secondary">Perbarui Kata Sandi</button>
        </form>
    </div>
</div>
@endsection
