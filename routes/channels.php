<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->getKey() === $id;
});

Broadcast::channel('tracker.company.{companyId}', function (User $user, int $companyId): bool {
    return (int) session('company_id') === $companyId
        && $user->belongsToCompany($companyId);
});
