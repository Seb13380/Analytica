<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'case_id',
        'bank_name',
        'iban_masked',
        'account_holder',
    ];

    public function caseFile(): BelongsTo
    {
        return $this->belongsTo(CaseFile::class, 'case_id');
    }

    public function statements(): HasMany
    {
        return $this->hasMany(Statement::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
