<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cv extends Model
{
    protected $table = 'cv';

    protected $fillable = [
        'user_id',
        'nama',
        'gelar_depan',
        'gelar_belakang',
        'posisi',
        'perusahaan',
        'tempat_lahir',
        'tanggal_lahir',
        'kewarganegaraan',
        'status_kepegawaian',
        'pernah_di_wb',
        'employment_from',
        'employment_to',
        'employer',
        'employment_position',
        'employment_desc',
        'domisili_negara',
        'domisili_kota',
    ];

    protected $casts = [
        'tanggal_lahir'   => 'date',
        'employment_from' => 'date',
        'employment_to'   => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pendidikanFormal(): HasMany
    {
        return $this->hasMany(PendidikanFormal::class, 'cv_id');
    }

    public function pendidikanNonformal(): HasMany
    {
        return $this->hasMany(PendidikanNonformal::class, 'cv_id');
    }

    public function pengalamanKerja(): HasMany
    {
        return $this->hasMany(PengalamanKerja::class, 'cv_id');
    }

    public function sertifikasiProfesi(): HasMany
    {
        return $this->hasMany(SertifikasiProfesi::class, 'cv_id');
    }

    public function projectRelevan(): HasMany
    {
        return $this->hasMany(ProjectRelevan::class, 'cv_id');
    }

    public function cvKeahlian(): HasMany
    {
        return $this->hasMany(CvKeahlian::class, 'cv_id');
    }

    public function bahasa(): HasMany
    {
        return $this->hasMany(Bahasa::class, 'cv_id');
    }

    public function lampiran(): HasOne
    {
        return $this->hasOne(LampiranCv::class, 'cv_id');
    }
}