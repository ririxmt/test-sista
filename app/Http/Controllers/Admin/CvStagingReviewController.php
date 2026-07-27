<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CvStaging;
use App\Models\User;
use App\Services\CvDistributionService;
use Illuminate\Http\Request;

class CvStagingReviewController extends Controller
{
    public function __construct(protected CvDistributionService $distributionService)
    {
    }

    /**
     * Daftar staging record yang menunggu review, terbaru dulu.
     * Seksi yang "needs_attention" (ada penyusutan jumlah item) tetap
     * ditampilkan bersama yang lain — admin yang memutuskan, bukan sistem.
     */
    public function index()
    {
        $pending = CvStaging::where('review_status', 'pending_review')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.cv_staging.index', compact('pending'));
    }

    public function show(CvStaging $staging)
    {
        return view('admin.cv_staging.show', compact('staging'));
    }

    /**
     * Admin bisa mengoreksi user_id di sini kalau pencocokan otomatis by-email
     * salah/kosong, sebelum approve.
     */
    public function assignUser(Request $request, CvStaging $staging)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        $staging->update(['user_id' => $request->integer('user_id')]);

        return back()->with('success', 'User berhasil dicocokkan ke staging record ini.');
    }

    public function approve(Request $request, CvStaging $staging)
    {
        if (! $staging->isPending()) {
            return back()->withErrors(['error' => 'Staging record ini sudah direview sebelumnya.']);
        }

        if (! $staging->user_id) {
            return back()->withErrors(['error' => 'Cocokkan staging record ke user terlebih dahulu sebelum approve.']);
        }

        $staging->update([
            'review_status' => 'approved',
            'reviewed_by'   => $request->user()->id,
            'review_note'   => $request->input('note'),
        ]);

        // Distribusi ke tabel relasional dijalankan di sini, dibungkus transaksi
        // di dalam CvDistributionService. Jika gagal, exception akan naik dan
        // status staging TIDAK berubah jadi rusak — tetap 'approved' tapi
        // reviewed_at kosong, sehingga mudah diketahui perlu retry.
        $cv = $this->distributionService->distribute($staging->fresh());

        return back()->with([
            'success' => 'Data berhasil didistribusikan ke profil CV user.',
            'cv_id' => $cv->id,
        ]);
    }

    public function reject(Request $request, CvStaging $staging)
    {
        if (! $staging->isPending()) {
            return back()->withErrors(['error' => 'Staging record ini sudah direview sebelumnya.']);
        }

        $request->validate(['note' => 'required|string|max:1000']);

        $staging->update([
            'review_status' => 'rejected',
            'reviewed_by'   => $request->user()->id,
            'reviewed_at'   => now(),
            'review_note'   => $request->input('note'),
        ]);

        return back()->with('success', 'Staging record ditolak dan diarsipkan.');
    }
}
