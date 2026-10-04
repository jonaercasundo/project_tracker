<?php

use App\Models\ProjectInformation;
use App\Models\ProjectItem;
use Tests\BiddingTestCase;

pest()->extend(BiddingTestCase::class)->in(__FILE__);

it('stores a bidding document when the abc value is blank', function () {
    $this->signInBiddingUser('finance');
    $payload = $this->validBiddingPayload();
    $payload['approved_budget_contract_abc'] = '';

    $response = $this->post(route('bidding.store'), $payload);

    $response->assertRedirect(route('bidding.show', ProjectInformation::query()->sole()));
    $response->assertSessionHas('success', 'Bidding document created successfully.');
    expect(ProjectInformation::query()->sole()->approved_budget_contract_abc)->toBe('0.00');
    expect(ProjectInformation::query()->sole()->calculated_total)->toBe('25.50');
    expect(ProjectItem::query()->sole()->total_amount)->toBe('25.50');
});
