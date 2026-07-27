<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendidikanNonformal extends Model
{
    protected $table = 'pendidikan_nonformal';

    public $timestamps = false;

    protected $fillable = [
        'cv_id',
        'nama_pelatihan',
        'penyelenggara',
        'tahun',
        'sertifikat_file',
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