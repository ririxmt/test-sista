<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LampiranCv extends Model
{
    protected $table = 'lampiran_cv';

    public $timestamps = false;

    protected $fillable = [
        'cv_id',
        'ktp_file',
        'is_visible_ktp_file',
        'npwp_file',
        'is_visible_npwp_file',
        'bukti_pajak',
        'is_visible_bukti_pajak',
        'foto',
        'is_visible_foto',
        'lainnya',
        'is_visible_lainnya',
    ];

    protected $casts = [
        'is_visible_ktp_file'    => 'boolean',
        'is_visible_npwp_file'   => 'boolean',
        'is_visible_bukti_pajak' => 'boolean',
        'is_visible_foto'        => 'boolean',
        'is_visible_lainnya'     => 'boolean',
        // Kolom TEXT `lainnya` dipakai menampung daftar file Ijazah & Sertifikat (JSON)
        'lainnya'                => 'array',
    ];

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }
}
