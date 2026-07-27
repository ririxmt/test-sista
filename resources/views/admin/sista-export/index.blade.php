<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Export untuk SISTA</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 20px; background: #f8fafc; color: #1e293b; }
        h1 { font-size: 1.5rem; margin-bottom: 4px; }
        h2 { font-size: 1.05rem; margin: 24px 0 10px; }
        .muted { color: #64748b; font-size: 0.9rem; }
        a.back { color: #2563eb; text-decoration: none; font-size: 0.9rem; }
        a.back:hover { text-decoration: underline; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin-top: 16px; }
        .big { font-size: 2.2rem; font-weight: 700; color: #0f172a; }
        .btn { display: inline-block; background: #2563eb; color: #fff; border: none; cursor: pointer; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 0.95rem; font-weight: 500; }
        .btn:hover { background: #1d4ed8; }
        .btn:disabled { background: #cbd5e1; cursor: not-allowed; }
        .btn-outline { background: #fff; color: #2563eb; border: 1px solid #bfdbfe; }
        .btn-outline:hover { background: #eff6ff; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .pager { display: flex; align-items: center; gap: 6px; margin: 14px 4px 0; justify-content: flex-end; flex-wrap: wrap; }
        .pager a, .pager span { display: inline-block; min-width: 32px; text-align: center; padding: 5px 9px; border-radius: 6px; font-size: 0.82rem; text-decoration: none; border: 1px solid #e2e8f0; background: #fff; color: #334155; }
        .pager a:hover { background: #f1f5f9; }
        .pager .current { background: #2563eb; color: #fff; border-color: #2563eb; }
        .pager .disabled { color: #cbd5e1; background: #f8fafc; }
        .pager .info { border: none; background: none; color: #64748b; margin-right: auto; padding-left: 0; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-top: 16px; font-size: 0.9rem; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 9px 14px; font-size: 0.88rem; border-bottom: 1px solid #f1f5f9; }
        th { color: #64748b; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; }
        .tag { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 0.75rem; }
        .tag-new { background: #ecfdf5; color: #065f46; }
        .tag-changed { background: #fef3c7; color: #92400e; }
        .tag-success { background: #ecfdf5; color: #065f46; }
        .tag-failed { background: #fef2f2; color: #991b1b; }
        .tag-processing { background: #eff6ff; color: #1e40af; }
        .empty { color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>
    <div class="topbar">
        <div>
            <h1>Export untuk SISTA</h1>
            <p class="muted">Kemas data CV yang sudah approved menjadi paket ZIP untuk diserahkan ke pengelola SISTA.</p>
        </div>
        <a href="{{ route('cv.index') }}" class="btn btn-outline">Ke Daftar CV &rarr;</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif
    @if (session('error'))
        <div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">{{ session('error') }}</div>
    @endif

    {{-- ===== Ringkasan + tombol export ===== --}}
    <div class="card">
        <div class="muted">CV siap diexport (approved, belum/berubah setelah export)</div>
        <div class="big">{{ $count }}</div>
        <form method="POST" action="{{ route('sista-export.export') }}" style="margin-top:8px;"
              onsubmit="return confirm('Buat paket ZIP untuk {{ $count }} CV?');">
            @csrf
            <button type="submit" class="btn" @if($count === 0) disabled @endif>Export untuk SISTA &rarr;</button>
        </form>
    </div>

    {{-- ===== Daftar kandidat ===== --}}
    <h2>Daftar CV yang akan diexport ({{ $count }})</h2>
    <div class="card" style="padding:8px 0;">
        <table>
            <thead>
                <tr><th>Nama</th><th>Email</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td>{{ $r->parsed_name ?: '—' }}</td>
                        <td>{{ $r->parsed_email ?: '—' }}</td>
                        <td>
                            @if ($r->sista_export_status === 'changed_after_export')
                                <span class="tag tag-changed">berubah setelah export</span>
                            @else
                                <span class="tag tag-new">belum diexport</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="empty" style="padding:20px 14px;text-align:center;">Tidak ada CV yang perlu diexport.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($rows->total() > 0)
        <div class="pager">
            <span class="info">Menampilkan {{ $rows->firstItem() }}–{{ $rows->lastItem() }} dari {{ $rows->total() }}</span>
            @if ($rows->onFirstPage())
                <span class="disabled">&laquo;</span>
            @else
                <a href="{{ $rows->previousPageUrl() }}">&laquo;</a>
            @endif
            @foreach ($rows->getUrlRange(1, $rows->lastPage()) as $page => $url)
                @if ($page == $rows->currentPage())
                    <span class="current">{{ $page }}</span>
                @else
                    <a href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
            @if ($rows->hasMorePages())
                <a href="{{ $rows->nextPageUrl() }}">&raquo;</a>
            @else
                <span class="disabled">&raquo;</span>
            @endif
        </div>
    @endif

    {{-- ===== Riwayat batch ===== --}}
    <h2>Riwayat Export</h2>
    <div class="card" style="padding:8px 0;">
        <table>
            <thead>
                <tr><th>Batch</th><th>Waktu</th><th>CV</th><th>File</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse ($batches as $b)
                    <tr>
                        <td>{{ $b->batch_code }}</td>
                        <td>{{ $b->exported_at?->format('d M Y H:i') ?: '—' }}</td>
                        <td>{{ $b->total_cv }}</td>
                        <td>{{ $b->total_files }}</td>
                        <td><span class="tag tag-{{ $b->status }}">{{ $b->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty" style="padding:20px 14px;text-align:center;">Belum ada riwayat export.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
