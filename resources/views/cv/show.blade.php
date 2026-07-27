<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail CV - {{ $cv->nama }}</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 850px;
            margin: 40px auto;
            padding: 0 20px;
            background: #f8fafc;
            color: #1e293b;
        }
        a.back { color: #2563eb; text-decoration: none; font-size: 0.9rem; }
        a.back:hover { text-decoration: underline; }
        h1 { font-size: 1.6rem; margin: 8px 0 2px; }
        .muted { color: #64748b; font-size: 0.9rem; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin-top: 18px; }
        .card h2 { font-size: 1.05rem; margin: 0 0 14px; padding-bottom: 8px; border-bottom: 1px solid #eef2f7; color: #0f172a; }
        .field { margin-bottom: 12px; }
        .label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em; color: #94a3b8; font-weight: 600; }
        .value { font-size: 0.95rem; margin-top: 2px; white-space: pre-line; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 20px; }
        .grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px 20px; }
        .item { border: 1px solid #eef2f7; border-radius: 8px; padding: 12px 16px; margin-bottom: 10px; background: #fafbfc; }
        .item:last-child { margin-bottom: 0; }
        .item-title { font-weight: 600; font-size: 0.95rem; }
        .item-sub { color: #64748b; font-size: 0.85rem; margin-top: 1px; }
        .item-desc { font-size: 0.9rem; margin-top: 8px; white-space: pre-line; color: #334155; }
        .chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; border-radius: 999px; padding: 4px 12px; font-size: 0.85rem; }
        .empty { color: #94a3b8; font-size: 0.88rem; font-style: italic; }
        .topbar { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 8px; }
        .btn { display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 0.88rem; }
        .btn:hover { background: #1d4ed8; }
        .alert-success {
            background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;
            padding: 12px 16px; border-radius: 8px; margin-top: 16px; font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <a href="{{ route('cv.index') }}" class="back">&larr; Kembali ke daftar CV</a>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="topbar">
    <div>
    <h1>
        {{ $cv->gelar_depan ? $cv->gelar_depan . ' ' : '' }}{{ $cv->nama ?: 'Tanpa Nama' }}{{ $cv->gelar_belakang ? ', ' . $cv->gelar_belakang : '' }}
    </h1>
    <p class="muted">{{ $cv->posisi ?: 'Tenaga Ahli' }} &middot; {{ $cv->perusahaan ?: 'PT LAPI ITB' }} &middot; Tersimpan {{ $cv->updated_at?->format('d M Y H:i') }}</p>
    </div>
        <a href="{{ route('cv.edit', $cv) }}" class="btn">Edit CV</a>
    </div>

    {{-- ===================== BIODATA ===================== --}}
    <div class="card">
        <h2>Biodata Talent</h2>
        <div class="grid3" style="margin-bottom:12px;">
            <div class="field"><div class="label">Email (akun)</div><div class="value">{{ $cv->user?->email ?: '-' }}</div></div>
            <div class="field"><div class="label">Kewarganegaraan</div><div class="value">{{ $cv->kewarganegaraan ?: 'Indonesia' }}</div></div>
            <div class="field"><div class="label">Status Kepegawaian</div><div class="value">{{ $cv->status_kepegawaian ?: '-' }}</div></div>
        </div>
        <div class="grid3" style="margin-bottom:12px;">
            <div class="field"><div class="label">Tempat, Tgl Lahir</div><div class="value">{{ $cv->tempat_lahir ?: '-' }}{{ $cv->tanggal_lahir ? ', ' . $cv->tanggal_lahir->format('d M Y') : '' }}</div></div>
            <div class="field"><div class="label">Domisili Kota</div><div class="value">{{ $cv->domisili_kota ?: '-' }}</div></div>
            <div class="field"><div class="label">Domisili Negara</div><div class="value">{{ $cv->domisili_negara ?: 'Indonesia' }}</div></div>
        </div>
        <div class="field"><div class="label">Ringkasan Profil</div><div class="value">{{ $cv->employment_desc ?: '-' }}</div></div>
    </div>

    {{-- ===================== PENDIDIKAN FORMAL ===================== --}}
    <div class="card">
        <h2>Pendidikan Formal</h2>
        @forelse ($cv->pendidikanFormal as $p)
            <div class="item">
                <div class="item-title">{{ $p->tingkat ?: '' }}{{ $p->jurusan ? ' - ' . $p->jurusan : '' }}</div>
                <div class="item-sub">{{ $p->institusi ?: '' }}{{ $p->tahun_lulus ? ' | Lulus ' . $p->tahun_lulus : '' }}</div>
            </div>
        @empty
            <p class="empty">Tidak ada data pendidikan formal.</p>
        @endforelse
    </div>

    {{-- ===================== PELATIHAN / NON-FORMAL ===================== --}}
    <div class="card">
        <h2>Pelatihan / Pendidikan Non-Formal</h2>
        @forelse ($cv->pendidikanNonformal as $p)
            <div class="item">
                <div class="item-title">{{ $p->nama_pelatihan ?: '-' }}</div>
                <div class="item-sub">{{ $p->penyelenggara ?: '-' }}{{ $p->tahun ? ' (' . $p->tahun . ')' : '' }}</div>
            </div>
        @empty
            <p class="empty">Tidak ada data pelatihan.</p>
        @endforelse
    </div>

    {{-- ===================== PENGALAMAN KERJA ===================== --}}
    <div class="card">
        <h2>Pengalaman Kerja</h2>
        @forelse ($cv->pengalamanKerja as $p)
            <div class="item">
                <div class="item-title">{{ $p->nama_kegiatan ?: ($p->posisi ?: '-') }}</div>
                <div class="item-sub">{{ $p->pemberi_pekerjaan ?: '-' }}{{ $p->posisi ? ' | ' . $p->posisi : '' }}{{ $p->tahun ? ' | ' . $p->tahun : ($p->durasi ? ' | ' . $p->durasi : '') }}</div>
                @if ($p->uraian_tugas)
                    <div class="item-desc">{{ $p->uraian_tugas }}</div>
                @endif
            </div>
        @empty
            <p class="empty">Tidak ada data pengalaman kerja.</p>
        @endforelse
    </div>

    {{-- ===================== SERTIFIKASI ===================== --}}
    <div class="card">
        <h2>Sertifikasi Profesi</h2>
        @forelse ($cv->sertifikasiProfesi as $s)
            <div class="item">
                <div class="item-title">{{ $s->nama ?: '-' }}</div>
                <div class="item-sub">{{ $s->penerbit ?: '-' }}{{ $s->tahun ? ' (' . $s->tahun . ')' : '' }}</div>
                @if ($s->file_sertifikat)
                    <div class="item-sub"><a href="{{ route('cv.sertifikat-file', $s) }}" target="_blank">Lihat file sertifikat &nearr;</a></div>
                @endif
            </div>
        @empty
            <p class="empty">Tidak ada data sertifikasi.</p>
        @endforelse
    </div>

    {{-- ===================== PROYEK RELEVAN ===================== --}}
    <div class="card">
        <h2>Proyek Relevan</h2>
        @forelse ($cv->projectRelevan as $p)
            <div class="item">
                <div class="item-title">{{ $p->nama_project ?: '-' }}</div>
                <div class="item-sub">Klien: {{ $p->klien ?: '-' }}{{ $p->tahun ? ' | Tahun ' . $p->tahun : '' }}</div>
            </div>
        @empty
            <p class="empty">Tidak ada data proyek.</p>
        @endforelse
    </div>

    {{-- ===================== BAHASA ===================== --}}
    <div class="card">
        <h2>Kemampuan Bahasa</h2>
        @forelse ($cv->bahasa as $b)
            <div class="item">
                <div class="item-title">{{ $b->bahasa ?: '-' }}</div>
                <div class="item-sub">Speaking: {{ $b->speaking ?: 'Baik' }} | Reading: {{ $b->reading ?: 'Baik' }} | Writing: {{ $b->writing ?: 'Baik' }}</div>
            </div>
        @empty
            <p class="empty">Tidak ada data bahasa.</p>
        @endforelse
    </div>

    {{-- ===================== SKILLS ===================== --}}
    <div class="card">
        <h2>Keahlian (Skills)</h2>
        @if ($cv->cvKeahlian->isNotEmpty())
            <div class="chips">
                @foreach ($cv->cvKeahlian as $k)
                    <span class="chip">{{ $k->nama_keahlian }}</span>
                @endforeach
            </div>
        @else
            <p class="empty">Tidak ada data keahlian.</p>
        @endif
    </div>

    {{-- ===================== LAMPIRAN ===================== --}}
    @php
        $jenisLampiran = ['ktp' => 'KTP', 'npwp' => 'NPWP', 'bukti_pajak' => 'Bukti Pajak', 'foto' => 'Foto'];
        $lampiran = $cv->lampiran;
        $kolom = ['ktp' => 'ktp_file', 'npwp' => 'npwp_file', 'bukti_pajak' => 'bukti_pajak', 'foto' => 'foto'];
        $ijazahList = $lampiran?->lainnya['ijazah'] ?? [];
    @endphp
    <div class="card">
        <h2>Lampiran Dokumen</h2>
        <div class="grid3">
            @foreach ($jenisLampiran as $key => $label)
                @php $ada = $lampiran?->{$kolom[$key]}; @endphp
                <div class="field">
                    <div class="label">{{ $label }}</div>
                    <div class="value">
                        @if ($ada)
                            <a href="{{ route('cv.lampiran', [$cv, $key]) }}" target="_blank">Lihat / unduh file &nearr;</a>
                        @else
                            <span class="empty">Belum ada</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid2" style="margin-top:6px;">
            <div class="field">
                <div class="label">Ijazah</div>
                <div class="value">
                    @forelse ($ijazahList as $i => $path)
                        <div><a href="{{ route('cv.lampiran-multi', [$cv, 'ijazah', $i]) }}" target="_blank">Ijazah #{{ $i + 1 }} &nearr;</a></div>
                    @empty
                        <span class="empty">Belum ada</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</body>
</html>