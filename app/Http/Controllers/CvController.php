<?php

namespace App\Http\Controllers;

use App\Models\Cv;
use App\Models\CvStaging;
use App\Models\SertifikasiProfesi;
use App\Models\User;
use App\Services\CvDistributionService;
use App\Services\CvLampiranService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CvController extends Controller
{
    public function __construct(
        protected CvDistributionService $distributionService,
        protected CvLampiranService $lampiranService,
    ) {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        // Lolos karakter wildcard LIKE ('%' dan '_') supaya diperlakukan sebagai
        // teks biasa, bukan pola — mencari "_" jangan sampai cocok ke semua baris.
        $term = '%' . addcslashes($q, '%_\\') . '%';

        $cvs = Cv::with('user')
            ->when($q !== '', fn ($query) => $query->where(function ($sub) use ($term) {
                $sub->where('nama', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $term));
            }))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString(); // pertahankan ?q= saat pindah halaman

        return view('cv.index', compact('cvs', 'q'));
    }

    public function show(Cv $cv)
    {
        $cv->load([
            'pendidikanFormal',
            'pendidikanNonformal',
            'pengalamanKerja',
            'sertifikasiProfesi',
            'projectRelevan',
            'cvKeahlian',
            'bahasa',
            'lampiran',
        ]);

        return view('cv.show', compact('cv'));
    }

    public function edit(Cv $cv)
    {
        $cv->load([
            'pendidikanFormal',
            'pendidikanNonformal',
            'pengalamanKerja',
            'sertifikasiProfesi',
            'projectRelevan',
            'cvKeahlian',
            'bahasa',
            'lampiran',
            'user',
        ]);

        return view('cv.edit', compact('cv'));
    }

    public function update(Request $request, Cv $cv)
    {
        // Email tersimpan di akun user (tabel `cv` tidak punya kolom email).
        // Aturan email hanya dipasang kalau nilainya benar-benar diubah, supaya
        // email lama hasil parsing yang berantakan (mis. dua alamat sekaligus)
        // tidak memblokir penyimpanan field-field lain.
        $emailBaru   = trim((string) $request->input('email'));
        $emailDiubah = $cv->user && strcasecmp($emailBaru, (string) $cv->user->email) !== 0;

        $request->validate([
            'nama'                 => ['nullable', 'string', 'max:255'],
            'email'                => $emailDiubah ? [
                'nullable', 'string', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($cv->user_id),
            ] : [],
            'lampiran.ktp'         => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'lampiran.npwp'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'lampiran.bukti_pajak' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'lampiran.foto'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'ijazah_files.*'       => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'sertifikasi_files.*'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
        ]);

        // Sertifikasi: tempelkan file per-baris (upload baru menimpa path lama),
        // dan kosongkan path bila baris ditandai hapus file.
        $sertifikasi = $this->lampiranService->attachRowFiles(
            $request->input('sertifikasi', []),
            $request->file('sertifikasi_files', []),
            'file_sertifikat',
        );
        foreach ((array) $request->input('sertifikasi_hapus_file', []) as $i => $v) {
            if ($v && isset($sertifikasi[$i])) {
                $sertifikasi[$i]['file_sertifikat'] = null;
            }
        }

        // Bentuk data mengikuti struktur form (sama dengan raw_parsed_json),
        // lalu ditulis ke cv + seluruh tabel anak lewat service.
        $data = [
            'nama'             => $request->input('nama'),
            'gelar_depan'      => $request->input('gelar_depan'),
            'gelar_belakang'   => $request->input('gelar_belakang'),
            'tempat_lahir'     => $request->input('tempat_lahir'),
            'tanggal_lahir'    => $request->input('tanggal_lahir'),
            'kewarganegaraan'  => $request->input('kewarganegaraan', $cv->kewarganegaraan),
            'posisi'           => $request->input('posisi'),
            'perusahaan'       => $request->input('perusahaan'),
            'domisili_kota'    => $request->input('domisili_kota'),
            'domisili_negara'  => $request->input('domisili_negara', 'Indonesia'),
            'ringkasan_profil' => $request->input('ringkasan_profil'),
            'pendidikan'       => $request->input('pendidikan', []),
            'pelatihan'        => $request->input('pelatihan', []),
            'pengalaman_kerja' => $request->input('pengalaman_kerja', []),
            'sertifikasi'      => $sertifikasi,
            'proyek'           => $request->input('proyek', []),
            'bahasa'           => $request->input('bahasa', []),
            'skills'           => $request->input('skills', []),
        ];

        $this->distributionService->applyData($cv, $data);

        // Simpan email ke akun talent yang terhubung.
        // Dikosongkan = biarkan email lama (akun wajib punya email).
        if ($emailDiubah && $emailBaru !== '') {
            $cv->user->forceFill([
                'email'             => $emailBaru,
                'email_verified_at' => null, // alamat baru belum terverifikasi
            ])->save();
        }

        // Lampiran single (KTP/NPWP/Bukti Pajak/Foto): file baru mengganti yang lama.
        $this->lampiranService->sync(
            $cv,
            (array) $request->file('lampiran', []),
            (array) $request->input('lampiran_hapus', []),
        );

        // Lampiran multi (Ijazah): tambah banyak file + hapus per-index.
        $this->lampiranService->syncMulti(
            $cv,
            ['ijazah' => (array) $request->file('ijazah_files', [])],
            ['ijazah' => (array) $request->input('ijazah_hapus', [])],
        );

        // Kalau CV ini sudah pernah diexport ke SISTA, tandai berubah agar ikut export lagi.
        CvStaging::markChangedAfterExport($cv->id);

        return redirect()->route('cv.show', $cv)
            ->with('success', 'Data CV berhasil diperbarui.');
    }

    public function destroy(Cv $cv)
    {
        $nama = $cv->nama ?: 'Tanpa Nama';

        $this->lampiranService->purgeFiles($cv); // buang file lampiran_cv & ijazah
        // buang file sertifikat per-baris
        foreach ($cv->sertifikasiProfesi()->pluck('file_sertifikat')->filter() as $path) {
            $this->lampiranService->deleteFile($path);
        }

        // Bersihkan staging terkait agar tidak jadi "hantu" di daftar export.
        // (staging menunjuk ke cv ini; kalau cv dihapus, staging-nya ikut dibuang + file uploadnya)
        $stagings = CvStaging::where('cv_id', $cv->id)->get();
        foreach ($stagings as $s) {
            if ($s->source_file_path) {
                Storage::disk('local')->delete($s->source_file_path);
            }
        }
        CvStaging::where('cv_id', $cv->id)->delete();

        $cv->delete();                            // baris DB cascade otomatis

        return redirect()->route('cv.index')
            ->with('success', "CV \"{$nama}\" berhasil dihapus.");
    }

    /**
     * Alirkan file lampiran (disk privat) ke browser.
     */
    public function lampiran(Cv $cv, string $jenis)
    {
        $meta = CvLampiranService::JENIS[$jenis] ?? abort(404);
        $path = $cv->lampiran?->{$meta['col']};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('local')->response(
            $path,
            $meta['label'] . '-' . $cv->id . '.' . $ext
        );
    }

    /**
     * Alirkan file lampiran MULTI (ijazah/sertifikat) berdasarkan index.
     */
    public function lampiranMulti(Cv $cv, string $jenis, int $index)
    {
        $label = CvLampiranService::JENIS_MULTI[$jenis] ?? abort(404);
        $paths = $this->lampiranService->multiFiles($cv, $jenis);
        $path  = $paths[$index] ?? null;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('local')->response(
            $path,
            $label . '-' . $cv->id . '-' . ($index + 1) . '.' . $ext
        );
    }

    /**
     * Alirkan file sertifikat milik satu baris sertifikasi.
     */
    public function sertifikatFile(SertifikasiProfesi $sertifikasi)
    {
        $path = $sertifikasi->file_sertifikat;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';

        return Storage::disk('local')->response(
            $path,
            'Sertifikat-' . $sertifikasi->id . '.' . $ext
        );
    }
}
