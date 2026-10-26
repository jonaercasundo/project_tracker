<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class AiModuleRegistry
{
    /** @return array<string, array<string, mixed>> */
    public function available(User $user, Company $company): array
    {
        if (! $user->can('ai.assistant.use')) {
            return [];
        }

        return collect(config('ai_assistant.modules', []))
            ->filter(fn (array $module): bool => $this->allows($user, $company, $module))
            ->all();
    }

    /** @return array{key: string, label: string, description: string, route: ?string, page: array<string, mixed>, fields: array<string, string>, suggestions: array<int, string>, links: array<int, array<string, mixed>>, modules: array<int, array{key: string, label: string}>} */
    public function pageContext(User $user, Company $company, ?string $routeName): array
    {
        $available = $this->available($user, $company);
        $matches = collect($available)->map(function (array $module, string $key) use ($routeName): ?array {
            $prefix = collect($module['route_prefixes'] ?? [])
                ->filter(fn (string $prefix): bool => $routeName !== null && str_starts_with($routeName, $prefix))
                ->sortByDesc(fn (string $value): int => mb_strlen($value))
                ->first();

            return $prefix === null ? null : ['key' => $key, 'prefix_length' => mb_strlen($prefix), 'module' => $module];
        })->filter()->sortByDesc('prefix_length')->first();

        $key = $matches['key'] ?? 'workspace';
        $module = $matches['module'] ?? [
            'label' => $company->code.' Workspace',
            'description' => 'Answer general questions about the current authorized company workspace. Do not infer access to other companies.',
            'pages' => [],
        ];
        $page = $routeName !== null ? ($module['pages'][$routeName] ?? []) : [];

        return [
            'key' => $key,
            'label' => $module['label'],
            'description' => $module['description'],
            'route' => $routeName,
            'page' => $page,
            'fields' => $page['fields'] ?? [],
            'suggestions' => $page['suggestions'] ?? ['Explain what I can do in this area.', 'What should I check before saving?'],
            'links' => $this->authorizedLinks($user, $company, $page['links'] ?? []),
            'modules' => collect($available)->map(fn (array $item, string $moduleKey): array => ['key' => $moduleKey, 'label' => $item['label']])->values()->all(),
        ];
    }

    /** @param array<string, mixed> $module */
    public function allows(User $user, Company $company, array $module): bool
    {
        if (($module['company'] ?? null) !== '*' && ($module['company'] ?? null) !== $company->code) {
            return false;
        }

        $roles = $module['roles'] ?? [];
        if ($roles !== [] && ! $user->hasAnyRole($roles)) {
            return false;
        }

        $permissions = $module['permissions'] ?? [];

        return $permissions === [] || $user->hasAnyPermission($permissions);
    }

    /** @param array<int, array<string, mixed>> $links
     * @return array<int, array{label: string, url: string}>
     */
    private function authorizedLinks(User $user, Company $company, array $links): array
    {
        return collect($links)->filter(function (array $link) use ($user, $company): bool {
            if (! isset($link['route']) || ! Route::has($link['route'])) {
                return false;
            }

            return $this->moduleForRoute($user, $company, $link['route']) !== null;
        })->map(fn (array $link): array => [
            'label' => (string) $link['label'],
            'url' => route($link['route'], $link['parameters'] ?? []),
        ])->values()->all();
    }

    /** @return array<string, mixed>|null */
    private function moduleForRoute(User $user, Company $company, string $routeName): ?array
    {
        return collect($this->available($user, $company))
            ->filter(fn (array $module): bool => collect($module['route_prefixes'] ?? [])
                ->contains(fn (string $prefix): bool => str_starts_with($routeName, $prefix)))
            ->sortByDesc(fn (array $module): int => collect($module['route_prefixes'] ?? [])
                ->filter(fn (string $prefix): bool => str_starts_with($routeName, $prefix))
                ->max(fn (string $value): int => mb_strlen($value)) ?? 0)
            ->first();
    }
}
