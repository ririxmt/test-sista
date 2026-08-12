<?php

namespace App\Http\Controllers;

use App\Models\CvStaging;
use App\Services\CvDiffService;
use App\Services\ResumeParserService;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    public function __construct(
        protected ResumeParserService $parserService,
        protected CvDiffService $diffService,
    ) {
    }

    /**
     * Tampilkan form upload CV.
     */
    public function showForm()
    {
        return view('applicants.upload');
    }

    /**
     * Upload CV -> AI parse -> simpan sebagai cv_staging (pending_review).
     */
    public function upload(Request $request)
    {
        ini_set('max_execution_time', 300);
        set_time_limit(300);

        $request->validate([
            // max dalam KB: 512000 KB = 500 MB. Sengaja dibuat lebih rendah dari
            // upload_max_filesize PHP (512M) supaya pesan validasi yang ramah muncul
            // duluan, bukan pesan teknis "failed to upload" dari PHP.
            'resume' => 'required|mimes:pdf,jpeg,png,jpg|max:512000',
        ]);

        $file = $request->file('resume');
        $fileContent = file_get_contents($file->getRealPath());
        $mimeType = $file->getMimeType();

        $parsedData = $this->parserService->parseResume($fileContent, $mimeType, $file->getClientOriginalName());

        if (! $parsedData) {
            return redirect()->back()->withErrors([
                'error' => 'AI gagal memproses dokumen. Cek storage/logs/laravel.log untuk detail errornya.',
            ]);
        }

        $storedPath = $file->store('cv-uploads', 'local');

        // Kebijakan: setiap upload = talent baru. Tidak melakukan auto-match
        // berdasarkan email, karena email perusahaan (mis. office@lapi-itb.com)
        // sering muncul di banyak CV berbeda dan menyebabkan data saling menimpa.
        $existingCv = null;

        $diff = $this->diffService->diff($parsedData, $existingCv);

        $staging = CvStaging::create([
            'user_id'                   => null,
            'source_file_path'          => $storedPath,
            'source_file_original_name' => $file->getClientOriginalName(),
            'source_mime_type'          => $mimeType,
            'raw_parsed_json'           => $parsedData,
            'parsed_email'              => $parsedData['email'] ?? null,
            'parsed_name'               => $parsedData['nama'] ?? null,
            'diff_summary'              => $diff,
            'review_status'             => 'pending_review',
        ]);

        return redirect()->route('cv-staging.edit', $staging)
            ->with('success', "CV berhasil diparse (staging #{$staging->id}). Silakan periksa & koreksi sebelum disimpan.");
    }
}
