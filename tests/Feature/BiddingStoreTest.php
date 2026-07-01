<?php

use App\Models\User;

it('stores a bidding document when the abc value is blank', function () {
    $this->withoutMiddleware();

    $user = User::factory()->create([
        'username' => 'tester',
        'role' => 'admin',
    ]);

    $response = $this->actingAs($user)->post(route('bidding.store'), [
        'project_name' => 'Sample Bidding Project',
        'project_id' => 'BID-TEST-001',
        'procuring_entity' => 'Test Agency',
        'approved_budget_contract_abc' => '',
        'delivery_period' => '30',
        'date_of_bid_opening' => '2026-08-01',
        'prepared_by' => 'Test User',
        'prepared_date' => '2026-07-01',
        'verified_by' => 'Verifier',
        'status' => 'Draft',
        'lots' => [
            [
                'lot_no' => 'Lot 1',
                'country_code' => 'PH',
                'region_code' => null,
                'province_code' => null,
                'city_code' => null,
                'barangay_code' => null,
                'delivery_address' => 'Test Address',
                'items' => [
                    [
                        'item_description' => 'Sample item',
                        'unit' => 'pcs',
                        'quantity' => '2',
                        'unit_cost' => '100',
                        'brand' => 'Test Brand',
                        'remarks' => 'Test remarks',
                    ],
                ],
            ],
        ],
    ]);

    $response->assertRedirect(route('bidding.index'));
    $response->assertSessionHas('success', 'Bidding document created successfully.');
});
