<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvStaging extends Model
{
    protected $table = 'cv_staging';

    protected $fillable = [
        'user_id',
        'source_file_path',
        'source_file_original_name',
        'source_mime_type',
        'raw_parsed_json',
        'parsed_email',
        'parsed_name',
        'diff_summary',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'cv_id',
        'sista_export_status',
        'sista_export_batch',
        'sista_exported_at',
        'sista_export_hash',
    ];

    protected $casts = [
        'raw_parsed_json'   => 'array',
        'diff_summary'      => 'array',
        'reviewed_at'       => 'datetime',
        'sista_exported_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->review_status === 'pending_review';
    }

    /**
     * Kalau CV yang SUDAH diexport ke SISTA diedit, tandai agar ikut export lagi.
     */
    public static function markChangedAfterExport(int $cvId): void
    {
        static::where('cv_id', $cvId)
            ->where('review_status', 'approved')
            ->where('sista_export_status', 'exported')
            ->update(['sista_export_status' => 'changed_after_export']);
    }
}