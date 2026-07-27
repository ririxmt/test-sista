<?php

namespace App\Services;

/**
 * Menyeragamkan kapitalisasi field bertipe "nama" (nama orang, institusi,
 * perusahaan, jabatan, proyek, dsb) ke Title Case dengan aturan rumah:
 *
 *  - Kata sambung (dan, di, untuk, of, and, ...) tetap huruf kecil,
 *    kecuali jika jadi kata pertama.
 *  - Akronim dipertahankan/dikanonikkan (PT, GRK, IoT, ISO, BMS, ...).
 *  - Token yang sudah campur-kapital (IoT, McKinsey) atau semua kapital
 *    (GRK, PGN) atau mengandung angka (15B, 1MWH) dibiarkan apa adanya.
 *  - Gelar & singkatan bertitik (Dr.-Ing., S.T., M.T.) tidak dirusak.
 *  - Diakritik (ä, é, ...) terjaga karena seluruh operasi memakai mb_* UTF-8.
 *
 * CATATAN: tidak ada standar ISO untuk kapitalisasi nama; ini house-style.
 * ISO yang relevan hanya soal encoding (ISO/IEC 10646 / Unicode) yang
 * otomatis terpenuhi selama memakai mb_* + kolom utf8mb4.
 */
class NameNormalizer
{
    /** Kata sambung yang tetap huruf kecil (kecuali kata pertama). */
    private const LOWERCASE_WORDS = [
        // Indonesia
        'dan', 'di', 'ke', 'dari', 'untuk', 'pada', 'yang', 'atau', 'dengan',
        'serta', 'bagi', 'oleh', 'dalam', 'demi', 'per',
        // English
        'and', 'or', 'of', 'in', 'on', 'for', 'to', 'a', 'an', 'the', 'at',
        'by', 'vs', 'de', 'van',
    ];

    /** Akronim/istilah dengan bentuk kanonik tetap. */
    private const ACRONYMS = [
        'pt' => 'PT', 'cv' => 'CV', 'tbk' => 'Tbk', 'iot' => 'IoT',
        'grk' => 'GRK', 'bms' => 'BMS', 'ai' => 'AI', 'ml' => 'ML',
        'iso' => 'ISO', 'pln' => 'PLN', 'itb' => 'ITB', 'adb' => 'ADB',
        'pgn' => 'PGN', 'bnsp' => 'BNSP', 'lpdp' => 'LPDP', 'ghg' => 'GHG',
        'plt' => 'PLT', 'plts' => 'PLTS', 'sbess' => 'SBESS', 'mdms' => 'MDMS',
        'bdo' => 'BDO', 'hev' => 'HEV', 'icon' => 'ICON', 'rti' => 'RTI',
    ];

    public function titleCase(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return '';
        }

        // Kalau SELURUH nilai kapital tanpa satu pun huruf kecil (mis.
        // "BUDI SANTOSO"), sinyal "semua kapital = akronim" tidak berlaku —
        // perlakukan sebagai teks yang diketik caps-lock, jadi tiap kata
        // di-Title-Case (akronim terdaftar tetap dikanonikkan).
        $shout = preg_match('/\p{L}/u', $value) && ! preg_match('/\p{Ll}/u', $value);

        $words = explode(' ', $value);
        $out = [];
        foreach ($words as $idx => $word) {
            $out[] = $this->castToken($word, $idx === 0, (bool) $shout);
        }

        $result = implode(' ', $out);

        // Seragamkan "PT." -> "PT" (badan usaha ditulis tanpa titik)
        $result = (string) preg_replace('/\bPT\.\s*/u', 'PT ', $result);

        return trim($result);
    }

    /** Pecah pada tanda hubung, proses tiap bagian ("academic-industrial"). */
    private function castToken(string $token, bool $isFirst, bool $shout): string
    {
        if (mb_strlen($token) > 1 && str_contains($token, '-')) {
            $parts = explode('-', $token);
            $cast = [];
            foreach ($parts as $k => $part) {
                $cast[] = $part === '' ? '' : $this->castCore($part, $isFirst && $k === 0, $shout);
            }
            return implode('-', $cast);
        }

        return $this->castCore($token, $isFirst, $shout);
    }

    /** Proses satu kata: pisahkan tanda baca pembungkus, olah intinya saja. */
    private function castCore(string $token, bool $isFirst, bool $shout): string
    {
        if (! preg_match('/^([^\p{L}\p{N}]*)(.*?)([^\p{L}\p{N}]*)$/u', $token, $m)) {
            return $token;
        }

        [, $prefix, $core, $suffix] = $m;
        if ($core === '') {
            return $token;
        }

        $letters  = (string) preg_replace('/[^\p{L}]/u', '', $core);
        $lowerKey = mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]/u', '', $core), 'UTF-8');
        $hasUpper = (bool) preg_match('/\p{Lu}/u', $core);
        $hasLower = (bool) preg_match('/\p{Ll}/u', $core);

        if ($lowerKey !== '' && isset(self::ACRONYMS[$lowerKey])) {
            $coreOut = self::ACRONYMS[$lowerKey];                       // akronim dikenal
        } elseif (! $isFirst && in_array($lowerKey, self::LOWERCASE_WORDS, true)) {
            $coreOut = mb_strtolower($core, 'UTF-8');                   // kata sambung
        } elseif (preg_match('/\p{N}/u', $core)) {
            $coreOut = $core;                                          // ada angka -> biarkan
        } elseif ($hasUpper && $hasLower) {
            $coreOut = $core;                                          // campur-kapital -> biarkan
        } elseif ($hasUpper && ! $hasLower && mb_strlen($letters) > 1 && ! $shout) {
            $coreOut = $core;                                          // semua kapital (di string campuran) -> biarkan
        } elseif ($shout && preg_match('/\p{L}\.\p{L}/u', $core)) {
            // Gelar/singkatan bertitik saat caps-lock (S.KOM -> S.Kom, S.T -> S.T)
            $coreOut = implode('.', array_map(fn ($s) => $this->ucfirstMb($s), explode('.', $core)));
        } else {
            $coreOut = $this->ucfirstMb($core);                         // default -> Title Case
        }

        return $prefix . $coreOut . $suffix;
    }

    /** Huruf pertama kapital, sisanya kecil — aman untuk diakritik (mb_). */
    private function ucfirstMb(string $s): string
    {
        if ($s === '') {
            return $s;
        }
        $lc = mb_strtolower($s, 'UTF-8');
        return mb_strtoupper(mb_substr($lc, 0, 1), 'UTF-8') . mb_substr($lc, 1);
    }
}
