<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class AnalysisResult extends Model
{
    protected $fillable = [
        'case_id',
        'generated_at',
        'global_score',
        'total_transactions',
        'total_flagged',
        'total_flagged_amount',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'total_flagged_amount' => 'decimal:2',
    ];

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }
}
