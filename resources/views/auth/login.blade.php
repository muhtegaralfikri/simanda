<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIMANDA</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-box {
            background-color: #ffffff;
            width: 100%;
            max-width: 420px;
            border-radius: 12px;
            padding: 36px 32px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.3), 0 8px 10px -6px rgba(0,0,0,0.2);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-badge {
            display: inline-block;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
            padding: 8px 16px;
            border-radius: 8px;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .login-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
        }
        .login-sub {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-brand">
            <div class="login-badge">SIMANDA</div>
            <div class="login-title">Sistem Monitoring Anggaran</div>
            <div class="login-sub">Silakan masuk dengan akun instansi/unit Anda</div>
        </div>

        @include('admin.partials.flash')

        <form action="{{ route('login.post') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Alamat Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="contoh: admin@simanda.go.id">
            </div>

            <div class="form-group">
                <label class="form-label">Kata Sandi</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>

            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; margin-top: 12px;">
                <label style="font-size: 0.8rem; color: #475569; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                    <input type="checkbox" name="remember"> Ingat saya
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 10px; margin-top: 8px;">
                Masuk ke SIMANDA
            </button>
        </form>
    </div>
</body>
</html>
