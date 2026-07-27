@php
    $d = $staging->raw_parsed_json ?? [];
    $pendidikan = $d['pendidikan'] ?? [];
    $pelatihan = $d['pelatihan'] ?? [];
    $pengalaman = $d['pengalaman_kerja'] ?? [];
    $sertifikasi = $d['sertifikasi'] ?? [];
    $proyek = $d['proyek'] ?? [];
    $skills = $d['skills'] ?? [];
    $bahasa = $d['bahasa'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Periksa Hasil CV #{{ $staging->id }}</title>
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
        h2 { font-size: 1.1rem; margin: 0; }
        .muted { color: #64748b; font-size: 0.9rem; }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px 24px;
            margin-top: 18px;
        }
        .card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        label { display: block; font-size: 0.82rem; font-weight: 600; color: #475569; margin: 10px 0 4px; }
        input[type="text"], input[type="date"], select, textarea {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 0.92rem;
            font-family: inherit;
            background: #fff;
            color: #1e293b;
        }
        textarea { min-height: 64px; resize: vertical; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
        .grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0 16px; }
        .grid4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0 16px; }
        .row {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px 16px;
            margin-bottom: 12px;
            background: #f9fafb;
            position: relative;
        }
        .btn {
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-add { background: #e0f2fe; color: #0369a1; padding: 6px 12px; font-size: 0.85rem; }
        .btn-add:hover { background: #bae6fd; }
        .btn-remove { background: #fee2e2; color: #b91c1c; padding: 4px 10px; font-size: 0.8rem; }
        .btn-remove:hover { background: #fecaca; }
        .skill-row { display: flex; gap: 8px; margin-bottom: 8px; }
        .skill-row input { flex: 1; }
        .actions { margin-top: 24px; display: flex; gap: 12px; align-items: center; }
        .empty { color: #94a3b8; font-size: 0.88rem; font-style: italic; }
    </style>
</head>
<body>
    <h1>Periksa Hasil Ekstraksi CV</h1>
    <p class="muted">Staging #{{ $staging->id }} &middot; {{ $staging->source_file_original_name }} &middot; diproses {{ $staging->created_at?->format('d M Y H:i') }}</p>
    <p class="muted">Koreksi bila ada yang salah baca, lalu klik <strong>Simpan ke Database</strong>.</p>

    @if ($errors->any())
        <div class="card" style="border-color:#fecaca;background:#fef2f2;color:#991b1b;">{{ $errors->first() }}</div>
    @endif

    <form action="{{ route('cv-staging.update', $staging) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- ===================== BIODATA ===================== --}}
        <div class="card">
            <div class="card-head"><h2>Biodata Talent</h2></div>
            <div class="grid3">
                <div>
                    <label>Gelar Depan</label>
                    <input type="text" name="gelar_depan" value="{{ old('gelar_depan', $d['gelar_depan'] ?? '') }}">
                </div>
                <div>
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" value="{{ old('nama', $d['nama'] ?? '') }}" required>
                </div>
                <div>
                    <label>Gelar Belakang</label>
                    <input type="text" name="gelar_belakang" value="{{ old('gelar_belakang', $d['gelar_belakang'] ?? '') }}">
                </div>
            </div>

            <div class="grid2">
                <div>
                    <label>Email</label>
                    <input type="text" name="email" value="{{ old('email', $d['email'] ?? '') }}">
                </div>
                <div>
                    <label>Telepon</label>
                    <input type="text" name="telepon" value="{{ old('telepon', $d['telepon'] ?? '') }}">
                </div>
                <div>
                    <label>Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $d['tempat_lahir'] ?? '') }}">
                </div>
                <div>
                    <label>Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $d['tanggal_lahir'] ?? '') }}">
                </div>
                <div>
                    <label>Posisi / Keahlian Utama</label>
                    <input type="text" name="posisi" value="{{ old('posisi', $d['posisi'] ?? '') }}" placeholder="Tenaga Ahli Sipil / Dosen">
                </div>
                <div>
                    <label>Perusahaan / Institusi</label>
                    <input type="text" name="perusahaan" value="{{ old('perusahaan', $d['perusahaan'] ?? '') }}" placeholder="ITB / PT LAPI ITB">
                </div>
                <div>
                    <label>Domisili Kota</label>
                    <input type="text" name="domisili_kota" value="{{ old('domisili_kota', $d['domisili_kota'] ?? '') }}">
                </div>
                <div>
                    <label>Domisili Negara</label>
                    <input type="text" name="domisili_negara" value="{{ old('domisili_negara', $d['domisili_negara'] ?? 'Indonesia') }}">
                </div>
            </div>

            <label>Alamat Lengkap</label>
            <input type="text" name="alamat" value="{{ old('alamat', $d['alamat'] ?? '') }}">

            <label>Ringkasan Profil</label>
            <textarea name="ringkasan_profil">{{ old('ringkasan_profil', $d['ringkasan_profil'] ?? '') }}</textarea>
        </div>

        {{-- ===================== PENDIDIKAN FORMAL ===================== --}}
        <div class="card">
            <div class="card-head">
                <h2>Pendidikan Formal</h2>
                <button type="button" class="btn btn-add" data-add="pendidikan">+ Tambah</button>
            </div>
            <div data-list="pendidikan">
                @foreach ($pendidikan as $i => $p)
                    <div class="row">
                        <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
                        <div class="grid4">
                            <div><label>Jenjang</label><input type="text" name="pendidikan[{{ $i }}][jenjang]" value="{{ $p['jenjang'] ?? '' }}" placeholder="S1/S2/S3"></div>
                            <div><label>Institusi</label><input type="text" name="pendidikan[{{ $i }}][institusi]" value="{{ $p['institusi'] ?? '' }}"></div>
                            <div><label>Jurusan</label><input type="text" name="pendidikan[{{ $i }}][jurusan]" value="{{ $p['jurusan'] ?? '' }}"></div>
                            <div><label>Tahun Lulus</label><input type="text" name="pendidikan[{{ $i }}][tahun_lulus]" value="{{ $p['tahun_lulus'] ?? '' }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="empty" data-empty="pendidikan" @if(count($pendidikan)) style="display:none" @endif>Belum ada data.</p>
        </div>

        {{-- ===================== PELATIHAN (NON-FORMAL) ===================== --}}
        <div class="card">
            <div class="card-head">
                <h2>Pelatihan / Pendidikan Non-Formal</h2>
                <button type="button" class="btn btn-add" data-add="pelatihan">+ Tambah</button>
            </div>
            <div data-list="pelatihan">
                @foreach ($pelatihan as $i => $p)
                    <div class="row">
                        <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
                        <div class="grid3">
                            <div><label>Nama Pelatihan</label><input type="text" name="pelatihan[{{ $i }}][nama_pelatihan]" value="{{ $p['nama_pelatihan'] ?? '' }}"></div>
                            <div><label>Penyelenggara</label><input type="text" name="pelatihan[{{ $i }}][penyelenggara]" value="{{ $p['penyelenggara'] ?? '' }}"></div>
                            <div><label>Tahun</label><input type="text" name="pelatihan[{{ $i }}][tahun]" value="{{ $p['tahun'] ?? '' }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="empty" data-empty="pelatihan" @if(count($pelatihan)) style="display:none" @endif>Belum ada data pelatihan.</p>
        </div>

        {{-- ===================== PENGALAMAN KERJA ===================== --}}
        <div class="card">
            <div class="card-head">
                <h2>Pengalaman Kerja</h2>
                <button type="button" class="btn btn-add" data-add="pengalaman_kerja">+ Tambah</button>
            </div>
            <div data-list="pengalaman_kerja">
                @foreach ($pengalaman as $i => $p)
                    <div class="row">
                        <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
                        <div><label>Nama Kegiatan / Proyek</label><input type="text" name="pengalaman_kerja[{{ $i }}][nama_kegiatan]" value="{{ $p['nama_kegiatan'] ?? ($p['jabatan'] ?? '') }}"></div>
                        <div class="grid3">
                            <div><label>Pemberi Pekerjaan</label><input type="text" name="pengalaman_kerja[{{ $i }}][pemberi_pekerjaan]" value="{{ $p['pemberi_pekerjaan'] ?? ($p['perusahaan'] ?? '') }}"></div>
                            <div><label>Posisi</label><input type="text" name="pengalaman_kerja[{{ $i }}][posisi]" value="{{ $p['posisi'] ?? ($p['jabatan'] ?? '') }}"></div>
                            <div><label>Tahun</label><input type="text" name="pengalaman_kerja[{{ $i }}][tahun]" value="{{ $p['tahun'] ?? '' }}"></div>
                        </div>
                        <div class="grid2">
                            <div><label>Lokasi</label><input type="text" name="pengalaman_kerja[{{ $i }}][lokasi]" value="{{ $p['lokasi'] ?? '' }}"></div>
                            <div><label>Durasi</label><input type="text" name="pengalaman_kerja[{{ $i }}][durasi]" value="{{ $p['durasi'] ?? '' }}"></div>
                        </div>
                        <label>Deskripsi / Uraian Tugas</label>
                        <textarea name="pengalaman_kerja[{{ $i }}][deskripsi]">{{ $p['deskripsi'] ?? '' }}</textarea>
                    </div>
                @endforeach
            </div>
            <p class="empty" data-empty="pengalaman_kerja" @if(count($pengalaman)) style="display:none" @endif>Belum ada data.</p>
        </div>

        {{-- ===================== SERTIFIKASI ===================== --}}
        <div class="card">
            <div class="card-head">
                <h2>Sertifikasi Profesi</h2>
                <button type="button" class="btn btn-add" data-add="sertifikasi">+ Tambah</button>
            </div>
            <div data-list="sertifikasi">
                @foreach ($sertifikasi as $i => $s)
                    <div class="row">
                        <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
                        <div class="grid3">
                            <div><label>Nama Sertifikasi</label><input type="text" name="sertifikasi[{{ $i }}][nama_sertifikasi]" value="{{ $s['nama_sertifikasi'] ?? '' }}"></div>
                            <div><label>Penerbit</label><input type="text" name="sertifikasi[{{ $i }}][penerbit]" value="{{ $s['penerbit'] ?? '' }}"></div>
                            <div><label>Tahun</label><input type="text" name="sertifikasi[{{ $i }}][tahun]" value="{{ $s['tahun'] ?? '' }}"></div>
                        </div>
                        <label>File Sertifikat (PDF/JPG/DOC/DOCX)</label>
                        <input type="file" name="sertifikasi_files[{{ $i }}]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                    </div>
                @endforeach
            </div>
            <p class="empty" data-empty="sertifikasi" @if(count($sertifikasi)) style="display:none" @endif>Belum ada data.</p>
        </div>

        {{-- ===================== PROYEK ===================== --}}
        <div class="card">
            <div class="card-head">
                <h2>Proyek Relevan</h2>
                <button type="button" class="btn btn-add" data-add="proyek">+ Tambah</button>
            </div>
            <div data-list="proyek">
                @foreach ($proyek as $i => $p)
                    <div class="row">
                        <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
                        <div class="grid3">
                            <div><label>Nama Proyek</label><input type="text" name="proyek[{{ $i }}][nama_proyek]" value="{{ $p['nama_proyek'] ?? '' }}"></div>
                            <div><label>Klien</label><input type="text" name="proyek[{{ $i }}][klien]" value="{{ $p['klien'] ?? '' }}"></div>
                            <div><label>Tahun</label><input type="text" name="proyek[{{ $i }}][tahun]" value="{{ $p['tahun'] ?? '' }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="empty" data-empty="proyek" @if(count($proyek)) style="display:none" @endif>Belum ada data.</p>
        </div>

        {{-- ===================== KEMAMPUAN BAHASA ===================== --}}
        <div class="card">
            <div class="card-head">
                <h2>Kemampuan Bahasa</h2>
                <button type="button" class="btn btn-add" data-add="bahasa">+ Tambah</button>
            </div>
            <div data-list="bahasa">
                @foreach ($bahasa as $i => $b)
                    <div class="row">
                        <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
                        <div class="grid4">
                            <div><label>Bahasa</label><input type="text" name="bahasa[{{ $i }}][bahasa]" value="{{ $b['bahasa'] ?? '' }}" placeholder="Indonesia / Inggris"></div>
                            <div><label>Speaking</label><input type="text" name="bahasa[{{ $i }}][speaking]" value="{{ $b['speaking'] ?? 'Baik' }}"></div>
                            <div><label>Reading</label><input type="text" name="bahasa[{{ $i }}][reading]" value="{{ $b['reading'] ?? 'Baik' }}"></div>
                            <div><label>Writing</label><input type="text" name="bahasa[{{ $i }}][writing]" value="{{ $b['writing'] ?? 'Baik' }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="empty" data-empty="bahasa" @if(count($bahasa)) style="display:none" @endif>Belum ada data bahasa.</p>
        </div>

        {{-- ===================== SKILLS ===================== --}}
        <div class="card">
            <div class="card-head">
                <h2>Keahlian (Skills)</h2>
                <button type="button" class="btn btn-add" data-add="skills">+ Tambah</button>
            </div>
            <div data-list="skills">
                @foreach ($skills as $skill)
                    <div class="skill-row">
                        <input type="text" name="skills[]" value="{{ is_string($skill) ? $skill : '' }}">
                        <button type="button" class="btn btn-remove" data-remove>Hapus</button>
                    </div>
                @endforeach
            </div>
            <p class="empty" data-empty="skills" @if(count($skills)) style="display:none" @endif>Belum ada data.</p>
        </div>

        {{-- ===================== LAMPIRAN DOKUMEN ===================== --}}
        <div class="card">
            <div class="card-head"><h2>Lampiran Dokumen (PDF)</h2></div>
            <p class="muted" style="margin:0 0 8px;">Opsional. Unggah berkas PDF / JPG / JPEG / DOC / DOCX (maks. 50MB per file). Ijazah boleh lebih dari satu file. (File sertifikat diunggah per baris di bagian Sertifikasi Profesi.)</p>
            <div class="grid2">
                <div><label>KTP</label><input type="file" name="lampiran[ktp]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></div>
                <div><label>NPWP</label><input type="file" name="lampiran[npwp]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></div>
                <div><label>Bukti Pajak</label><input type="file" name="lampiran[bukti_pajak]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></div>
                <div><label>Foto</label><input type="file" name="lampiran[foto]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"></div>
                <div><label>Ijazah (bisa banyak)</label><input type="file" name="ijazah_files[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" multiple></div>
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn btn-primary">Simpan ke Database SISTA</button>
            <a href="{{ route('applicants.upload.form') }}" class="muted">Batal</a>
        </div>
    </form>

    {{-- Templates JavaScript untuk Baris Baru --}}
    <template id="tpl-pendidikan">
        <div class="row">
            <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
            <div class="grid4">
                <div><label>Jenjang</label><input type="text" name="pendidikan[__I__][jenjang]" placeholder="S1/S2/S3"></div>
                <div><label>Institusi</label><input type="text" name="pendidikan[__I__][institusi]"></div>
                <div><label>Jurusan</label><input type="text" name="pendidikan[__I__][jurusan]"></div>
                <div><label>Tahun Lulus</label><input type="text" name="pendidikan[__I__][tahun_lulus]"></div>
            </div>
        </div>
    </template>

    <template id="tpl-pelatihan">
        <div class="row">
            <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
            <div class="grid3">
                <div><label>Nama Pelatihan</label><input type="text" name="pelatihan[__I__][nama_pelatihan]"></div>
                <div><label>Penyelenggara</label><input type="text" name="pelatihan[__I__][penyelenggara]"></div>
                <div><label>Tahun</label><input type="text" name="pelatihan[__I__][tahun]"></div>
            </div>
        </div>
    </template>

    <template id="tpl-pengalaman_kerja">
        <div class="row">
            <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
            <div><label>Nama Kegiatan / Proyek</label><input type="text" name="pengalaman_kerja[__I__][nama_kegiatan]"></div>
            <div class="grid3">
                <div><label>Pemberi Pekerjaan</label><input type="text" name="pengalaman_kerja[__I__][pemberi_pekerjaan]"></div>
                <div><label>Posisi</label><input type="text" name="pengalaman_kerja[__I__][posisi]"></div>
                <div><label>Tahun</label><input type="text" name="pengalaman_kerja[__I__][tahun]"></div>
            </div>
            <div class="grid2">
                <div><label>Lokasi</label><input type="text" name="pengalaman_kerja[__I__][lokasi]"></div>
                <div><label>Durasi</label><input type="text" name="pengalaman_kerja[__I__][durasi]"></div>
            </div>
            <label>Deskripsi / Uraian Tugas</label>
            <textarea name="pengalaman_kerja[__I__][deskripsi]"></textarea>
        </div>
    </template>

    <template id="tpl-sertifikasi">
        <div class="row">
            <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
            <div class="grid3">
                <div><label>Nama Sertifikasi</label><input type="text" name="sertifikasi[__I__][nama_sertifikasi]"></div>
                <div><label>Penerbit</label><input type="text" name="sertifikasi[__I__][penerbit]"></div>
                <div><label>Tahun</label><input type="text" name="sertifikasi[__I__][tahun]"></div>
            </div>
            <label>File Sertifikat (PDF/JPG/DOC/DOCX)</label>
            <input type="file" name="sertifikasi_files[__I__]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,application/pdf,image/jpeg,image/png,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
        </div>
    </template>

    <template id="tpl-proyek">
        <div class="row">
            <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
            <div class="grid3">
                <div><label>Nama Proyek</label><input type="text" name="proyek[__I__][nama_proyek]"></div>
                <div><label>Klien</label><input type="text" name="proyek[__I__][klien]"></div>
                <div><label>Tahun</label><input type="text" name="proyek[__I__][tahun]"></div>
            </div>
        </div>
    </template>

    <template id="tpl-bahasa">
        <div class="row">
            <button type="button" class="btn btn-remove" data-remove style="position:absolute;top:12px;right:12px;">Hapus</button>
            <div class="grid4">
                <div><label>Bahasa</label><input type="text" name="bahasa[__I__][bahasa]" placeholder="Indonesia / Inggris"></div>
                <div><label>Speaking</label><input type="text" name="bahasa[__I__][speaking]" value="Baik"></div>
                <div><label>Reading</label><input type="text" name="bahasa[__I__][reading]" value="Baik"></div>
                <div><label>Writing</label><input type="text" name="bahasa[__I__][writing]" value="Baik"></div>
            </div>
        </div>
    </template>

    <template id="tpl-skills">
        <div class="skill-row">
            <input type="text" name="skills[]">
            <button type="button" class="btn btn-remove" data-remove>Hapus</button>
        </div>
    </template>

    <script>
        const counters = {
            pendidikan: {{ count($pendidikan) }},
            pelatihan: {{ count($pelatihan) }},
            pengalaman_kerja: {{ count($pengalaman) }},
            sertifikasi: {{ count($sertifikasi) }},
            proyek: {{ count($proyek) }},
            bahasa: {{ count($bahasa) }},
        };

        document.querySelectorAll('[data-add]').forEach(btn => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.add;
                const tpl = document.getElementById('tpl-' + key);
                const list = document.querySelector('[data-list="' + key + '"]');
                let html = tpl.innerHTML;
                if (key in counters) {
                    html = html.replaceAll('__I__', counters[key]++);
                }
                const wrap = document.createElement('div');
                wrap.innerHTML = html.trim();
                list.appendChild(wrap.firstChild);
                hideEmpty(key);
            });
        });

        document.addEventListener('click', e => {
            if (e.target.matches('[data-remove]')) {
                const row = e.target.closest('.row, .skill-row');
                const list = row.parentElement;
                row.remove();
                const key = list.dataset.list;
                if (list.children.length === 0) showEmpty(key);
            }
        });

        function hideEmpty(key) {
            const el = document.querySelector('[data-empty="' + key + '"]');
            if (el) el.style.display = 'none';
        }
        function showEmpty(key) {
            const el = document.querySelector('[data-empty="' + key + '"]');
            if (el) el.style.display = '';
        }
    </script>
</body>
</html>