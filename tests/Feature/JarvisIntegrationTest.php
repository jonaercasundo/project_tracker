<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JarvisIntegrationTestCase extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): Application
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->singleton(Kernel::class, JarvisIntegrationConsoleKernel::class);
        $app->make(Kernel::class)->bootstrap();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];

        return $app;
    }

    protected function migrateFreshUsing(): array
    {
        return [
            '--path' => [
                'database/migrations/0001_01_01_000000_create_users_table.php',
                'database/migrations/2026_06_15_081615_create_permission_tables.php',
                'database/migrations/2026_09_02_024221_create_companies_table.php',
                'database/migrations/2026_09_02_024448_create_company_user_table.php',
                'database/migrations/2026_10_02_073836_create_personal_access_tokens_table.php',
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        RefreshDatabaseState::$migrated = false;
        RefreshDatabaseState::$inMemoryConnections = [];
    }
}

/**
 * These HTTP tests do not need application command discovery, which currently
 * includes a command whose class name does not match its filename.
 */
class JarvisIntegrationConsoleKernel extends Illuminate\Foundation\Console\Kernel
{
    protected function discoverCommands(): void {}
}

pest()->extend(JarvisIntegrationTestCase::class);

function jarvisTestUser(bool $administrator = true): User
{
    $roleName = $administrator ? 'Administrator' : 'user';
    $role = Role::findOrCreate($roleName, 'web');
    $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => $roleName]);
    $user->assignRole($role);

    return $user;
}

function jarvisTestRequest(string $token): TestResponse
{
    Auth::forgetGuards();

    return test()->withToken($token)->getJson('/api/jarvis/test');
}

it('forbids non administrators from managing tokens', function (string $method) {
    $user = jarvisTestUser(false);

    $this->actingAs($user)->call($method, '/settings/integrations/jarvis')->assertForbidden();

    $this->assertDatabaseCount('personal_access_tokens', 0);
})->with(['GET', 'POST', 'PUT', 'DELETE']);

it('requires login for token management', function (string $method) {
    $this->call($method, '/settings/integrations/jarvis')->assertRedirect('/login');

    $this->assertDatabaseCount('personal_access_tokens', 0);
})->with(['GET', 'POST', 'PUT', 'DELETE']);

it('allows administrators to view the integration', function () {
    $user = jarvisTestUser();

    $this->actingAs($user)->get('/settings/integrations/jarvis')
        ->assertOk()->assertSee('Not Configured')->assertSee('Generate API Token');
});

it('generates a hashed scoped token and never returns it on page refresh', function () {
    $user = jarvisTestUser();

    $response = $this->actingAs($user)->postJson('/settings/integrations/jarvis')
        ->assertCreated()->assertJsonStructure(['token']);
    $plainToken = $response->json('token');
    $storedToken = $user->tokens()->sole();

    expect($storedToken->name)->toBe('JARVIS');
    expect($storedToken->abilities)->toBe(['jarvis:read']);
    expect($storedToken->token)->toBe(hash('sha256', explode('|', $plainToken, 2)[1]));
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    expect(json_encode(session()->all()))->not->toContain($plainToken);

    $this->get('/settings/integrations/jarvis')
        ->assertOk()->assertSee('Active')->assertSee('Token hidden permanently.')
        ->assertDontSee($plainToken)->assertDontSee($storedToken->token);
    $this->get('/settings/integrations/jarvis')->assertDontSee($plainToken);

    jarvisTestRequest($plainToken)->assertOk()->assertExactJson([
        'success' => true,
        'message' => 'JARVIS connected to MMC Tracker',
    ]);
    expect($storedToken->fresh()->last_used_at)->not->toBeNull();
});

it('returns 401 without a bearer token including non JSON requests', function () {
    $this->get('/api/jarvis/test')->assertUnauthorized();
});

it('returns 401 for an invalid token', function () {
    $this->withToken('999|invalid')->getJson('/api/jarvis/test')->assertUnauthorized();
});

it('returns 401 for browser login without a bearer token', function () {
    $this->actingAs(jarvisTestUser())->getJson('/api/jarvis/test')->assertUnauthorized();
});

it('regenerates the token and immediately rejects the old token', function () {
    $user = jarvisTestUser();
    $oldToken = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;
    $unrelated = $user->createToken('Other integration', ['other:read'])->accessToken;

    $response = $this->actingAs($user)->putJson('/settings/integrations/jarvis')->assertCreated();
    $newToken = $response->json('token');

    expect($newToken)->not->toBe($oldToken);
    expect($user->tokens()->where('name', 'JARVIS')->count())->toBe(1);
    $this->assertModelExists($unrelated);
    jarvisTestRequest($oldToken)->assertUnauthorized();
    jarvisTestRequest($newToken)->assertOk();
});

it('revokes the token and immediately returns 401 for it', function () {
    $user = jarvisTestUser();
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    $this->actingAs($user)->deleteJson('/settings/integrations/jarvis')->assertOk();

    expect($user->tokens()->where('name', 'JARVIS')->count())->toBe(0);
    $this->get('/settings/integrations/jarvis')->assertSee('Not Configured');
    jarvisTestRequest($token)->assertUnauthorized();
});

it('rejects duplicate generation without replacing the existing token', function () {
    $user = jarvisTestUser();
    $token = $user->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    $this->actingAs($user)->postJson('/settings/integrations/jarvis')->assertConflict();

    $this->assertDatabaseCount('personal_access_tokens', 1);
    jarvisTestRequest($token)->assertOk();
});

it('returns 403 when the JARVIS token lacks read permission', function () {
    $token = jarvisTestUser()->createToken('JARVIS', ['other:read'])->plainTextToken;

    jarvisTestRequest($token)->assertForbidden();
});

it('returns 401 for a token belonging to another integration', function () {
    $token = jarvisTestUser()->createToken('Other integration', ['jarvis:read'])->plainTextToken;

    jarvisTestRequest($token)->assertUnauthorized();
});

it('returns 403 when the token owner is no longer an administrator', function () {
    $token = jarvisTestUser(false)->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    jarvisTestRequest($token)->assertForbidden();
});

it('keeps each administrators tokens separate', function () {
    $first = jarvisTestUser();
    $other = jarvisTestUser();
    $otherToken = $other->createToken('JARVIS', ['jarvis:read'])->accessToken;

    $this->actingAs($first)->get('/settings/integrations/jarvis')->assertSee('Not Configured');
    $this->deleteJson('/settings/integrations/jarvis')->assertOk();

    $this->assertModelExists($otherToken);
});

it('requires CSRF for management mutations', function (string $method) {
    $this->app->instance('env', 'local');
    $user = jarvisTestUser();

    $this->actingAs($user)->call($method, '/settings/integrations/jarvis')->assertStatus(419);

    $this->assertDatabaseCount('personal_access_tokens', 0);
})->with(['POST', 'PUT', 'DELETE']);

it('preserves the existing login and logout flow', function () {
    $user = jarvisTestUser();
    $company = Company::create(['name' => 'MMC', 'code' => 'MMC', 'is_active' => true]);
    $user->companies()->attach($company);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($user);
    $this->get('/settings/integrations/jarvis')->assertOk();
    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

it('does not allow the integration token to access browser administration', function () {
    $token = jarvisTestUser()->createToken('JARVIS', ['jarvis:read'])->plainTextToken;

    $this->withToken($token)->get('/settings/integrations/jarvis')->assertRedirect('/login');
});
