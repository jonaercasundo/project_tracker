<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

it('shows warehouse stock summary cards', function () {
    $role = Role::create(['name' => 'Warehouse_officer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    actingAs($user);

    $response = $this->get(route('warehouse.dashboard'));

    $response->assertOk();
    $response->assertSee('Stock In');
    $response->assertSee('Stock Out');
    $response->assertSee('Delivered');
});
