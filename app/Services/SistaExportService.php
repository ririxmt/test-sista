<?php

namespace App\Services;

use App\Models\Cv;
use App\Models\CvStaging;
use App\Models\SistaExportBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class SistaExportService
{
    protected string $disk = 'local';

    /**
     * Bangun paket ZIP export untuk SISTA dari CV approved yang belum/berubah setelah export.
     * READ-ONLY terhadap data sumber — hanya menulis file & memperbarui STATUS export.
     *
     * @return array{zip_path:string, zip_name:string, batch:SistaExportBatch, total_cv:int, total_files:int, missing_files:int}
     */
    public function export(?int $exportedBy = null): array
    {
        $stagings = CvStaging::query()
            ->where('review_status', 'approved')
            ->whereNotNull('cv_id')
            ->whereIn('sista_export_status', ['not_exported', 'changed_after_export'])
            ->orderBy('id')
            ->get();

        if ($stagings->isEmpty()) {
            throw new RuntimeException('Tidak ada data untuk diekspor.');
        }

        $cvIds = $stagings->pluck('cv_id')->unique()->values();

        $ts        = now()->format('Ymd-His');
        $batchCode = 'RP-' . $ts;
        $zipName   = 'resume-parser-sista-' . $ts . '-' . $batchCode . '.zip';

        $batch = SistaExportBatch::create([
            'batch_code'   => $batchCode,
            'exported_by'  => $exportedBy,
            'status'       => 'processing',
            'zip_filename' => $zipName,
        ]);

        $root    = storage_path('app/sista-exports');
        $workDir = $root . '/' . $batchCode;
        $zipPath = $root . '/' . $zipName;

        try {
            File::ensureDirectoryExists($workDir . '/files');

            $cvs = Cv::with([
                'user', 'bahasa', 'cvKeahlian', 'pendidikanFormal', 'pendidikanNonformal',
                'pengalamanKerja', 'projectRelevan', 'sertifikasiProfesi', 'lampiran',
            ])->whereIn('id', $cvIds)->get();

            // Salin file fisik dulu; dapatkan path relatif dalam ZIP untuk ditulis ke CSV.
            $fileMap = $this->copyFiles($cvs, $stagings, $workDir);

            $this->writeAllCsv($workDir, $cvs, $fileMap);
            $this->writeManifest($workDir, $batchCode, $cvs, $fileMap['total']);

            $this->buildZip($workDir, $zipPath);

            if (! file_exists($zipPath) || filesize($zipPath) === 0) {
                throw new RuntimeException('ZIP tidak berhasil dibuat.');
            }

            // Sukses -> tandai staging exported + catat batch. (baru SETELAH ZIP jadi)
            DB::transaction(function () use ($stagings, $batchCode, $batch, $cvs, $fileMap) {
                CvStaging::whereIn('id', $stagings->pluck('id'))->update([
                    'sista_export_status' => 'exported',
                    'sista_export_batch'  => $batchCode,
                    'sista_exported_at'   => now(),
                ]);
                $batch->update([
                    'status'      => 'success',
                    'exported_at' => now(),
                    'total_cv'    => $cvs->count(),
                    'total_files' => $fileMap['total'],
                ]);
            });

            File::deleteDirectory($workDir); // sisakan hanya ZIP

            return [
                'zip_path'      => $zipPath,
                'zip_name'      => $zipName,
                'batch'         => $batch->fresh(),
                'total_cv'      => $cvs->count(),
                'total_files'   => $fileMap['total'],
                'missing_files' => $fileMap['missing'],
            ];
        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            File::deleteDirectory($workDir);
            throw $e;
        }
    }

    /**
     * Salin semua file lampiran ke workDir/files/{cv_id}/ dan kembalikan pemetaan path.
     */
    protected function copyFiles($cvs, $stagings, string $workDir): array
    {
        $total = 0;
        $missing = 0;
        $lampiran = [];                 // [cv_id][kolom] => path zip
        $ijazahByPendidikan = [];       // [pendidikan_formal_id] => path zip
        $sertBySertifikasi = [];        // [sertifikasi_id] => path zip
        $refByPengalaman = [];          // [pengalaman_id] => path zip

        foreach ($cvs as $cv) {
            $rel = 'files/' . $cv->id;
            $abs = $workDir . '/' . $rel;

            // 1. File CV asli (dari staging)
            $staging = $stagings->firstWhere('cv_id', $cv->id);
            if ($staging && $staging->source_file_path) {
                $ext = $this->ext($staging->source_file_path);
                if ($this->copy($staging->source_file_path, $abs . '/cv-original.' . $ext)) {
                    $total++;
                } else {
                    $missing++;
                }
            }

            // 2. Lampiran tunggal (ktp/npwp/bukti_pajak/foto)
            if ($cv->lampiran) {
                foreach (['ktp_file' => 'ktp', 'npwp_file' => 'npwp', 'bukti_pajak' => 'bukti-pajak', 'foto' => 'foto'] as $col => $name) {
                    $src = $cv->lampiran->{$col};
                    if ($src) {
                        $ext = $this->ext($src);
                        $file = $name . '-' . $cv->id . '.' . $ext;
                        if ($this->copy($src, $abs . '/' . $file)) {
                            $lampiran[$cv->id][$col] = $rel . '/' . $file;
                            $total++;
                        } else {
                            $missing++;
                        }
                    }
                }

                // 3. Ijazah (JSON global) -> dipetakan ke pendidikan_formal secara berurutan
                $ijazahList = array_values($cv->lampiran->lainnya['ijazah'] ?? []);
                $i = 0;
                foreach ($cv->pendidikanFormal as $pf) {
                    if (! isset($ijazahList[$i])) {
                        break;
                    }
                    $src = $ijazahList[$i];
                    $ext = $this->ext($src);
                    $file = 'ijazah-' . $pf->id . '.' . $ext;
                    if ($this->copy($src, $abs . '/' . $file)) {
                        $ijazahByPendidikan[$pf->id] = $rel . '/' . $file;
                        $total++;
                    } else {
                        $missing++;
                    }
                    $i++;
                }
                // Sisa ijazah (lebih banyak dari jumlah pendidikan) tetap disertakan agar tidak hilang.
                for (; $i < count($ijazahList); $i++) {
                    $src = $ijazahList[$i];
                    $file = 'ijazah-extra-' . ($i + 1) . '.' . $this->ext($src);
                    if ($this->copy($src, $abs . '/' . $file)) {
                        $total++;
                    } else {
                        $missing++;
                    }
                }
            }

            // 4. Sertifikat per baris sertifikasi
            foreach ($cv->sertifikasiProfesi as $s) {
                if ($s->file_sertifikat) {
                    $file = 'sertifikat-' . $s->id . '.' . $this->ext($s->file_sertifikat);
                    if ($this->copy($s->file_sertifikat, $abs . '/' . $file)) {
                        $sertBySertifikasi[$s->id] = $rel . '/' . $file;
                        $total++;
                    } else {
                        $missing++;
                    }
                }
            }

            // 5. Referensi per baris pengalaman
            foreach ($cv->pengalamanKerja as $e) {
                if ($e->referensi_file) {
                    $file = 'referensi-' . $e->id . '.' . $this->ext($e->referensi_file);
                    if ($this->copy($e->referensi_file, $abs . '/' . $file)) {
                        $refByPengalaman[$e->id] = $rel . '/' . $file;
                        $total++;
                    } else {
                        $missing++;
                    }
                }
            }
        }

        return compact('total', 'missing', 'lampiran', 'ijazahByPendidikan', 'sertBySertifikasi', 'refByPengalaman');
    }

    protected function writeAllCsv(string $dir, $cvs, array $fileMap): void
    {
        $v  = fn ($x) => $x === null ? '' : (string) $x;
        $b  = fn ($x) => $x ? 1 : 0;
        $d  = fn ($x) => $x ? optional($this->carbon($x))->format('Y-m-d') ?? '' : '';
        $dt = fn ($x) => $x ? optional($this->carbon($x))->format('Y-m-d H:i:s') ?? '' : '';

        // users.csv (unik per user)
        $users = $cvs->pluck('user')->filter()->unique('id');
        $this->writeCsv("$dir/users.csv",
            ['source_user_id', 'name', 'email', 'source_created_at', 'source_updated_at'],
            $users->map(fn ($u) => [$u->id, $v($u->name), $this->normalizeEmail($u->email) ?? '', $dt($u->created_at), $dt($u->updated_at)]));

        // cv.csv
        $this->writeCsv("$dir/cv.csv",
            ['source_cv_id', 'source_user_id', 'nama', 'gelar_depan', 'gelar_belakang', 'posisi', 'perusahaan',
             'tempat_lahir', 'tanggal_lahir', 'kewarganegaraan', 'status_kepegawaian', 'pernah_di_wb',
             'employment_from', 'employment_to', 'employer', 'employment_position', 'employment_desc',
             'domisili_negara', 'domisili_kota', 'source_created_at', 'source_updated_at'],
            $cvs->map(fn ($c) => [
                $c->id, $c->user_id, $v($c->nama), $v($c->gelar_depan), $v($c->gelar_belakang), $v($c->posisi), $v($c->perusahaan),
                $v($c->tempat_lahir), $d($c->tanggal_lahir), $v($c->kewarganegaraan), $v($c->status_kepegawaian), $v($c->pernah_di_wb),
                $d($c->employment_from), $d($c->employment_to), $v($c->employer), $v($c->employment_position), $v($c->employment_desc),
                $v($c->domisili_negara), $v($c->domisili_kota), $dt($c->created_at), $dt($c->updated_at),
            ]));

        // bahasa.csv
        $this->writeCsv("$dir/bahasa.csv",
            ['source_bahasa_id', 'source_cv_id', 'bahasa', 'speaking', 'reading', 'writing', 'is_visible'],
            $this->flat($cvs, 'bahasa', fn ($x, $c) => [$x->id, $c->id, $v($x->bahasa), $v($x->speaking), $v($x->reading), $v($x->writing), $b($x->is_visible)]));

        // cv_keahlian.csv
        $this->writeCsv("$dir/cv_keahlian.csv",
            ['source_keahlian_id', 'source_cv_id', 'nama_keahlian', 'is_visible', 'source_created_at'],
            $this->flat($cvs, 'cvKeahlian', fn ($x, $c) => [$x->id, $c->id, $v($x->nama_keahlian), $b($x->is_visible), $dt($x->created_at)]));

        // cv_keahlian_link.csv (kosong di aplikasi ini — header saja)
        $this->writeCsv("$dir/cv_keahlian_link.csv",
            ['source_link_id', 'source_keahlian_id', 'source_target_id', 'target_type', 'is_visible_section'], collect());

        // pendidikan_formal.csv (ijazah_file dari pemetaan)
        $this->writeCsv("$dir/pendidikan_formal.csv",
            ['source_pendidikan_formal_id', 'source_cv_id', 'tingkat', 'institusi', 'jurusan', 'tahun_lulus', 'ijazah_file', 'is_visible'],
            $this->flat($cvs, 'pendidikanFormal', fn ($x, $c) => [$x->id, $c->id, $v($x->tingkat), $v($x->institusi), $v($x->jurusan), $v($x->tahun_lulus), $fileMap['ijazahByPendidikan'][$x->id] ?? '', $b($x->is_visible)]));

        // pendidikan_nonformal.csv
        $this->writeCsv("$dir/pendidikan_nonformal.csv",
            ['source_pendidikan_nonformal_id', 'source_cv_id', 'nama_pelatihan', 'penyelenggara', 'tahun', 'sertifikat_file', 'is_visible'],
            $this->flat($cvs, 'pendidikanNonformal', fn ($x, $c) => [$x->id, $c->id, $v($x->nama_pelatihan), $v($x->penyelenggara), $v($x->tahun), $v($x->sertifikat_file), $b($x->is_visible)]));

        // pengalaman_kerja.csv (referensi_file dari pemetaan)
        $this->writeCsv("$dir/pengalaman_kerja.csv",
            ['source_pengalaman_id', 'source_cv_id', 'tahun', 'nama_kegiatan', 'uraian_proyek', 'lokasi', 'negara', 'pemberi_pekerjaan', 'perusahaan',
             'pelaksana_proyek', 'uraian_tugas', 'waktu_mulai', 'waktu_akhir', 'durasi', 'waktu_legacy', 'posisi', 'status_kepegawaian',
             'referensi_file', 'is_visible', 'source_created_at', 'source_updated_at'],
            $this->flat($cvs, 'pengalamanKerja', fn ($x, $c) => [
                $x->id, $c->id, $v($x->tahun), $v($x->nama_kegiatan), $v($x->uraian_proyek), $v($x->lokasi), $v($x->negara), $v($x->pemberi_pekerjaan), $v($x->perusahaan),
                $v($x->pelaksana_proyek), $v($x->uraian_tugas), $d($x->waktu_mulai), $d($x->waktu_akhir), $v($x->durasi), $v($x->waktu_legacy), $v($x->posisi), $v($x->status_kepegawaian),
                $fileMap['refByPengalaman'][$x->id] ?? '', $b($x->is_visible), $dt($x->created_at), $dt($x->updated_at),
            ]));

        // project_relevan.csv
        $this->writeCsv("$dir/project_relevan.csv",
            ['source_project_id', 'source_cv_id', 'nama_project', 'tahun', 'lokasi', 'klien', 'fitur_proyek', 'posisi', 'aktivitas'],
            $this->flat($cvs, 'projectRelevan', fn ($x, $c) => [$x->id, $c->id, $v($x->nama_project), $v($x->tahun), $v($x->lokasi), $v($x->klien), $v($x->fitur_proyek), $v($x->posisi), $v($x->aktivitas)]));

        // sertifikasi_profesi.csv (file_sertifikat dari pemetaan)
        $this->writeCsv("$dir/sertifikasi_profesi.csv",
            ['source_sertifikasi_id', 'source_cv_id', 'nama', 'penerbit', 'tahun', 'file_sertifikat', 'is_visible'],
            $this->flat($cvs, 'sertifikasiProfesi', fn ($x, $c) => [$x->id, $c->id, $v($x->nama), $v($x->penerbit), $v($x->tahun), $fileMap['sertBySertifikasi'][$x->id] ?? '', $b($x->is_visible)]));

        // lampiran_cv.csv (path file diganti ke path ZIP; ijazah sudah pindah ke pendidikan)
        $lampRows = collect();
        foreach ($cvs as $c) {
            $l = $c->lampiran;
            if (! $l) {
                continue;
            }
            $m = $fileMap['lampiran'][$c->id] ?? [];
            $lampRows->push([
                $l->id, $c->id,
                $m['ktp_file'] ?? '', $b($l->is_visible_ktp_file),
                $m['npwp_file'] ?? '', $b($l->is_visible_npwp_file),
                $m['bukti_pajak'] ?? '', $b($l->is_visible_bukti_pajak),
                $m['foto'] ?? '', $b($l->is_visible_foto),
                '', $b($l->is_visible_lainnya), // lainnya dikosongkan (ijazah dipindah ke pendidikan)
            ]);
        }
        $this->writeCsv("$dir/lampiran_cv.csv",
            ['source_lampiran_id', 'source_cv_id', 'ktp_file', 'is_visible_ktp_file', 'npwp_file', 'is_visible_npwp_file',
             'bukti_pajak', 'is_visible_bukti_pajak', 'foto', 'is_visible_foto', 'lainnya', 'is_visible_lainnya'],
            $lampRows);
    }

    protected function writeManifest(string $dir, string $batchCode, $cvs, int $totalFiles): void
    {
        $manifest = [
            'schema_version'         => '1.0',
            'source_application'     => 'resume_parser',
            'export_batch_id'        => $batchCode,
            'exported_at'            => now()->toIso8601String(),
            'export_type'            => 'incremental',
            'total_users'            => $cvs->pluck('user')->filter()->unique('id')->count(),
            'total_cv'               => $cvs->count(),
            'total_bahasa'           => $cvs->sum(fn ($c) => $c->bahasa->count()),
            'total_pengalaman_kerja' => $cvs->sum(fn ($c) => $c->pengalamanKerja->count()),
            'total_files'            => $totalFiles,
        ];
        File::put($dir . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function buildZip(string $workDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuka file ZIP.');
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($workDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $abs = $file->getRealPath();
            $rel = substr($abs, strlen($workDir) + 1);
            $zip->addFile($abs, $rel);
        }
        $zip->close();
    }

    // ---------------- helper kecil ----------------

    protected function writeCsv(string $path, array $headers, $rows): void
    {
        $h = fopen($path, 'wb');
        if ($h === false) {
            throw new RuntimeException('Tidak dapat membuat CSV: ' . $path);
        }
        fwrite($h, "\xEF\xBB\xBF"); // BOM UTF-8 untuk Excel
        fputcsv($h, $headers, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($h, $row, ',', '"', '');
        }
        fclose($h);
    }

    /** Ratakan relasi anak dari semua cv menjadi baris CSV. */
    protected function flat($cvs, string $relation, callable $map)
    {
        $out = collect();
        foreach ($cvs as $c) {
            foreach ($c->{$relation} as $child) {
                $out->push($map($child, $c));
            }
        }
        return $out;
    }

    protected function normalizeEmail(?string $email): ?string
    {
        // Ambil satu alamat pertama (kalau ada beberapa dipisah koma/spasi), trim, lowercase, validasi.
        $email = trim((string) $email);
        if ($email !== '' && preg_match('/[^\s,;]+@[^\s,;]+\.[^\s,;]+/', $email, $m)) {
            $email = $m[0];
        }
        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        return $email;
    }

    protected function copy(?string $storageRelPath, string $destAbs): bool
    {
        if (! $storageRelPath || ! Storage::disk($this->disk)->exists($storageRelPath)) {
            return false;
        }
        File::ensureDirectoryExists(dirname($destAbs));
        return copy(Storage::disk($this->disk)->path($storageRelPath), $destAbs);
    }

    protected function ext(string $path): string
    {
        return pathinfo($path, PATHINFO_EXTENSION) ?: 'bin';
    }

    protected function carbon($x): ?Carbon
    {
        try {
            return Carbon::parse($x);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
