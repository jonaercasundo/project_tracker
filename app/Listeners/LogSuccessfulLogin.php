<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        app(AdminAuditLogger::class)->record($event->user, 'user.login', 'user', $event->user->getAuthIdentifier(), 'User signed in.');
    }
}
