<?php

namespace App\Http\Controllers;

use App\Jobs\ImportStatementJob;
use App\Models\BankAccount;
use App\Models\Statement;
use App\Services\EncryptedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StatementController extends Controller
{
    public function store(Request $request, BankAccount $bankAccount, EncryptedFileStorage $storage)
    {
        Gate::authorize('update', $bankAccount->caseFile);

        $validated = $request->validate([
            'statement' => ['required', 'file', 'max:51200', 'mimes:pdf,csv,txt'],
        ]);

        $case = $bankAccount->caseFile;

        $payload = $storage->storeUploadedStatement($validated['statement'], $case);

        $statement = Statement::create([
            'bank_account_id' => $bankAccount->getKey(),
            'file_path' => $payload['file_path'],
            'hash_integrity' => $payload['hash_integrity'],
            'imported_at' => now(),
            'original_filename' => $payload['original_filename'],
            'mime_type' => $payload['mime_type'],
            'size_bytes' => $payload['size_bytes'],
            'encryption_alg' => $payload['encryption_alg'],
            'encryption_meta' => $payload['encryption_meta'],
            'import_status' => 'queued',
        ]);

        ImportStatementJob::dispatch($statement->getKey());

        return redirect()->route('cases.show', $case)->with('status', 'Relevé envoyé; import en cours.');
    }

    public function destroy(Request $request, Statement $statement, EncryptedFileStorage $storage)
    {
        $statement->loadMissing(['bankAccount.caseFile']);
        Gate::authorize('delete', $statement);

        $case = $statement->bankAccount->caseFile;

        // Delete encrypted object (best-effort), then delete DB row.
        try {
            $storage->deleteFile((string) $statement->file_path);
        } catch (\Throwable) {
            // Ignore storage deletion failures in MVP.
        }

        $statement->delete();

        return redirect()->route('cases.show', $case)->with('status', 'Document supprimé.');
    }
}
