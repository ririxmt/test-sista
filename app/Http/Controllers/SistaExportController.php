<?php

namespace App\Http\Controllers;

use App\Models\CvStaging;
use App\Models\SistaExportBatch;
use App\Services\SistaExportService;
use Illuminate\Http\Request;

class SistaExportController extends Controller
{
    public function __construct(protected SistaExportService $exportService)
    {
    }

    /**
     * Kandidat export = staging approved, punya cv_id, dan belum diexport
     * atau berubah setelah export.
     */
    private function candidateQuery()
    {
        return CvStaging::query()
            ->where('review_status', 'approved')
            ->whereNotNull('cv_id')
            ->whereIn('sista_export_status', ['not_exported', 'changed_after_export'])
            ->orderBy('id');
    }

    public function index()
    {
        $count = $this->candidateQuery()->count();

        // parsed_email sering kosong (CV tanpa email pribadi). Yang benar-benar ikut
        // ke ZIP adalah users.email — termasuk email dummy @dummy.sista yang dibuat
        // otomatis saat approve. Muat relasi user supaya preview = isi export.
        // user_id wajib ikut di-select, kalau tidak relasinya tak bisa dimuat.
        $rows = $this->candidateQuery()->with('user:id,email')->paginate(50, [
            'id', 'cv_id', 'user_id', 'parsed_name', 'parsed_email', 'sista_export_status',
        ]);

        $batches = SistaExportBatch::orderByDesc('id')->limit(10)->get();

        return view('admin.sista-export.index', [
            'count'   => $count,
            'rows'    => $rows,
            'batches' => $batches,
        ]);
    }

    public function export(Request $request)
    {
        try {
            $result = $this->exportService->export(optional($request->user())->id);
        } catch (\RuntimeException $e) {
            return back()->with('info', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Export gagal: ' . $e->getMessage());
        }

        $pesan = "Export berhasil: {$result['total_cv']} CV, {$result['total_files']} file.";
        if ($result['missing_files'] > 0) {
            $pesan .= " ({$result['missing_files']} file lampiran tidak ditemukan & dilewati.)";
        }
        session()->flash('success', $pesan);

        return response()->download($result['zip_path'], $result['zip_name']);
    }
}
