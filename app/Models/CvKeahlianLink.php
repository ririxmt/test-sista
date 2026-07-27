<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvKeahlianLink extends Model
{
    protected $table = 'cv_keahlian_link';

    public $timestamps = false;

    protected $fillable = [
        'keahlian_id',
        'target_id',
        'target_type',
        'is_visible_section',
    ];

    protected $casts = [
        'is_visible_section' => 'boolean',
    ];
}
