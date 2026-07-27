<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendidikanFormal extends Model
{
    protected $table = 'pendidikan_formal';

    public $timestamps = false;

    protected $fillable = [
        'cv_id',
        'tingkat',
        'institusi',
        'jurusan',
        'tahun_lulus',
        'ijazah_file',
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