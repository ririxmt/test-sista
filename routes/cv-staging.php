<?php

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\CvController;
use App\Http\Controllers\CvReviewController;
use App\Http\Controllers\SistaExportController;
use Illuminate\Support\Facades\Route;

// Semua halaman pengelolaan CV wajib login. Kalau belum login -> diarahkan ke /login.
Route::middleware('auth')->group(function () {

    Route::get('/applicants/upload', [ApplicantController::class, 'showForm'])
        ->name('applicants.upload.form');

    Route::post('/applicants/upload', [ApplicantController::class, 'upload'])
        ->name('applicants.upload');

    // Form editable hasil parsing AI (sebelum disimpan ke tabel final)
    Route::get('/cv-staging/{staging}/edit', [CvReviewController::class, 'edit'])
        ->name('cv-staging.edit');

    Route::put('/cv-staging/{staging}', [CvReviewController::class, 'update'])
        ->name('cv-staging.update');

    // Data final yang sudah tersimpan
    Route::get('/cv', [CvController::class, 'index'])->name('cv.index');
    Route::get('/cv/{cv}', [CvController::class, 'show'])->name('cv.show');
    Route::get('/cv/{cv}/edit', [CvController::class, 'edit'])->name('cv.edit');
    Route::put('/cv/{cv}', [CvController::class, 'update'])->name('cv.update');
    Route::delete('/cv/{cv}', [CvController::class, 'destroy'])->name('cv.destroy');

    // Lihat / unduh lampiran single (ktp, npwp, bukti_pajak, foto)
    Route::get('/cv/{cv}/lampiran/{jenis}', [CvController::class, 'lampiran'])->name('cv.lampiran');

    // Lihat / unduh lampiran multi (ijazah) berdasar index
    Route::get('/cv/{cv}/lampiran-multi/{jenis}/{index}', [CvController::class, 'lampiranMulti'])->name('cv.lampiran-multi');

    // Lihat / unduh file sertifikat per-baris sertifikasi
    Route::get('/cv/sertifikat/{sertifikasi}', [CvController::class, 'sertifikatFile'])->name('cv.sertifikat-file');

    // Export data ke SISTA (paket ZIP)
    Route::get('/admin/sista-export', [SistaExportController::class, 'index'])->name('sista-export.index');
    Route::post('/admin/sista-export', [SistaExportController::class, 'export'])->name('sista-export.export');

});
