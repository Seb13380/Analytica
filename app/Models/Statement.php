<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Statement extends Model
{
    protected $fillable = [
        'bank_account_id',
        'file_path',
        'hash_integrity',
        'imported_at',
        'original_filename',
        'mime_type',
        'size_bytes',
        'encryption_alg',
        'encryption_meta',
        'import_status',
        'transactions_imported',
        'ocr_used',
        'import_error',
        'extracted_text',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'encryption_meta' => 'array',
        'ocr_used' => 'boolean',
        'extracted_text' => 'encrypted',
    ];

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
