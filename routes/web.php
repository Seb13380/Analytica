<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CaseController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AiAssistantController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('cases.index');
    }

    return view('landing');
});

Route::get('/dashboard', function () {
    return redirect()->route('cases.index');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'accesslog'])->group(function () {
    Route::get('/cases', [CaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [CaseController::class, 'create'])->name('cases.create');
    Route::post('/cases', [CaseController::class, 'store'])->name('cases.store');
    Route::get('/cases/{case}', [CaseController::class, 'show'])->name('cases.show');
    Route::get('/cases/{case}/transactions/export', [CaseController::class, 'exportTransactions'])->name('cases.transactions.export');
    Route::patch('/cases/{case}/details', [CaseController::class, 'updateDetails'])->name('cases.update-details');
    Route::post('/cases/{case}/analyze', [CaseController::class, 'analyze'])->name('cases.analyze');
    Route::post('/cases/{case}/beneficiary-overrides', [CaseController::class, 'storeBeneficiaryOverrides'])->name('cases.beneficiary-overrides');
    Route::post('/cases/{case}/ai', [AiAssistantController::class, 'analyze'])->name('cases.ai');

    Route::post('/cases/{case}/reports', [ReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');

    Route::post('/cases/{case}/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::post('/bank-accounts/{bankAccount}/statements', [StatementController::class, 'store'])->name('statements.store');
    Route::delete('/statements/{statement}', [StatementController::class, 'destroy'])->name('statements.destroy');
    Route::get('/cases/{case}/statements/{statement}/download', [CaseController::class, 'downloadStatement'])->name('statements.download');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
