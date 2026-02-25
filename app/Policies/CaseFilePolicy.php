<?php

namespace App\Policies;

use App\Models\CaseFile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CaseFilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CaseFile $caseFile): bool
    {
        if ($caseFile->user_id === $user->getKey()) {
            return true;
        }

        if ($caseFile->organization_id === null) {
            return false;
        }

        return $user->organizations()->whereKey($caseFile->organization_id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CaseFile $caseFile): bool
    {
        return $this->view($user, $caseFile);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CaseFile $caseFile): bool
    {
        return $this->view($user, $caseFile);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CaseFile $caseFile): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CaseFile $caseFile): bool
    {
        return false;
    }
}
