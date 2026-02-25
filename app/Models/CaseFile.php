<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class CaseFile extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'organization_id',
        'user_id',
        'title',
        'deceased_name',
        'death_date',
        'analysis_period_start',
        'analysis_period_end',
        'status',
        'global_score',
        'expires_at',
    ];

    protected $casts = [
        'death_date' => 'date',
        'analysis_period_start' => 'date',
        'analysis_period_end' => 'date',
        'expires_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'case_id');
    }

    public function analysisResults(): HasMany
    {
        return $this->hasMany(AnalysisResult::class, 'case_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'case_id');
    }
}
