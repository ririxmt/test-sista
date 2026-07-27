<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengalamanKerja extends Model
{
    protected $table = 'pengalaman_kerja';

    protected $fillable = [
        'cv_id',
        'tahun',
        'nama_kegiatan',
        'uraian_proyek',
        'lokasi',
        'negara',
        'pemberi_pekerjaan',
        'perusahaan',
        'pelaksana_proyek',
        'uraian_tugas',
        'waktu_mulai',
        'waktu_akhir',
        'durasi',
        'waktu_legacy',
        'posisi',
        'status_kepegawaian',
        'referensi_file',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }
}