<?php

namespace App\Policies;

use App\Models\MI_Product;
use App\Models\User;

class MIProductPolicy
{
    public function update(User $user, MI_Product $product): bool
    {
        return $user->hasMIUserAccess();
    }
}
