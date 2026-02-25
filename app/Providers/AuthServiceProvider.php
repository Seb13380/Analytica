<?php

namespace App\Providers;

use App\Models\BankAccount;
use App\Models\CaseFile;
use App\Models\Statement;
use App\Policies\BankAccountPolicy;
use App\Policies\CaseFilePolicy;
use App\Policies\StatementPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        CaseFile::class => CaseFilePolicy::class,
        BankAccount::class => BankAccountPolicy::class,
        Statement::class => StatementPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
