<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SertifikasiProfesi extends Model
{
    protected $table = 'sertifikasi_profesi';

    public $timestamps = false;

    protected $fillable = [
        'cv_id',
        'nama',
        'penerbit',
        'tahun',
        'file_sertifikat',
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