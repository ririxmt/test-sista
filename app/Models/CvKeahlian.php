<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvKeahlian extends Model
{
    protected $table = 'cv_keahlian';

    // SISTA: hanya ada created_at, tidak ada updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'cv_id',
        'nama_keahlian',
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