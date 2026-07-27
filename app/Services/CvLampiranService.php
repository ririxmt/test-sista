<?php

namespace App\Services;

use App\Models\Cv;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CvLampiranService
{
    /**
     * Lampiran SINGLE (satu file per jenis) => [kolom di lampiran_cv, label].
     * Mengikuti skema SISTA.
     */
    public const JENIS = [
        'ktp'         => ['col' => 'ktp_file',    'label' => 'KTP'],
        'npwp'        => ['col' => 'npwp_file',   'label' => 'NPWP'],
        'bukti_pajak' => ['col' => 'bukti_pajak', 'label' => 'Bukti Pajak'],
        'foto'        => ['col' => 'foto',        'label' => 'Foto'],
    ];

    /**
     * Lampiran MULTI (banyak file per jenis) => label. Disimpan sebagai JSON di
     * kolom lampiran_cv.lainnya (tanpa mengubah struktur tabel). Kolom ini tidak
     * ikut ditulis-ulang saat CV diedit, jadi file aman/awet.
     */
    public const JENIS_MULTI = [
        'ijazah' => 'Ijazah',
    ];

    protected string $disk = 'local';
    protected string $folder = 'cv-lampiran';

    /**
     * Sinkronkan lampiran SINGLE (ktp/npwp/bukti_pajak/foto).
     *
     * @param  array<string, UploadedFile|null>  $files  map jenis => file baru
     * @param  array<string, bool>               $remove map jenis => true jika ingin dihapus
     */
    public function sync(Cv $cv, array $files, array $remove = []): void
    {
        $lampiran = $cv->lampiran; // null jika belum ada
        $changes = [];

        foreach (self::JENIS as $jenis => $meta) {
            $col  = $meta['col'];
            $file = $files[$jenis] ?? null;

            if ($file instanceof UploadedFile) {
                $this->deleteFile($lampiran?->{$col});
                $changes[$col] = $file->store($this->folder, $this->disk);
            } elseif (! empty($remove[$jenis])) {
                $this->deleteFile($lampiran?->{$col});
                $changes[$col] = null;
            }
        }

        if (empty($changes)) {
            return;
        }

        $this->lampiranRow($cv)->update($changes);
    }

    /**
     * Daftar path file MULTI untuk sebuah jenis (ijazah/sertifikat).
     *
     * @return array<int, string>
     */
    public function multiFiles(Cv $cv, string $jenis): array
    {
        $store = $cv->lampiran?->lainnya ?? [];

        return array_values($store[$jenis] ?? []);
    }

    /**
     * Sinkronkan lampiran MULTI: tambah file baru & hapus yang ditandai.
     *
     * @param  array<string, array<int, UploadedFile|null>>  $newFiles  jenis => daftar file baru
     * @param  array<string, array<int, int|string>>         $remove    jenis => daftar index yang dihapus
     */
    public function syncMulti(Cv $cv, array $newFiles, array $remove = []): void
    {
        $hasChange = false;
        foreach (self::JENIS_MULTI as $jenis => $label) {
            if (! empty(array_filter($newFiles[$jenis] ?? [])) || ! empty(array_filter($remove[$jenis] ?? []))) {
                $hasChange = true;
                break;
            }
        }
        if (! $hasChange) {
            return;
        }

        $row   = $this->lampiranRow($cv);
        $store = $row->lainnya ?? [];

        foreach (array_keys(self::JENIS_MULTI) as $jenis) {
            $list = $store[$jenis] ?? [];

            // Hapus berdasarkan index yang ditandai
            foreach ($remove[$jenis] ?? [] as $idx) {
                if (isset($list[$idx])) {
                    $this->deleteFile($list[$idx]);
                    unset($list[$idx]);
                }
            }
            $list = array_values($list);

            // Tambah file baru
            foreach ($newFiles[$jenis] ?? [] as $file) {
                if ($file instanceof UploadedFile) {
                    $list[] = $file->store($this->folder, $this->disk);
                }
            }

            $store[$jenis] = array_values($list);
        }

        $row->lainnya = $store;
        $row->save();
    }

    /**
     * Hapus SEMUA file fisik milik sebuah CV (dipakai saat CV dihapus).
     */
    public function purgeFiles(Cv $cv): void
    {
        $lampiran = $cv->lampiran;
        if (! $lampiran) {
            return;
        }
        foreach (self::JENIS as $meta) {
            $this->deleteFile($lampiran->{$meta['col']});
        }
        foreach ((array) ($lampiran->lainnya ?? []) as $paths) {
            foreach ((array) $paths as $path) {
                $this->deleteFile($path);
            }
        }
    }

    /**
     * Ambil (atau buat) baris lampiran_cv milik CV — dijamin satu baris,
     * dan relasi di-refresh agar pemanggilan berikutnya konsisten.
     */
    protected function lampiranRow(Cv $cv)
    {
        $row = $cv->lampiran()->firstOrCreate([]);
        $cv->setRelation('lampiran', $row);

        return $row;
    }

    /**
     * Tempelkan file upload per-baris ke dalam array baris (mis. untuk sertifikasi).
     * $uploaded diindeks sama dengan baris form; hasil disimpan ke kolom $col tiap baris.
     *
     * @param  array  $rows      baris data (indeks sama dgn form)
     * @param  mixed  $uploaded  array UploadedFile per index
     */
    public function attachRowFiles(array $rows, $uploaded, string $col): array
    {
        foreach ((array) $uploaded as $i => $file) {
            if ($file instanceof UploadedFile && isset($rows[$i]) && is_array($rows[$i])) {
                $rows[$i][$col] = $file->store($this->folder, $this->disk);
            }
        }

        return $rows;
    }

    public function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk($this->disk)->delete($path);
        }
    }
}
