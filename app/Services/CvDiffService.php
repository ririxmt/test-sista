<?php

namespace App\Services;

use App\Models\Cv;

/**
 * Menghitung ringkasan perbedaan antara hasil parsing AI (raw_parsed_json)
 * dengan data CV existing milik user (jika ada).
 *
 * Menggantikan heuristik lama di ApplicantController yang hanya membandingkan
 * COUNT total item — heuristik itu gagal membedakan "CV baru yang sengaja
 * di-ringkas" dari "salah upload file lama". Di sini kita bandingkan
 * per-seksi (pendidikan, pengalaman, sertifikasi, proyek, skills) sehingga
 * admin melihat *apa* yang berubah, bukan cuma angka net.
 */
class CvDiffService
{
    public function diff(array $parsedData, ?Cv $existingCv): array
    {
        if (! $existingCv) {
            return [
                'has_existing_data' => false,
                'sections' => [],
                'summary'  => 'Tidak ada data CV existing untuk user ini — akan dibuat baru.',
            ];
        }

        $sections = [
            'pendidikan' => $this->diffSection(
                $parsedData['pendidikan'] ?? [],
                $existingCv->pendidikanFormal->count()
            ),
            'pengalaman_kerja' => $this->diffSection(
                $parsedData['pengalaman_kerja'] ?? [],
                $existingCv->pengalamanKerja->count()
            ),
            'sertifikasi' => $this->diffSection(
                $parsedData['sertifikasi'] ?? [],
                $existingCv->sertifikasiProfesi->count()
            ),
            'proyek' => $this->diffSection(
                $parsedData['proyek'] ?? [],
                $existingCv->projectRelevan->count()
            ),
            'skills' => $this->diffSection(
                $parsedData['skills'] ?? [],
                $existingCv->cvKeahlian->count()
            ),
        ];

        $sectionsShrunk = array_filter($sections, fn ($s) => $s['delta'] < 0);

        return [
            'has_existing_data' => true,
            'sections' => $sections,
            // Flag hanya dipakai untuk highlight di UI review — TIDAK memblokir proses,
            // karena keputusan akhir tetap ada di admin lewat halaman review.
            'needs_attention' => count($sectionsShrunk) > 0,
            'summary' => count($sectionsShrunk) > 0
                ? 'Seksi berikut memiliki lebih sedikit item dari data existing: ' . implode(', ', array_keys($sectionsShrunk))
                : 'Tidak ada seksi yang berkurang dibanding data existing.',
        ];
    }

    private function diffSection(array $newItems, int $existingCount): array
    {
        $newCount = count($newItems);

        return [
            'existing_count' => $existingCount,
            'new_count'      => $newCount,
            'delta'          => $newCount - $existingCount,
        ];
    }
}
