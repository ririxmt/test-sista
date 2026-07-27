<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\CvStaging;
use App\Models\User;
use App\Services\NameNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CvDistributionService
{
    public function __construct(protected NameNormalizer $normalizer)
    {
    }

    public function distribute(CvStaging $staging): Cv
    {
        $data  = $staging->raw_parsed_json;
        $email = $staging->parsed_email ?: ($data['email'] ?? null);

        // Email kantor JANGAN dipakai sebagai identitas talent.
        if (strcasecmp((string) $email, 'office@lapi-itb.com') === 0) {
            $email = null;
        }

        return DB::transaction(function () use ($staging, $data, $email) {

            // 1. Tentukan user (talent).
            //    Idempoten per-staging: kalau staging ini sudah pernah didistribusikan
            //    (mis. admin menyimpan ulang setelah koreksi), pakai user yang sama.
            $userId = $staging->user_id;
            $alreadyDistributed = $userId && Cv::where('user_id', $userId)->exists();

            if (! $alreadyDistributed) {
                // Kalau email pribadi terdeteksi & sudah ada akun dengan email itu,
                // PAKAI ULANG akun tersebut (orang yang sama). Email pribadi unik per orang.
                $existing = $email ? User::where('email', $email)->first() : null;

                if ($existing) {
                    $userId = $existing->id;
                } else {
                    // Ada email -> pakai. Tidak ada email -> buatkan dari nama
                    // (mis. "Asep Sufyan Tsauri" -> AsepSufyanTsauri@dummy.sista).
                    $user = User::create([
                        'name'     => $this->normalizer->titleCase($data['nama'] ?? 'Talent SISTA'),
                        'email'    => $email ?: $this->generateEmailFromName($data['nama'] ?? null),
                        'password' => Hash::make(Str::random(16)), // Password acak sementara
                    ]);
                    $userId = $user->id;
                }
                $staging->update(['user_id' => $userId]);
            }

            $cv = Cv::firstOrNew(['user_id' => $userId]);
            $cv->user_id = $userId;

            $this->applyData($cv, $data);

            // Tandai Staging Record Sebagai Approved + catat cv_id (untuk kontrol export SISTA)
            $staging->update([
                'cv_id'         => $cv->id,
                'review_status' => 'approved',
                'reviewed_at'   => now(),
            ]);

            return $cv->fresh([
                'pendidikanFormal',
                'pendidikanNonformal',
                'pengalamanKerja',
                'sertifikasiProfesi',
                'projectRelevan',
                'cvKeahlian',
                'bahasa',
            ]);
        });
    }

    /**
     * Buat email dari nama saat CV tidak memuat email pribadi.
     * "Asep Sufyan Tsauri" -> AsepSufyanTsauri@dummy.sista (unik; +angka bila bentrok).
     */
    protected function generateEmailFromName(?string $nama): string
    {
        $base = preg_replace('/[^A-Za-z0-9]/', '', (string) $nama);
        $base = $base === '' ? 'Talent' : mb_substr($base, 0, 200);

        $candidate = $base . '@dummy.sista';
        $i = 1;
        while (User::where('email', $candidate)->exists()) {
            $i++;
            $candidate = $base . $i . '@dummy.sista';
        }

        return $candidate;
    }

    /**
     * Tulis satu paket data (berformat sama dengan raw_parsed_json) ke sebuah
     * record Cv: kolom biodata + seluruh tabel anak. Dipakai oleh distribute()
     * (dari staging) maupun oleh fitur edit CV final.
     *
     * $data memakai kunci yang sama dengan form: nama, email, telepon, alamat,
     * pendidikan[], pelatihan[], pengalaman_kerja[], sertifikasi[], proyek[],
     * bahasa[], skills[].
     */
    public function applyData(Cv $cv, array $data): Cv
    {
        $n = fn ($v) => $this->normalizer->titleCase($v);
        // Kolom tahun di SISTA bertipe YEAR(4) — ambil 4 digit tahun pertama, selain itu null.
        $year = fn ($v) => preg_match('/\d{4}/', (string) $v, $m) ? $m[0] : null;
        // Potong agar muat di kolom varchar SISTA (cegah error "Data too long"). null tetap null.
        $cut = fn ($v, $len = 255) => $v === null ? null : mb_substr((string) $v, 0, $len);

        return DB::transaction(function () use ($cv, $data, $n, $year, $cut) {

            // 1. Kolom biodata pada tabel `cv` (skema SISTA: tanpa email/telepon/alamat;
            //    ringkasan profil disimpan di employment_desc)
            $cv->fill([
                'nama'                => $cut($n($data['nama'] ?? null)),
                'gelar_depan'         => $data['gelar_depan'] ?? null,
                'gelar_belakang'      => $data['gelar_belakang'] ?? null,
                'posisi'              => $cut($n($data['posisi'] ?? null)),
                'perusahaan'          => $cut($n($data['perusahaan'] ?? null)),
                'tempat_lahir'        => $cut($n($data['tempat_lahir'] ?? null), 100),
                'tanggal_lahir'       => ($data['tanggal_lahir'] ?? null) ?: null,
                'kewarganegaraan'     => $cut($data['kewarganegaraan'] ?? 'Indonesia', 100),
                'employer'            => $cut($n($data['perusahaan'] ?? null)),
                'employment_position' => $cut($n($data['posisi'] ?? null)),
                'employment_desc'     => $data['ringkasan_profil'] ?? null,
                'domisili_negara'     => $cut($data['domisili_negara'] ?? 'Indonesia', 100),
                'domisili_kota'       => $cut($n($data['domisili_kota'] ?? null), 100),
            ]);
            $cv->save();

            // 2. Pendidikan Formal
            $cv->pendidikanFormal()->delete();
            foreach ($this->cleanRows($data['pendidikan'] ?? []) as $item) {
                $cv->pendidikanFormal()->create([
                    'tingkat'     => $cut($n($item['jenjang'] ?? null), 100),
                    'institusi'   => $cut($n($item['institusi'] ?? null)),
                    'jurusan'     => $cut($n($item['jurusan'] ?? null)),
                    'tahun_lulus' => $year($item['tahun_lulus'] ?? null),
                    'is_visible'  => true,
                ]);
            }

            // 3. Pendidikan Non-Formal (Pelatihan)
            $cv->pendidikanNonformal()->delete();
            foreach ($this->cleanRows($data['pelatihan'] ?? []) as $item) {
                $cv->pendidikanNonformal()->create([
                    'nama_pelatihan' => $cut($n($item['nama_pelatihan'] ?? null)),
                    'penyelenggara'  => $cut($n($item['penyelenggara'] ?? null)),
                    'tahun'          => $year($item['tahun'] ?? null),
                    'is_visible'     => true,
                ]);
            }

            // 4. Pengalaman Kerja -> kolom yang dipakai SISTA (nama_kegiatan, pemberi_pekerjaan,
            //    posisi, tahun, ...). Fallback ke key lama (jabatan/perusahaan/deskripsi).
            $cv->pengalamanKerja()->delete();
            foreach ($this->cleanRows($data['pengalaman_kerja'] ?? []) as $item) {
                $namaKegiatan = $item['nama_kegiatan'] ?? $item['jabatan'] ?? null;
                $pemberi      = $item['pemberi_pekerjaan'] ?? $item['perusahaan'] ?? null;
                $posisi       = $item['posisi'] ?? $item['jabatan'] ?? null;
                $uraian       = $item['deskripsi'] ?? $item['uraian_tugas'] ?? null;
                $cv->pengalamanKerja()->create([
                    'nama_kegiatan'     => $cut($n($namaKegiatan)),
                    'pemberi_pekerjaan' => $cut($n($pemberi)),
                    'posisi'            => $cut($n($posisi), 100),
                    'tahun'             => $year($item['tahun'] ?? $item['durasi'] ?? null),
                    'lokasi'            => $cut($n($item['lokasi'] ?? null)),
                    'durasi'            => $cut($item['durasi'] ?? null, 100),
                    'uraian_tugas'      => $uraian,
                    'is_visible'        => true,
                ]);
            }

            // 5. Sertifikasi Profesi (+ file per-baris di kolom file_sertifikat)
            $oldSertFiles  = $cv->sertifikasiProfesi()->pluck('file_sertifikat')->filter()->all();
            $keptSertFiles = [];
            $cv->sertifikasiProfesi()->delete();
            foreach ($this->cleanRows($data['sertifikasi'] ?? []) as $item) {
                $fileSertifikat = $item['file_sertifikat'] ?? null;
                if ($fileSertifikat) {
                    $keptSertFiles[] = $fileSertifikat;
                }
                $cv->sertifikasiProfesi()->create([
                    'nama'            => $cut($n($item['nama_sertifikasi'] ?? null)),
                    'penerbit'        => $cut($n($item['penerbit'] ?? null)),
                    'tahun'           => $year($item['tahun'] ?? null),
                    'file_sertifikat' => $fileSertifikat,
                    'is_visible'      => true,
                ]);
            }
            // Buang file sertifikat yang tidak lagi dipakai (diganti / barisnya dihapus).
            foreach (array_diff($oldSertFiles, $keptSertFiles) as $orphan) {
                Storage::disk('local')->delete($orphan);
            }

            // 6. Project Relevan (tanpa is_visible di SISTA)
            $cv->projectRelevan()->delete();
            foreach ($this->cleanRows($data['proyek'] ?? []) as $item) {
                $cv->projectRelevan()->create([
                    'nama_project' => $cut($n($item['nama_proyek'] ?? null)),
                    'klien'        => $cut($n($item['klien'] ?? null)),
                    'tahun'        => $year($item['tahun'] ?? null),
                ]);
            }

            // 7. Keahlian (Skills)
            $cv->cvKeahlian()->delete();
            foreach ($data['skills'] ?? [] as $skillName) {
                if (trim((string) $skillName) === '') {
                    continue;
                }
                $cv->cvKeahlian()->create([
                    'nama_keahlian' => $cut($n($skillName), 100),
                    'is_visible'    => true,
                ]);
            }

            // 8. Kemampuan Bahasa
            $cv->bahasa()->delete();
            foreach ($this->cleanRows($data['bahasa'] ?? []) as $b) {
                $cv->bahasa()->create([
                    'bahasa'     => $cut($n($b['bahasa'] ?? null), 100),
                    'speaking'   => $cut($b['speaking'] ?? 'Baik', 100),
                    'reading'    => $cut($b['reading'] ?? 'Baik', 100),
                    'writing'    => $cut($b['writing'] ?? 'Baik', 100),
                    'is_visible' => true,
                ]);
            }

            return $cv;
        });
    }

    /**
     * Buang baris yang seluruh kolomnya kosong.
     */
    private function cleanRows($rows): array
    {
        $out = [];
        foreach ((array) $rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $v) {
                if (trim((string) $v) !== '') {
                    $out[] = $row;
                    break;
                }
            }
        }

        return $out;
    }
}
