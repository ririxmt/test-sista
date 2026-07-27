<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload CV</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 640px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f8fafc;
            color: #1e293b;
        }
        h1 { font-size: 1.5rem; }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 24px;
            margin-top: 20px;
        }
        input[type="file"] {
            display: block;
            margin: 12px 0;
        }
        button {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 0.95rem;
            cursor: pointer;
        }
        button:hover { background: #1d4ed8; }
        .alert-success {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        pre {
            background: #0f172a;
            color: #e2e8f0;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.85rem;
        }
        .muted { color: #64748b; font-size: 0.9rem; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
        .btn-outline {
            display: inline-block; background: #fff; color: #2563eb; border: 1px solid #bfdbfe;
            padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; white-space: nowrap;
        }
        .btn-outline:hover { background: #eff6ff; }
    </style>
</head>
<body>

    <div class="topbar">
        <div>
            <h1>Upload CV</h1>
            <p class="muted">File akan diproses AI dan hasil ekstraksinya disimpan sebagai data staging (belum masuk ke profil final).</p>
        </div>
        <a href="{{ route('cv.index') }}" class="btn-outline">Lihat Saved CV List &rarr;</a>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        <form action="{{ route('applicants.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="resume">Pilih file CV (PDF/JPG/PNG, maks 4MB)</label>
            <input type="file" name="resume" id="resume" accept=".pdf,.jpg,.jpeg,.png" required>
            <button type="submit">Upload & Proses</button>
        </form>
    </div>

    @if (session('parsed_data'))
        <div class="card">
            <h2 style="font-size:1.1rem;">Hasil Ekstraksi AI</h2>
            <pre>{{ json_encode(session('parsed_data'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif

</body>
</html>