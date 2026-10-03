<?php

namespace App\Http\Controllers;

use App\Models\User;
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

    public function store(Request $request): JsonResponse
    {
        return $this->generate($request, false);
    }

    public function update(Request $request): JsonResponse
    {
        return $this->generate($request, true);
    }

    public function destroy(Request $request): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $user->tokens()->where('name', 'JARVIS')->delete();
        });

        return response()->json(['message' => 'JARVIS token revoked.']);
    }

    private function generate(Request $request, bool $regenerate): JsonResponse
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

        return response()->json(['token' => $plainTextToken], 201);
    }
}
