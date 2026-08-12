<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar CV Tersimpan</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f8fafc;
            color: #1e293b;
        }
        h1 { font-size: 1.5rem; margin-bottom: 4px; }
        .muted { color: #64748b; font-size: 0.9rem; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .alert-success {
            background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
            padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;
        }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 12px 20px; font-size: 0.92rem; }
        th { color: #64748b; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid #e2e8f0; }
        tbody tr { border-bottom: 1px solid #f1f5f9; }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #f9fafb; }
        .btn {
            display: inline-block; background: #2563eb; color: #fff; text-decoration: none;
            padding: 6px 14px; border-radius: 6px; font-size: 0.85rem;
        }
        .btn:hover { background: #1d4ed8; }
        .btn-outline {
            background: #fff; color: #2563eb; border: 1px solid #bfdbfe;
        }
        .btn-outline:hover { background: #eff6ff; }
        .btn-sm { padding: 5px 12px; font-size: 0.82rem; }
        .btn-edit { background: #fff; color: #334155; border: 1px solid #cbd5e1; }
        .btn-edit:hover { background: #f1f5f9; }
        .btn-danger { background: #fff; color: #dc2626; border: 1px solid #fecaca; cursor: pointer; font: inherit; }
        .btn-danger:hover { background: #fef2f2; }
        .row-actions { display: flex; gap: 6px; justify-content: flex-end; align-items: center; }
        .row-actions form { margin: 0; }
        .empty { padding: 40px 20px; text-align: center; color: #94a3b8; }
        .pager { display: flex; align-items: center; gap: 6px; margin-top: 18px; justify-content: flex-end; }
        .pager a, .pager span {
            display: inline-block; min-width: 34px; text-align: center;
            padding: 6px 10px; border-radius: 6px; font-size: 0.85rem; text-decoration: none;
            border: 1px solid #e2e8f0; background: #fff; color: #334155;
        }
        .pager a:hover { background: #f1f5f9; }
        .pager .current { background: #2563eb; color: #fff; border-color: #2563eb; }
        .pager .disabled { color: #cbd5e1; background: #f8fafc; cursor: default; }
        .pager .info { border: none; background: none; color: #64748b; margin-right: auto; padding-left: 0; }
        .searchbar { display: flex; gap: 8px; align-items: center; margin-bottom: 14px; }
        .searchbar input[type="search"] {
            flex: 1; padding: 9px 12px; font-size: 0.9rem; font-family: inherit; color: #1e293b;
            border: 1px solid #e2e8f0; border-radius: 8px; background: #fff;
        }
        .searchbar input[type="search"]:focus {
            outline: none; border-color: #93c5fd; box-shadow: 0 0 0 3px #dbeafe;
        }
        .searchbar button { font: inherit; cursor: pointer; }
        .search-info { color: #64748b; font-size: 0.88rem; margin: -6px 0 14px; }
        .search-info strong { color: #1e293b; }
    </style>
</head>
<body>

    <div class="topbar">
        <div>
            <h1>Daftar CV Tersimpan</h1>
            <p class="muted">Data final yang sudah masuk ke database.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="{{ route('sista-export.index') }}" class="btn btn-outline">Export ke SISTA</a>
            <a href="{{ route('applicants.upload.form') }}" class="btn btn-outline">+ Upload CV</a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-outline" style="cursor:pointer;color:#dc2626;border-color:#fecaca;">Logout</button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('cv.index') }}" class="searchbar">
        <input type="search" name="q" value="{{ $q }}"
               placeholder="Cari berdasarkan nama atau email…" aria-label="Cari CV">
        <button type="submit" class="btn btn-sm">Cari</button>
        @if ($q !== '')
            <a href="{{ route('cv.index') }}" class="btn btn-sm btn-edit">Reset</a>
        @endif
    </form>

    @if ($q !== '')
        <p class="search-info">
            {{ $cvs->total() }} hasil untuk <strong>"{{ $q }}"</strong>
        </p>
    @endif

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Gelar Depan</th>
                    <th>Nama</th>
                    <th>Gelar Belakang</th>
                    <th>Email</th>
                    <th>Tanggal Simpan</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cvs as $cv)
                    <tr>
                        <td>{{ $cv->gelar_depan ?: '—' }}</td>
                        <td>{{ $cv->nama ?: '—' }}</td>
                        <td>{{ $cv->gelar_belakang ?: '—' }}</td>
                        <td>{{ $cv->user?->email ?: '—' }}</td>
                        <td>{{ $cv->updated_at?->format('d M Y H:i') }}</td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('cv.show', $cv) }}" class="btn btn-sm">Detail</a>
                                <a href="{{ route('cv.edit', $cv) }}" class="btn btn-sm btn-edit">Edit</a>
                                <form method="POST" action="{{ route('cv.destroy', $cv) }}"
                                      onsubmit="return confirm('Hapus CV {{ addslashes($cv->nama ?: 'ini') }}? Data pendidikan, pengalaman, dan lainnya ikut terhapus permanen.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">
                            @if ($q !== '')
                                Tidak ada CV yang cocok dengan "{{ $q }}".
                            @else
                                Belum ada CV tersimpan. Silakan upload CV terlebih dahulu.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($cvs->total() > 0)
        <div class="pager">
            <span class="info">
                Menampilkan {{ $cvs->firstItem() }}–{{ $cvs->lastItem() }} dari {{ $cvs->total() }} CV
            </span>

            @if ($cvs->onFirstPage())
                <span class="disabled">&laquo;</span>
            @else
                <a href="{{ $cvs->previousPageUrl() }}">&laquo;</a>
            @endif

            @foreach ($cvs->getUrlRange(1, $cvs->lastPage()) as $page => $url)
                @if ($page == $cvs->currentPage())
                    <span class="current">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($cvs->hasMorePages())
                <a href="{{ $cvs->nextPageUrl() }}">&raquo;</a>
            @else
                <span class="disabled">&raquo;</span>
            @endif
        </div>
    @endif

</body>
</html>
