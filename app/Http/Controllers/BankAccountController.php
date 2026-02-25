<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CaseFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BankAccountController extends Controller
{
    public function store(Request $request, CaseFile $case)
    {
        Gate::authorize('update', $case);

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'iban_masked' => ['nullable', 'string', 'max:64'],
            'account_holder' => ['nullable', 'string', 'max:255'],
        ]);

        BankAccount::create([
            'case_id' => $case->getKey(),
            'bank_name' => $validated['bank_name'],
            'iban_masked' => $validated['iban_masked'] ?? null,
            'account_holder' => $validated['account_holder'] ?? null,
        ]);

        return redirect()->route('cases.show', $case);
    }
}
