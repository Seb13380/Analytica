<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'case_id',
        'file_path',
        'hash_integrity',
        'original_filename',
        'mime_type',
        'size_bytes',
        'encryption_alg',
        'encryption_meta',
        'generated_at',
        'version',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'encryption_meta' => 'array',
    ];

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }
}
