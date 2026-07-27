<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResumeParserService
{
    public function parseResume($fileContent, string $mimeType, ?string $fileName = null): ?array
    {
        $apiKey = config('services.gemini.key');
        $model  = config('services.gemini.model', 'gemini-1.5-flash');

        if (empty($apiKey)) {
            Log::error('Gemini: GEMINI_API_KEY masih kosong di .env');
            return null;
        }

        $base64File = base64_encode($fileContent);

        // Prompt disesuaikan 100% dengan kolom database sista.sql
        $prompt = "Kamu adalah sistem ATS Parser profesional yang sangat teliti. Tugasmu adalah menganalisis dokumen/gambar CV yang dilampirkan.\n\n" .
                  "PENTING: Ekstrak SELURUH riwayat biodata, pengalaman kerja, proyek, pendidikan, pelatihan, sertifikasi, dan bahasa TANPA ADA YANG TERLEWAT. Jangan memotong daftar jika jumlahnya banyak.\n\n" .
                  "Soal EMAIL: ambil email pemilik CV. Email kantor adalah 'office@lapi-itb.com' — email ini JANGAN diambil. Ambil email pribadinya (gmail/yahoo/dll). Kosongkan hanya jika tidak ada email lain.\n\n" .
                  "Soal ALAMAT/DOMISILI: alamat kantor 'PT LAPI ITB, Jalan Ganesa 15B, Bandung 40132, Indonesia' JANGAN diambil (itu alamat perusahaan, bukan alamat pelamar). Ambil hanya alamat/domisili PRIBADI pemilik CV; kosongkan jika tidak ada.\n\n" .
                  "Soal GELAR: PISAHKAN dengan benar. Gelar yang berada DI DEPAN nama (contoh: Prof., Dr., Dr.-Ing., Ir., Drs., Dra., dr.) masukkan ke 'gelar_depan'. " .
                  "Gelar yang berada DI BELAKANG nama (contoh: S.T., M.T., M.Sc., Ph.D., M.A.B., M.M., S.Kom.) masukkan ke 'gelar_belakang'. " .
                  "PENTING: SAPAAN / HONORIFIK BUKAN GELAR. JANGAN masukkan kata sapaan berikut ke 'gelar_depan' maupun 'gelar_belakang' (abaikan sepenuhnya): " .
                  "Mr, Mr., Mrs, Mrs., Ms, Ms., Miss, Tuan, Nyonya, Nona, Bapak, Bpk, Ibu, Sdr, Sdri, H, H., Haji, Hj, Hj., Hajjah. " .
                  "Hanya gelar akademik/profesional yang boleh mengisi kolom gelar. " .
                  "JANGAN tertukar: jangan menaruh gelar depan ke kolom belakang atau sebaliknya, dan jangan memasukkan gelar ke dalam field 'nama'.\n\n" .
                  "Untuk PENGALAMAN KERJA, isi tiap entri: 'nama_kegiatan' = nama proyek/pekerjaan/kegiatan (judul utama pengalaman); 'pemberi_pekerjaan' = perusahaan/klien/instansi pemberi kerja; 'posisi' = jabatan/peran; 'tahun' = tahun (YYYY); 'lokasi' = lokasi; 'durasi' = periode (mis. '2018 - 2020'); 'deskripsi' = uraian tugas. WAJIB isi 'nama_kegiatan' dan 'pemberi_pekerjaan' bila ada.\n\n" .
                  ($fileName
                      ? "PETUNJUK NAMA FILE: Dokumen ini diunggah dengan nama file \"{$fileName}\". " .
                        "Nama file SERING memuat gelar pemilik CV, terutama GELAR BELAKANG yang ditulis setelah nama dan dipisah koma. " .
                        "Pola umum nama file: 'CV_<Nama Lengkap>, <gelar belakang>_<jabatan/keterangan lain>'. " .
                        "Contoh: untuk nama file 'CV_Mandra Lazuardi Kitri, S.T., M.B.A._Senior Associate' -> nama='Mandra Lazuardi Kitri', gelar_belakang='S.T., M.B.A.' (ambil SEMUA gelar setelah koma sampai sebelum pemisah berikutnya seperti '_' atau '-'), dan 'Senior Associate' adalah jabatan (BUKAN gelar). " .
                        "Kumpulkan SEMUA potongan gelar belakang (mis. 'S.T., M.B.A.', 'S.Kom., M.T.', 'Ph.D.') secara lengkap, jangan hanya sebagian. " .
                        "Gunakan nama file HANYA untuk melengkapi 'gelar_depan'/'gelar_belakang' bila gelar tersebut TIDAK ditemukan di dalam isi dokumen, dan HANYA jika potongannya benar-benar tampak seperti gelar akademik/profesional yang valid. " .
                        "PENTING: JANGAN pernah mengubah, mengganti, atau mengambil field 'nama' dari nama file. Field 'nama' WAJIB murni dari isi dokumen CV. Abaikan nama file jika isinya asal/tidak relevan.\n\n"
                      : "") .
                  "Susun semua informasi ke dalam format JSON murni dengan struktur wajib seperti ini:\n" .
                  "{\n" .
                  "    \"nama\": \"\",\n" .
                  "    \"gelar_depan\": \"\",\n" .
                  "    \"gelar_belakang\": \"\",\n" .
                  "    \"email\": \"\",\n" .
                  "    \"telepon\": \"\",\n" .
                  "    \"alamat\": \"\",\n" .
                  "    \"tempat_lahir\": \"\",\n" .
                  "    \"tanggal_lahir\": \"YYYY-MM-DD\",\n" .
                  "    \"kewarganegaraan\": \"Indonesia\",\n" .
                  "    \"domisili_kota\": \"\",\n" .
                  "    \"domisili_negara\": \"Indonesia\",\n" .
                  "    \"posisi\": \"\",\n" .
                  "    \"perusahaan\": \"\",\n" .
                  "    \"ringkasan_profil\": \"\",\n" .
                  "    \"pendidikan\": [{\"jenjang\": \"\", \"institusi\": \"\", \"jurusan\": \"\", \"tahun_lulus\": \"\"}],\n" .
                  "    \"pelatihan\": [{\"nama_pelatihan\": \"\", \"penyelenggara\": \"\", \"tahun\": \"\"}],\n" .
                  "    \"pengalaman_kerja\": [{\"nama_kegiatan\": \"\", \"pemberi_pekerjaan\": \"\", \"posisi\": \"\", \"tahun\": \"\", \"lokasi\": \"\", \"durasi\": \"\", \"deskripsi\": \"\"}],\n" .
                  "    \"skills\": [],\n" .
                  "    \"proyek\": [{\"nama_proyek\": \"\", \"klien\": \"\", \"tahun\": \"\"}],\n" .
                  "    \"sertifikasi\": [{\"nama_sertifikasi\": \"\", \"penerbit\": \"\", \"tahun\": \"\"}],\n" .
                  "    \"bahasa\": [{\"bahasa\": \"\", \"speaking\": \"Baik\", \"reading\": \"Baik\", \"writing\": \"Baik\"}]\n" .
                  "}.\n\n" .
                  "Balas HANYA dengan kode JSON murni tanpa markdown, tanpa backticks ```json, dan tanpa penjelasan teks apa pun.";

        // URL Endpoint Gemini generateContent
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64File,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
        ];

        // Menerapkan mekanisme retry untuk menangani timeout/error sementara dari AI
        $maxAttempts = 3;
        $transientStatuses = [429, 500, 502, 503, 504];
        $response = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                    ->connectTimeout(15)
                    ->timeout(180)
                    ->post($url, $payload);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::warning("Gemini gagal terhubung (percobaan {$attempt}/{$maxAttempts})", ['message' => $e->getMessage()]);
                if ($attempt < $maxAttempts) {
                    sleep(2 * $attempt);
                    continue;
                }
                Log::error('Gemini gagal terhubung setelah retry.', ['message' => $e->getMessage()]);
                return null;
            }

            if (in_array($response->status(), $transientStatuses, true) && $attempt < $maxAttempts) {
                // Hormati waktu tunggu yang diminta Gemini (retryDelay pada 429 rate-limit),
                // plus buffer 3 detik. Kalau tidak ada, pakai backoff default. Maks 60 detik.
                $retryAfter = 5 * $attempt;
                foreach ((array) $response->json('error.details', []) as $detail) {
                    if (isset($detail['retryDelay']) && preg_match('/(\d+)/', $detail['retryDelay'], $mm)) {
                        $retryAfter = max($retryAfter, (int) $mm[1] + 3);
                    }
                }
                $retryAfter = min($retryAfter, 60);
                Log::warning("Gemini balas status {$response->status()} (percobaan {$attempt}/{$maxAttempts}), tunggu {$retryAfter}s lalu retry...", [
                    'message' => $response->json('error.message') ?? $response->body(),
                ]);
                sleep($retryAfter);
                continue;
            }

            break;
        }

        if ($response->failed()) {
            Log::error('Gemini API menolak request', [
                'status'  => $response->status(),
                'message' => $response->json('error.message') ?? $response->body(),
            ]);
            return null;
        }

        $aiText = $response->json('candidates.0.content.parts.0.text');

        if ($aiText === null) {
            Log::error('Gemini merespon tanpa teks', [
                'finishReason' => $response->json('candidates.0.finishReason'),
                'body'         => $response->body(),
            ]);
            return null;
        }

        // Pembersihan jika AI tidak sengaja menyertakan sintaks markdown
        $cleanJson = trim($aiText);
        if (preg_match('/^```(?:json)?(.*)```$/s', $cleanJson, $matches)) {
            $cleanJson = trim($matches[1]);
        }

        $data = json_decode($cleanJson, true);

        if (! is_array($data)) {
            Log::error('Gemini membalas JSON tidak valid', [
                'json_error' => json_last_error_msg(),
                'raw'        => $cleanJson,
            ]);
            return null;
        }

        return $data;
    }
}