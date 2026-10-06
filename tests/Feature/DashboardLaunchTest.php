<?php

use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

it('shows sign in to guests and requires authentication to launch', function () {
    $this->get(route('welcome'))->assertSee('Sign In')->assertDontSee('Launch Dashboard');
    $this->get(route('dashboard.launch'))->assertRedirect(route('login'));
});

it('points both authenticated welcome links at the central launcher', function () {
    $user = $this->miUser();

    $response = $this->actingAs($user)->get(route('welcome'));

    $response->assertSee('Launch Dashboard')->assertSee('href="'.route('dashboard.launch').'"', false)
        ->assertDontSee('href="'.route('projects.dashboard').'"', false);
});

it('selects the only active company and launches its dashboard', function (string $code, string $destination) {
    $user = $this->miUser('user', $code);
    $companyId = $user->companies()->sole()->getKey();

    $this->actingAs($user)->get(route('dashboard.launch'))
        ->assertRedirect(route($destination))->assertSessionHas('company_id', $companyId);
})->with(['MI' => ['MI', 'mi_app.dashboard'], 'MMC' => ['MMC', 'projects.dashboard']]);

it('restores valid company context from the surviving login session on subsequent requests', function (string $code, string $destination) {
    $user = $this->miUser('user', $code);
    $other = $this->miUser('user', $code === 'MI' ? 'MMC' : 'MI')->companies()->sole();
    $user->companies()->attach($other);
    $companyId = $user->companies()->where('code', $code)->sole()->getKey();

    $this->withSession(['company_id' => $companyId])->post(route('login'), [
        'email' => $user->email, 'password' => 'password', 'remember' => true,
    ])->assertRedirect(route($destination));
    $this->app['auth']->forgetGuards();
    $this->get(route('welcome'))->assertSee('Launch Dashboard');
    $this->get(route('dashboard.launch'))->assertRedirect(route($destination))->assertSessionHas('company_id', $companyId);
})->with(['MI' => ['MI', 'mi_app.dashboard'], 'MMC' => ['MMC', 'projects.dashboard']]);

it('sends users with multiple companies and no context to company selection', function () {
    $user = $this->miUser();
    $other = $this->miUser('user', 'MMC')->companies()->sole();
    $user->companies()->attach($other);

    $this->actingAs($user)->get(route('dashboard.launch'))->assertRedirect(route('company.select'))->assertSessionMissing('company_id');
    $this->get(route('company.select'))->assertSee('Select Company')->assertViewHas('companies', fn ($companies): bool => $companies->count() === 2);
});

it('clears stale company context before safely resolving the only authorized company', function (string $staleContext) {
    $user = $this->miUser();
    $authorizedCompanyId = $user->companies()->sole()->getKey();
    $other = $this->miUser('user', 'MMC')->companies()->sole();
    $staleId = $other->getKey();
    if ($staleContext === 'inactive') {
        $user->companies()->attach($other);
        $other->update(['is_active' => false]);
    } elseif ($staleContext === 'revoked') {
        $user->companies()->attach($other);
        $user->companies()->detach($other);
    } elseif ($staleContext === 'malformed') {
        $staleId = ['unexpected' => $staleId];
    }

    $this->actingAs($user)->withSession(['company_id' => $staleId])->get(route('mi_app.dashboard'))
        ->assertForbidden()->assertSessionMissing('company_id');
    $this->get(route('dashboard.launch'))->assertRedirect(route('mi_app.dashboard'))->assertSessionHas('company_id', $authorizedCompanyId);
})->with(['unassigned', 'inactive', 'revoked', 'malformed']);

it('clears an invalid context and requests selection when several authorized companies remain', function () {
    $user = $this->miUser();
    $other = $this->miUser('user', 'MMC')->companies()->sole();
    $user->companies()->attach($other);

    $this->actingAs($user)->withSession(['company_id' => 999999])->get(route('dashboard.launch'))
        ->assertRedirect(route('company.select'))->assertSessionMissing('company_id');
});

it('validates membership and regenerates the session when selecting or switching company', function (string $action, string $code, string $destination) {
    $user = $this->miUser();
    $other = $this->miUser('user', 'MMC')->companies()->sole();
    $user->companies()->attach($other);
    $selectedId = $user->companies()->where('code', $code)->sole()->getKey();
    $this->actingAs($user)->withSession(['company_id' => $other->getKey(), 'url.intended' => route('projects.dashboard')]);
    $previousSessionId = session()->getId();

    $this->post(route($action), ['company_id' => $selectedId])
        ->assertRedirect(route($destination))->assertSessionHas('company_id', $selectedId)->assertSessionMissing('url.intended');

    expect(session()->getId())->not->toBe($previousSessionId);
})->with([
    ['company.select.store', 'MI', 'mi_app.dashboard'], ['company.select.store', 'MMC', 'projects.dashboard'],
    ['company.switch', 'MI', 'mi_app.dashboard'], ['company.switch', 'MMC', 'projects.dashboard'],
]);

it('refuses unauthorized or inactive company selection without replacing valid context', function (string $action, bool $inactive) {
    $user = $this->miUser();
    $companyId = $user->companies()->sole()->getKey();
    $other = $this->miUser('user', 'MMC')->companies()->sole();
    if ($inactive) {
        $user->companies()->attach($other);
        $other->update(['is_active' => false]);
    }

    $this->actingAs($user)->withSession(['company_id' => $companyId])->post(route($action), ['company_id' => $other->getKey()])
        ->assertForbidden()->assertSessionHas('company_id', $companyId);
})->with([
    ['company.select.store', false], ['company.select.store', true], ['company.switch', false], ['company.switch', true],
]);

it('preserves cross company restrictions on manually entered module URLs', function (string $code, string $destination) {
    $user = $this->miUser('user', $code);

    $this->actingAs($user)->withSession(['company_id' => $user->companies()->sole()->getKey()])->get(route($destination))->assertForbidden();
})->with([
    ['MMC', 'mi_app.dashboard'], ['MMC', 'mi_app.index'], ['MMC', 'mi_app.create'], ['MMC', 'mi_app.settings'], ['MI', 'projects.dashboard'],
]);

it('does not silently switch context for users belonging to both companies', function () {
    $user = $this->miUser();
    $other = $this->miUser('user', 'MMC')->companies()->sole();
    $user->companies()->attach($other);
    $companyId = $user->companies()->where('code', 'MI')->sole()->getKey();

    $this->actingAs($user)->withSession(['company_id' => $companyId])->get(route('projects.dashboard'))
        ->assertForbidden()->assertSessionHas('company_id', $companyId);
});

it('uses the selected company and existing role destinations consistently', function (string $code, string $role, string $destination) {
    $user = $this->miUser($role, $code);
    if ($role === 'Executive') {
        $user->givePermissionTo(Permission::findOrCreate('mi.budget.approve', 'web'));
    }

    $this->actingAs($user)->get(route('dashboard.launch'))->assertRedirect(route($destination))
        ->assertSessionHas('company_id', $user->companies()->sole()->getKey());
})->with([
    ['MI', 'Administrator', 'admin.dashboard'], ['MI', 'accounting', 'accounting.mi.dashboard'], ['MI', 'Executive', 'mi.approvals'],
    ['MMC', 'Administrator', 'admin.dashboard'], ['MMC', 'finance', 'finance.dashboard'], ['MMC', 'IT', 'it.dashboard'],
    ['MMC', 'Warehouse_officer', 'warehouse.dashboard'], ['MI', 'IT', 'site.maintenance'],
]);

it('gives administrators a consistent destination when they also hold the user role', function () {
    $user = $this->miUser('Administrator');
    $user->assignRole(Role::findOrCreate('user', 'web'));
    $companyId = $user->companies()->sole()->getKey();

    $this->actingAs($user)->post(route('company.select.store'), ['company_id' => $companyId])->assertRedirect(route('admin.dashboard'));
    $this->get(route('dashboard.launch'))->assertRedirect(route('admin.dashboard'));
});

it('uses the launcher when an authenticated user visits the login page', function () {
    $user = $this->miUser();

    $this->actingAs($user)->get(route('login'))->assertRedirect(route('dashboard.launch'));
    $this->get(route('dashboard.launch'))->assertRedirect(route('mi_app.dashboard'));
});

it('logs in to the authorized company instead of a stale cross company intended URL', function () {
    $user = $this->miUser();

    $this->withSession(['url.intended' => route('projects.dashboard')])->post(route('login'), [
        'email' => $user->email, 'password' => 'password',
    ])->assertRedirect(route('mi_app.dashboard'))->assertSessionMissing('url.intended');

    $this->assertAuthenticatedAs($user);
});

it('requires company selection after login when multiple companies have no valid context', function () {
    $user = $this->miUser();
    $user->companies()->attach($this->miUser('user', 'MMC')->companies()->sole());

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('company.select'))->assertSessionMissing('company_id');
});

it('does not authenticate or select company context with an invalid password', function () {
    $user = $this->miUser();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'incorrect-password'])
        ->assertSessionHasErrors('email')->assertSessionMissing('company_id');

    $this->assertGuest();
});

it('uses the central launcher as the authenticated verification and password fallback', function (string $method, string $destination) {
    $user = $this->miUser();

    $this->actingAs($user)->{$method}(route($destination), ['password' => 'password'])
        ->assertRedirect(route('dashboard.launch', absolute: false));
})->with([
    ['get', 'verification.notice'], ['post', 'verification.send'], ['post', 'password.confirm'],
]);

it('uses the central launcher after opening a valid email verification link', function () {
    $user = $this->miUser();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(5), [
        'id' => $user->getKey(), 'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('dashboard.launch', absolute: false).'?verified=1');
});

it('shows a configuration error and invalidates authentication when no active company is available', function (bool $inactive, string $entry) {
    $user = $this->miUser();
    $company = $user->companies()->sole();
    if ($inactive) {
        $company->update(['is_active' => false]);
    } else {
        $user->companies()->detach();
    }

    $this->withSession(['company_id' => $company->getKey()]);
    $response = $entry === 'login'
        ? $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        : $this->actingAs($user)->get(route($entry));

    $response->assertRedirect(route('login'))->assertSessionHasErrors(['email' => 'Your account is not assigned to any active company.'])
        ->assertSessionMissing('company_id');

    $this->assertGuest();
})->with([[false, 'dashboard.launch'], [true, 'dashboard.launch'], [false, 'company.select'], [false, 'login'], [true, 'login']]);

it('clears company context and intended URLs on logout', function () {
    $user = $this->miUser();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->withSession(['url.intended' => route('mi_app.dashboard')])->post(route('logout'))
        ->assertRedirect('/')->assertSessionMissing('company_id')->assertSessionMissing('url.intended');

    $this->assertGuest();
    $this->get(route('welcome'))->assertSee('Sign In')->assertDontSee('Launch Dashboard');
});

it('allows authorized MI users to reach the dashboard and product pages', function () {
    foreach ([
        '2026_07_27_073520_create_m_i__products_table.php', '2026_07_29_075636_create_categories_table.php',
        '2026_07_29_075711_create_sub_categories_table.php', '2026_07_29_075746_create_product_types_table.php',
        '2026_07_29_075829_create_collections_table.php', '2026_07_30_085237_create_mi_materials_table.php',
        '2026_07_31_015928_update_mi_products_table_for_new_product_system.php',
        '2026_07_31_020944_change_materials_and_color_to_json_in_mi_products_table.php',
        '2026_08_17_000000_add_price_to_products_table.php', '2026_10_06_040342_create_mi_product_images_table.php',
    ] as $path) {
        (require database_path('migrations/'.$path))->up();
    }
    $user = $this->miUser();
    $this->actingAs($user)->get(route('dashboard.launch'))->assertRedirect(route('mi_app.dashboard'));

    foreach (['mi_app.dashboard', 'mi_app.index', 'mi_app.create', 'mi_app.settings'] as $destination) {
        $this->get(route($destination))->assertOk();
    }
});
