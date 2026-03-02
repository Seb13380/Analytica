<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryAliasOverride extends Model
{
    protected $table = 'beneficiary_alias_overrides';

    protected $fillable = [
        'case_id',
        'normalized_label',
        'identity_key',
        'identity_label',
    ];

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }
}
