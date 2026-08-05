<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Resume Parser SISTA</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #f1f5f9; color: #1e293b; margin: 0;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .card {
            background: #fff; width: 100%; max-width: 400px;
            border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px 28px;
            box-shadow: 0 6px 24px rgba(15,23,42,.06);
        }
        h1 { font-size: 1.35rem; margin: 0 0 2px; color: #0f172a; }
        .muted { color: #64748b; font-size: 0.88rem; margin: 0 0 22px; }
        label { display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin: 14px 0 5px; }
        input[type="email"], input[type="password"] {
            width: 100%; border: 1px solid #cbd5e1; border-radius: 8px;
            padding: 10px 12px; font-size: 0.95rem; font-family: inherit; background: #fff; color: #1e293b;
        }
        input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px #dbeafe; }
        .remember { display: flex; align-items: center; gap: 7px; margin-top: 14px; font-size: 0.86rem; color: #475569; }
        .remember input { width: auto; }
        .btn {
            width: 100%; margin-top: 22px; background: #2563eb; color: #fff; border: none; cursor: pointer;
            padding: 11px; border-radius: 8px; font-size: 0.95rem; font-weight: 600;
        }
        .btn:hover { background: #1d4ed8; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 12px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; }
        .err ul { margin: 4px 0 0; padding-left: 18px; }
        .status { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 10px 12px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; }
        .logo { font-weight: 700; color: #2563eb; font-size: 0.8rem; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Resume Parser · SISTA</div>
        <h1>Masuk</h1>
        <p class="muted">Silakan login untuk mengelola CV.</p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="err">
                Login gagal:
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@resume-sista.com">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">

            <label class="remember">
                <input type="checkbox" name="remember"> Ingat saya
            </label>

            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>
</html>
