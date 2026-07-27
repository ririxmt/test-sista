<?php

namespace App\Http\Controllers;

use App\Models\CvStaging;
use App\Services\CvDistributionService;
use App\Services\CvLampiranService;
use Illuminate\Http\Request;

class CvReviewController extends Controller
{
    public function __construct(
        protected CvDistributionService $distributionService,
        protected CvLampiranService $lampiranService,
    ) {
    }

    /**
     * Tampilkan hasil parsing AI dalam form editable per-seksi.
     * Admin boleh mengoreksi sebelum menyimpan ke tabel final.
     */
    public function edit(CvStaging $staging)
    {
        return view('cv_staging.edit', compact('staging'));
    }

    /**
     * Terima hasil edit form -> tulis balik ke staging -> distribusikan ke
     * tabel final (cv + tabel anaknya) lewat CvDistributionService.
     */
    public function update(Request $request, CvStaging $staging)
    {
        $request->validate([
            'lampiran.ktp'         => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'lampiran.npwp'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'lampiran.bukti_pajak' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'lampiran.foto'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'ijazah_files.*'       => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
            'sertifikasi_files.*'  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:51200'],
        ]);

        $existing = $staging->raw_parsed_json ?? [];

        $edited = array_merge($existing, [
            'nama'             => $request->input('nama'),
            'gelar_depan'      => $request->input('gelar_depan'),
            'gelar_belakang'   => $request->input('gelar_belakang'),
            'email'            => $request->input('email'),
            'telepon'          => $request->input('telepon'),
            'tempat_lahir'     => $request->input('tempat_lahir'),
            'tanggal_lahir'    => $request->input('tanggal_lahir'),
            'posisi'           => $request->input('posisi'),
            'perusahaan'       => $request->input('perusahaan'),
            'domisili_kota'    => $request->input('domisili_kota'),
            'domisili_negara'  => $request->input('domisili_negara', 'Indonesia'),
            'alamat'           => $request->input('alamat'),
            'ringkasan_profil' => $request->input('ringkasan_profil'),
            'pendidikan'       => $this->cleanRows($request->input('pendidikan')),
            'pelatihan'        => $this->cleanRows($request->input('pelatihan')),
            'pengalaman_kerja' => $this->cleanRows($request->input('pengalaman_kerja')),
            'sertifikasi'      => $this->cleanRows($this->lampiranService->attachRowFiles(
                $request->input('sertifikasi', []),
                $request->file('sertifikasi_files', []),
                'file_sertifikat',
            )),
            'proyek'           => $this->cleanRows($request->input('proyek')),
            'bahasa'           => $this->cleanRows($request->input('bahasa')),
            'skills'           => $this->cleanSkills($request->input('skills')),
        ]);

        $staging->update([
            'raw_parsed_json' => $edited,
            'parsed_email'    => $edited['email'] ?? null,
            'parsed_name'     => $edited['nama'] ?? null,
            'review_status'   => 'approved',
            'reviewed_at'     => now(),
        ]);

        $cv = $this->distributionService->distribute($staging->fresh());

        // Lampiran single (KTP/NPWP/Bukti Pajak/Foto) diproses setelah cv terbentuk.
        $this->lampiranService->sync($cv, (array) $request->file('lampiran', []));

        // Lampiran multi (Ijazah) — bisa banyak.
        $this->lampiranService->syncMulti($cv, [
            'ijazah' => (array) $request->file('ijazah_files', []),
        ]);

        // Kalau CV ini sudah pernah diexport ke SISTA, tandai berubah agar ikut export lagi.
        CvStaging::markChangedAfterExport($cv->id);

        return redirect()->route('cv.index')
            ->with('success', "CV #{$cv->id} ({$cv->nama}) berhasil disimpan ke database SISTA.");
}

    /**
     * Buang baris yang seluruh kolomnya kosong, dan reindex agar berurutan.
     * (Baris yang dihapus admin di form menyisakan indeks yang bolong.)
     */
    private function cleanRows($rows): array
    {
        $out = [];
        foreach ((array) $rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $hasValue = false;
            foreach ($row as $v) {
                if (trim((string) $v) !== '') {
                    $hasValue = true;
                    break;
                }
            }
            if ($hasValue) {
                $out[] = $row;
            }
        }

        return $out;
    }

    /**
     * Skills adalah daftar string datar — buang yang kosong, reindex.
     */
    private function cleanSkills($skills): array
    {
        $out = [];
        foreach ((array) $skills as $skill) {
            $skill = trim((string) $skill);
            if ($skill !== '') {
                $out[] = $skill;
            }
        }

        return $out;
    }
}
