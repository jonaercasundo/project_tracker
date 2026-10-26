<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AiPageContextService
{
    public function __construct(private AiModuleRegistry $registry) {}

    /** @return array<string, mixed> */
    public function current(Request $request): array
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        $company = $user->currentCompany();
        abort_unless($company instanceof Company, 403, 'Select an active company before using the assistant.');

        $requestedRoute = $request->string('page_route')->toString();
        $routeName = $requestedRoute !== '' ? $requestedRoute : $request->route()?->getName();
        if ($routeName !== null && ! Route::has($routeName)) {
            $routeName = null;
        }

        return $this->registry->pageContext($user, $company, $routeName);
    }
}
