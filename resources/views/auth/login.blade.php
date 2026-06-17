<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Helpdesk IT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #1a56db 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,.3);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #0f172a, #1a56db);
            padding: 2.5rem 2rem;
            text-align: center;
            color: #fff;
        }
        .login-header .icon-wrap {
            width: 72px; height: 72px;
            background: rgba(255,255,255,.15);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.2rem; margin: 0 auto 1rem;
            backdrop-filter: blur(10px);
        }
        .login-header h4 { font-weight: 800; margin: 0; }
        .login-header p { opacity: .75; font-size: .9rem; margin: .4rem 0 0; }
        .login-body { padding: 2rem; }
        .form-control:focus { border-color: #1a56db; box-shadow: 0 0 0 .2rem rgba(26,86,219,.15); }
        .btn-login {
            background: linear-gradient(135deg, #1a56db, #0ea5e9);
            border: none; color: #fff;
            padding: .7rem; font-weight: 600;
            border-radius: 10px;
            transition: opacity .2s;
        }
        .btn-login:hover { opacity: .9; color: #fff; }
        .input-group-text { background: #f8fafc; border-right: none; }
        .form-control { border-left: none; }
        .form-control:not(:first-child) { border-left: none; }
        .input-group .form-control { border-left: none; }
        .alert-danger { border-radius: 10px; font-size: .875rem; }
        .demo-accounts { background: #f8fafc; border-radius: 10px; padding: 1rem; font-size: .78rem; }
        .demo-accounts table td { padding: .2rem .5rem; }
    </style>
</head>
<body>
<div class="px-3 w-100" style="max-width: 440px; margin: auto;">
    <div class="login-card">
        <div class="login-header">
            <div class="icon-wrap"><i class="bi bi-headset"></i></div>
            <h4>Helpdesk IT</h4>
            <p>Sistem Manajemen Tiket Layanan IT</p>
        </div>
        <div class="login-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle-fill me-1"></i>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope text-muted"></i></span>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="email@perusahaan.com" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="••••••••" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePwd" tabindex="-1">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4 d-flex align-items-center justify-content-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label small" for="remember">Ingat saya</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>

            <hr class="my-3">
            <div class="demo-accounts">
                <div class="text-muted fw-semibold mb-2"><i class="bi bi-info-circle me-1"></i>Akun Demo</div>
                <table class="w-100">
                    <tr><td class="fw-semibold text-primary">Supervisor</td><td>supervisor@helpdesk.com</td></tr>
                    <tr><td class="fw-semibold text-success">Teknisi</td><td>teknisi1@helpdesk.com</td></tr>
                    <tr><td class="fw-semibold text-secondary">User</td><td>user1@helpdesk.com</td></tr>
                    <tr><td colspan="2" class="text-muted">Password: <strong>password123</strong></td></tr>
                </table>
            </div>
        </div>
    </div>
    <p class="text-center text-white-50 mt-3 small">&copy; {{ date('Y') }} Helpdesk IT System</p>
</div>

<script>
    document.getElementById('togglePwd')?.addEventListener('click', function() {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.className = 'bi bi-eye-slash';
        } else {
            pwd.type = 'password';
            icon.className = 'bi bi-eye';
        }
    });
</script>
</body>
</html>
