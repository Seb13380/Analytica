<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'bank_account_id',
        'date',
        'label',
        'normalized_label',
        'amount',
        'type',
        'kind',
        'origin',
        'destination',
        'motif',
        'cheque_number',
        'meta',
        'balance_after',
        'beneficiary_detected',
        'anomaly_score',
        'anomaly_level',
        'rule_flags',
    ];

    protected $casts = [
        'date' => 'date',
        'beneficiary_detected' => 'boolean',
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'rule_flags' => 'array',
        'meta' => 'array',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
