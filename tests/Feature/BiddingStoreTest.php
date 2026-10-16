<?php

use App\Models\ProjectInformation;
use App\Models\ProjectItem;
use Tests\BiddingProjectFixtureFactory;
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

it('stores the entered project code when Others is selected', function (string $prefix, string $role) {
    $this->signInBiddingUser($role);
    $payload = $this->validBiddingPayload();
    $payload['project_code'] = 'Others';
    $payload['project_code_other'] = '  CUSTOM-2026  ';

    $response = $this->post(route($prefix.'.store'), $payload);

    $response->assertRedirect(route($prefix.'.show', ProjectInformation::query()->sole()))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('project_information', ['project_id' => $payload['project_id'], 'project_code' => 'CUSTOM-2026']);
})->with(['operation' => ['project.bidding', 'user'], 'finance' => ['bidding', 'finance']]);

it('rejects Others without a valid custom project code', function (array $customInput, string $message) {
    $this->signInBiddingUser();
    $payload = array_replace($this->validBiddingPayload(), ['project_code' => 'Others'], $customInput);

    $response = $this->from(route('project.bidding.create'))->post(route('project.bidding.store'), $payload);

    $response->assertRedirect(route('project.bidding.create'))->assertSessionHasErrors(['project_code_other' => $message]);
    $this->assertDatabaseCount('project_information', 0);
})->with([
    'missing' => [[], 'Enter the other project code.'],
    'blank' => [['project_code_other' => ''], 'Enter the other project code.'],
    'whitespace' => [['project_code_other' => '   '], 'Enter the other project code.'],
    'non-string' => [['project_code_other' => ['CUSTOM']], 'The project code other field must be a string.'],
    'too long' => [['project_code_other' => str_repeat('A', 256)], 'The project code other field must not be greater than 255 characters.'],
]);

it('stores preset project codes while ignoring unused custom input', function (string $projectCode) {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['project_code'] = $projectCode;
    $payload['project_code_other'] = ['stale custom input'];

    $response = $this->post(route('project.bidding.store'), $payload);

    $response->assertRedirect(route('project.bidding.show', ProjectInformation::query()->sole()))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('project_information', ['project_id' => $payload['project_id'], 'project_code' => $projectCode]);
})->with(['SME', 'SFP', 'MT', 'Textbook', 'DCP']);

it('rejects project codes submitted outside the dropdown choices', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['project_code'] = 'UNLISTED';

    $response = $this->post(route('project.bidding.store'), $payload);

    $response->assertSessionHasErrors(['project_code' => 'The selected project code is invalid.']);
    $this->assertDatabaseCount('project_information', 0);
});

it('updates a bidding document to the entered custom project code', function (string $prefix, string $role) {
    $this->signInBiddingUser($role);
    $project = BiddingProjectFixtureFactory::new()->create();

    $response = $this->put(route($prefix.'.update', $project), ['project_code' => 'Others', 'project_code_other' => '  UPDATED-CODE  ']);

    $response->assertRedirect(route($prefix.'.show', $project))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('project_information', ['id' => $project->id, 'project_code' => 'UPDATED-CODE']);
})->with(['operation' => ['project.bidding', 'user'], 'finance' => ['bidding', 'finance']]);

it('preserves a saved custom project code when a partial update omits the dropdown', function () {
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create(['project_code' => 'SAVED-CUSTOM']);

    $response = $this->put(route('project.bidding.update', $project), ['project_name' => 'Updated project name', 'project_code_other' => 'STALE-CUSTOM']);

    $response->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('project_information', ['id' => $project->id, 'project_name' => 'Updated project name', 'project_code' => 'SAVED-CUSTOM']);
});

it('restores a saved custom code under Others and escapes it in the edit form', function () {
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create(['project_code' => '<script>alert("code")</script>']);

    $response = $this->get(route('project.bidding.edit', $project));

    $response->assertSee('value="Others" selected', false)
        ->assertSee('value="&lt;script&gt;alert(&quot;code&quot;)&lt;/script&gt;"', false)
        ->assertDontSee('<script>alert("code")</script>', false);
});

it('preserves the Others selection and custom input after validation fails', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['project_code'] = 'Others';
    $payload['project_code_other'] = 'CUSTOM-TO-RESTORE';
    $payload['project_name'] = '';

    $response = $this->from(route('project.bidding.create'))->post(route('project.bidding.store'), $payload);

    $response->assertRedirect(route('project.bidding.create'))
        ->assertSessionHasErrors('project_name')
        ->assertSessionHasInput('project_code', 'Others')
        ->assertSessionHasInput('project_code_other', 'CUSTOM-TO-RESTORE');
    $this->get(route('project.bidding.create'))
        ->assertSee('value="Others" selected', false)
        ->assertSee('value="CUSTOM-TO-RESTORE"', false);
    $this->assertDatabaseCount('project_information', 0);
});

it('keeps a missing custom input blank after an invalid edit instead of restoring the saved code', function () {
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create(['project_code' => 'SAVED-CUSTOM']);

    $response = $this->from(route('project.bidding.edit', $project))
        ->put(route('project.bidding.update', $project), ['project_code' => 'Others']);

    $response->assertRedirect(route('project.bidding.edit', $project))
        ->assertSessionHasErrors(['project_code_other' => 'Enter the other project code.']);
    $this->get(route('project.bidding.edit', $project))
        ->assertSee('value="Others" selected', false)
        ->assertDontSee('value="SAVED-CUSTOM"', false);
    $this->assertDatabaseHas('project_information', ['id' => $project->id, 'project_code' => 'SAVED-CUSTOM']);
});
