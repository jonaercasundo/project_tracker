<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JarvisIntegrationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.jarvis', [
            'token' => $request->user()->tokens()->where('name', 'JARVIS')->latest('id')->first(),
        ]);
    }

    public function store(Request $request, AdminAuditLogger $audit): JsonResponse
    {
        return $this->generate($request, false, $audit);
    }

    public function update(Request $request, AdminAuditLogger $audit): JsonResponse
    {
        return $this->generate($request, true, $audit);
    }

    public function destroy(Request $request, AdminAuditLogger $audit): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $user->tokens()->where('name', 'JARVIS')->delete();
        });
        $audit->record($request->user(), 'integration.jarvis_token_revoked', 'personal_access_token', null, 'Revoked the JARVIS API token.', $request->user()->currentCompany()?->getKey());

        return response()->json(['message' => 'JARVIS token revoked.']);
    }

    private function generate(Request $request, bool $regenerate, AdminAuditLogger $audit): JsonResponse
    {
        $plainTextToken = DB::transaction(function () use ($request, $regenerate): string {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $tokens = $user->tokens()->where('name', 'JARVIS');

            abort_if(! $regenerate && $tokens->exists(), 409, 'A JARVIS token already exists. Regenerate to replace it.');

            if ($regenerate) {
                $tokens->delete();
            }

            return $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
        });
        $audit->record($request->user(), $regenerate ? 'integration.jarvis_token_replaced' : 'integration.jarvis_token_created', 'personal_access_token', null, $regenerate ? 'Replaced the JARVIS API token.' : 'Created the JARVIS API token.', $request->user()->currentCompany()?->getKey());

        return response()->json(['token' => $plainTextToken], 201);
    }
}
