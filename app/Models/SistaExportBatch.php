<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SistaExportBatch extends Model
{
    protected $table = 'sista_export_batches';

    protected $fillable = [
        'batch_code',
        'exported_at',
        'exported_by',
        'total_cv',
        'total_files',
        'zip_filename',
        'status',
        'error_message',
    ];

    protected $casts = [
        'exported_at' => 'datetime',
    ];
}
