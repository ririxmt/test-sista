<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bahasa extends Model
{
    protected $table = 'bahasa';

    // Tabel SISTA lama tidak memiliki kolom created_at/updated_at di tabel bahasa
    public $timestamps = false;

    protected $fillable = [
        'cv_id',
        'bahasa',
        'speaking',
        'reading',
        'writing',
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