<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureJarvisToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (! $request->bearerToken() || ! $token instanceof PersonalAccessToken || $token->name !== 'JARVIS') {
            throw new AuthenticationException;
        }

        abort_unless($user->hasRole('Administrator') && $token->can('jarvis:read'), 403);

        return $next($request);
    }
}
