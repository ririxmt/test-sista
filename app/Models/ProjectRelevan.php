<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRelevan extends Model
{
    protected $table = 'project_relevan';

    // SISTA: project_relevan tidak punya is_visible maupun timestamps
    public $timestamps = false;

    protected $fillable = [
        'cv_id',
        'nama_project',
        'tahun',
        'lokasi',
        'klien',
        'fitur_proyek',
        'posisi',
        'aktivitas',
    ];

    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class, 'cv_id');
    }
}