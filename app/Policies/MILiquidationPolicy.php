<?php

namespace App\Policies;

use App\Models\MI_Liquidation;
use App\Models\User;

class MILiquidationPolicy
{
    public function create(User $user): bool
    {
        $company = $user->currentCompany();

        return $company?->code === 'MI' && $company->is_active && $user->hasRole('user');
    }

    public function view(User $user, MI_Liquidation $liquidation): bool
    {
        $company = $user->currentCompany();

        return $company?->code === 'MI' && $company->is_active
            && (int) $liquidation->company_id === (int) $company->getKey()
            && (($user->hasRole('accounting') && $user->can('mi.liquidation.view')) || ($user->hasRole('user')
                && (int) $liquidation->prepared_by === (int) $user->getKey()));
    }

    public function update(User $user, MI_Liquidation $liquidation): bool
    {
        return $this->view($user, $liquidation) && $user->hasRole('user')
            && (int) $liquidation->prepared_by === (int) $user->getKey() && $liquidation->status === 'Pending';
    }

    public function delete(User $user, MI_Liquidation $liquidation): bool
    {
        return $this->update($user, $liquidation);
    }
}
