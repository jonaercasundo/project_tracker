<?php

namespace App\Policies;

use App\Models\ProjectInformation;
use App\Models\User;

class ProjectInformationPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('finance')) {
            return true;
        }

        return $user->hasRole('user') && $user->companies()
            ->where('companies.code', 'MMC')
            ->where('companies.is_active', true)
            ->where('companies.company_id', session('company_id'))
            ->exists();
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
