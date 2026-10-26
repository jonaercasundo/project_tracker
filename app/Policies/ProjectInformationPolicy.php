<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\ProjectInformation;
use App\Models\User;

class ProjectInformationPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('finance')) {
            return true;
        }

        return $this->viewAnyForCompany($user, Company::query()->find(session('company_id')));
    }

    public function viewAnyForCompany(User $user, ?Company $company): bool
    {
        if ($user->hasRole('finance')) {
            return true;
        }

        if ($company === null || $company->code !== 'MMC' || ! $company->is_active || ! $user->belongsToCompany($company->getKey())) {
            return false;
        }

        return $user->hasRole('user');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, ProjectInformation $bidding): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ProjectInformation $bidding): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, ProjectInformation $bidding): bool
    {
        return $this->viewAny($user);
    }

    public function reverseAward(User $user, ProjectInformation $bidding): bool
    {
        return $user->hasRole('finance');
    }

    public function verifyLifecycleEvidence(User $user): bool
    {
        $company = $user->currentCompany();

        return $user->hasRole('user')
            && $company?->code === 'MMC'
            && $company->is_active
            && $user->belongsToCompany($company->company_id);
    }

    public function uploadDocument(User $user, ProjectInformation $bidding): bool
    {
        return $this->update($user, $bidding);
    }

    public function downloadDocument(User $user, ProjectInformation $bidding): bool
    {
        return $this->view($user, $bidding);
    }

    public function deleteDocument(User $user, ProjectInformation $bidding): bool
    {
        return $this->delete($user, $bidding);
    }
}
