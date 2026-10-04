<?php

use App\Models\BiddingDeliveryAddress;
use App\Models\BiddingKeyStage;
use App\Models\New\Item;
use App\Models\ProjectInformation;
use App\Models\ProjectItem;
use App\Models\ProjectLot;
use App\Policies\ProjectInformationPolicy;
use App\Services\BiddingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Tests\BiddingCatalogFixtureFactory;
use Tests\BiddingItemFixtureFactory;
use Tests\BiddingLotFixtureFactory;
use Tests\BiddingProjectFixtureFactory;
use Tests\BiddingTestCase;

pest()->extend(BiddingTestCase::class)->in(__FILE__);

it('deletes bidding A without deleting bidding B items when their numeric IDs overlap', function () {
    $this->signInBiddingUser();
    $projectA = BiddingProjectFixtureFactory::new()->create(['id' => 17]);
    $projectB = BiddingProjectFixtureFactory::new()->create(['id' => 18]);
    $lotB = BiddingLotFixtureFactory::new()->create(['id' => 17, 'project_id' => $projectB->id]);
    $lotA = BiddingLotFixtureFactory::new()->create(['id' => 30, 'project_id' => $projectA->id]);
    $itemB = BiddingItemFixtureFactory::new()->create(['lot_id' => $lotB->id]);
    $itemA = BiddingItemFixtureFactory::new()->create(['lot_id' => $lotA->id]);

    $this->delete(route('project.bidding.destroy', $projectA))->assertRedirect(route('project.bidding.index'));

    $this->assertModelMissing($projectA);
    $this->assertModelMissing($lotA);
    $this->assertModelMissing($itemA);
    $this->assertModelExists($projectB);
    $this->assertModelExists($lotB);
    $this->assertModelExists($itemB);
});

it('stores the same canonical hierarchy for operation and finance with authoritative money', function (string $prefix, string $role) {
    $this->signInBiddingUser($role);
    $payload = $this->validBiddingPayload();

    $response = $this->post(route($prefix.'.store'), $payload);

    $project = ProjectInformation::query()->sole();
    $response->assertRedirect(route($prefix.'.show', $project));
    $lot = $project->lots()->sole();
    $address = $lot->addresses()->sole();
    $stage = $address->keystages()->sole();
    $item = $stage->items()->sole();
    expect($project->approved_budget_contract_abc)->toBe('1000.00');
    expect($project->calculated_total)->toBe('25.50');
    expect($project->pre_bid_conf)->toBe('2026-10-01');
    expect($item->quantity)->toBe('2.50');
    expect($item->unit_cost)->toBe('10.20');
    expect($item->total_amount)->toBe('25.50');
    expect($item->unit)->toBe('pcs');
    expect($item->catalog_item_id)->toBe($payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0]['catalog_item_id']);
    $this->assertDatabaseHas('delivery_address', ['id' => $address->id, 'project_id' => $project->id, 'lot_id' => $lot->id, 'delivery_address' => 'Fixture delivery address']);
    $this->assertDatabaseHas('keystages', ['id' => $stage->id, 'project_id' => $project->id, 'lot_id' => $lot->id, 'delivery_address_id' => $address->id, 'name' => 'Fixture key stage']);
})->with(['operation' => ['project.bidding', 'user'], 'finance' => ['bidding', 'finance']]);

it('stores multiple lots addresses stages and items without collapsing their identities', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $stage = $payload['lots'][0]['addresses'][0]['keystages'][0];
    $stage['items'][] = $stage['items'][0];
    $payload['lots'][0]['addresses'][0]['keystages'] = [$stage, array_replace($stage, ['name' => 'Second key stage'])];
    $payload['lots'][0]['addresses'][] = ['delivery_address' => 'Second destination', 'keystages' => [$stage]];
    $payload['lots'][] = ['lot_no' => 'Lot 2', 'country_code' => 'PH', 'addresses' => [['delivery_address' => 'Third destination', 'keystages' => [$stage]]]];

    $response = $this->post(route('project.bidding.store'), $payload);

    $response->assertRedirect(route('project.bidding.show', ProjectInformation::query()->sole()));

    $this->assertDatabaseCount('project_information', 1);
    $this->assertDatabaseCount('lots', 2);
    $this->assertDatabaseCount('delivery_address', 3);
    $this->assertDatabaseCount('keystages', 4);
    $this->assertDatabaseCount('project_items', 8);
    expect(ProjectInformation::query()->sole()->calculated_total)->toBe('204.00');
    expect(ProjectItem::query()->whereNull('keystage_id')->count())->toBe(0);
});

it('preserves omitted status metadata and all unchanged child IDs during a partial edit', function () {
    $this->signInBiddingUser();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $item = ProjectItem::query()->sole();
    $stage = BiddingKeyStage::query()->sole();
    $address = BiddingDeliveryAddress::query()->sole();
    $lot = ProjectLot::query()->sole();

    $this->put(route('project.bidding.update', $project), ['project_name' => 'Changed name'])->assertRedirect(route('project.bidding.show', $project));

    $this->assertDatabaseHas('project_information', ['id' => $project->id, 'project_name' => 'Changed name', 'status' => 'Draft', 'prepared_by' => 'Fixture Preparer', 'verified_by' => 'Fixture Verifier', 'notes_special_condition' => 'Preserve fixture metadata', 'date_of_bid_opening' => '2026-10-04', 'prepared_date' => '2026-09-30']);
    $this->assertModelExists($item);
    $this->assertModelExists($stage);
    $this->assertModelExists($address);
    $this->assertModelExists($lot);
    expect($project->fresh()->calculated_total)->toBe('25.50');
});

it('preserves unchanged items when an existing hierarchy is resubmitted', function () {
    $this->signInBiddingUser();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $item = ProjectItem::query()->sole();

    $this->put(route('project.bidding.update', $project), $this->biddingHierarchyPayload($project))->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertModelExists($item);
    $this->assertDatabaseCount('project_items', 1);
    expect($item->fresh()->total_amount)->toBe('25.50');
});

it('adds a child without recreating unchanged existing item rows', function () {
    $this->signInBiddingUser();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $originalItem = ProjectItem::query()->sole();
    $payload = $this->biddingHierarchyPayload($project);
    $newItem = $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0];
    unset($newItem['id']);
    $newItem['quantity'] = '3.00';
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][] = $newItem;

    $this->put(route('project.bidding.update', $project), $payload)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertModelExists($originalItem);
    $this->assertDatabaseCount('project_items', 2);
    expect($project->fresh()->calculated_total)->toBe('56.10');
});

it('removes only explicitly omitted items from an authoritative submitted stage', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][] = $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0];
    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $items = ProjectItem::query()->orderBy('id')->get();
    $update = $this->biddingHierarchyPayload($project);
    array_shift($update['lots'][0]['addresses'][0]['keystages'][0]['items']);

    $this->put(route('project.bidding.update', $project), $update)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertModelMissing($items[0]);
    $this->assertModelExists($items[1]);
    $this->assertDatabaseCount('project_items', 1);
    expect($project->fresh()->calculated_total)->toBe('25.50');
});

it('preserves an omitted nested item collection but clears an explicitly empty collection', function () {
    $this->signInBiddingUser();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $item = ProjectItem::query()->sole();
    $payload = $this->biddingHierarchyPayload($project);
    unset($payload['lots'][0]['addresses'][0]['keystages'][0]['items']);
    $this->put(route('project.bidding.update', $project), $payload)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();
    $this->assertModelExists($item);
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'] = [];

    $this->put(route('project.bidding.update', $project), $payload)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertModelMissing($item);
    $this->assertDatabaseCount('keystages', 1);
    expect($project->fresh()->calculated_total)->toBe('0.00');
});

it('rejects another bidding document child ID and rolls back the parent update', function () {
    $this->signInBiddingUser();
    $projectA = BiddingProjectFixtureFactory::new()->create(['project_name' => 'Original name']);
    $projectB = BiddingProjectFixtureFactory::new()->create();
    $lotB = BiddingLotFixtureFactory::new()->create(['project_id' => $projectB->id]);
    $itemB = BiddingItemFixtureFactory::new()->create(['lot_id' => $lotB->id]);

    $this->putJson(route('project.bidding.update', $projectA), ['project_name' => 'Unsafe replacement', 'lots' => [['id' => $lotB->id, 'lot_no' => 'Lot 1']]])->assertUnprocessable();

    expect($projectA->fresh()->project_name)->toBe('Original name');
    $this->assertModelExists($lotB);
    $this->assertModelExists($itemB);
});

it('preserves legacy lot items without inventing their missing unit costs', function () {
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $lot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id]);
    $item = BiddingItemFixtureFactory::new()->create(['lot_id' => $lot->id, 'unit_cost' => null, 'total_amount' => '45.50']);
    $payload = ['lots' => [['id' => $lot->id, 'lot_no' => 'Lot 1', 'legacy_items' => [['id' => $item->id, 'item_description' => $item->item_description, 'unit' => 'pcs', 'quantity' => '2.50', 'unit_cost' => null, 'total_amount' => '999.99']]]]];

    $this->put(route('project.bidding.update', $project), $payload)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('project_items', ['id' => $item->id, 'unit_cost' => null, 'total_amount' => '45.50', 'keystage_id' => null]);
    expect($project->fresh()->calculated_total)->toBe('45.50');
});

it('rejects changing a legacy quantity without supplying its missing cost', function () {
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $lot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id]);
    $item = BiddingItemFixtureFactory::new()->create(['lot_id' => $lot->id, 'unit_cost' => null]);

    $this->putJson(route('project.bidding.update', $project), ['lots' => [['id' => $lot->id, 'lot_no' => 'Lot 1', 'legacy_items' => [['id' => $item->id, 'quantity' => '3.00', 'unit_cost' => null]]]]])->assertUnprocessable();

    expect($item->fresh()->quantity)->toBe('2.50');
    expect($item->fresh()->unit_cost)->toBeNull();
});

it('preserves missing legacy quantity and pricing while editing metadata', function (?string $unitCost, ?string $total) {
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $lot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id]);
    $item = BiddingItemFixtureFactory::new()->create(['lot_id' => $lot->id, 'quantity' => null, 'unit_cost' => $unitCost, 'total_amount' => $total]);
    $payload = ['project_name' => 'Updated legacy metadata', 'lots' => [['id' => $lot->id, 'lot_no' => 'Lot 1', 'legacy_items' => [['id' => $item->id, 'quantity' => null, 'unit_cost' => $unitCost, 'total_amount' => '999.99', 'remarks' => 'Updated legacy remarks']]]]];

    $this->put(route('project.bidding.update', $project), $payload)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('project_items', ['id' => $item->id, 'quantity' => null, 'unit_cost' => $unitCost, 'total_amount' => $total, 'remarks' => 'Updated legacy remarks']);
    expect($project->fresh()->project_name)->toBe('Updated legacy metadata');
    expect($project->fresh()->calculated_total)->toBe($total ?? '0.00');
    $edit = $this->get(route('project.bidding.edit', $project));
    $document = new DOMDocument;
    $document->loadHTML($edit->getContent(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    $quantity = (new DOMXPath($document))->query('//input[@name="lots[0][legacy_items][0][quantity]"]')->item(0);
    expect($quantity)->not->toBeNull();
    expect($quantity->getAttribute('value'))->toBe('');
})->with(['known cost and historic total' => ['10.20', '45.50'], 'missing quantity cost and total' => [null, null]]);

it('rejects changing legacy unit cost while its quantity is missing', function () {
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create(['project_name' => 'Original legacy name']);
    $lot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id]);
    $item = BiddingItemFixtureFactory::new()->create(['lot_id' => $lot->id, 'quantity' => null, 'unit_cost' => '10.20', 'total_amount' => '45.50']);

    $this->putJson(route('project.bidding.update', $project), ['project_name' => 'Rejected legacy name', 'lots' => [['id' => $lot->id, 'lot_no' => 'Lot 1', 'legacy_items' => [['id' => $item->id, 'quantity' => null, 'unit_cost' => '11.20']]]]])->assertUnprocessable()->assertJsonValidationErrors('lots.0.legacy_items.0.quantity');

    $this->assertDatabaseHas('project_items', ['id' => $item->id, 'quantity' => null, 'unit_cost' => '10.20', 'total_amount' => '45.50']);
    expect($project->fresh()->project_name)->toBe('Original legacy name');
});

it('rejects invalid status dates and financial values before saving any bidding data', function (string $field, mixed $value) {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    data_set($payload, $field, $value);

    $this->postJson(route('project.bidding.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);

    $this->assertDatabaseCount('project_information', 0);
    $this->assertDatabaseCount('lots', 0);
    $this->assertDatabaseCount('project_items', 0);
})->with([
    'unsupported status' => ['status', 'Unknown'],
    'invalid pre-bid date' => ['date_of_pre_bid_conference', '2026-02-30'],
    'invalid opening date' => ['date_of_bid_opening', 'not-a-date'],
    'negative ABC' => ['approved_budget_contract_abc', '-1.00'],
    'overflow ABC' => ['approved_budget_contract_abc', '10000000000000.00'],
    'negative quantity' => ['lots.0.addresses.0.keystages.0.items.0.quantity', '-1.00'],
    'missing canonical quantity' => ['lots.0.addresses.0.keystages.0.items.0.quantity', null],
    'overprecision quantity' => ['lots.0.addresses.0.keystages.0.items.0.quantity', '1.234'],
    'negative unit cost' => ['lots.0.addresses.0.keystages.0.items.0.unit_cost', '-1.00'],
    'negative total' => ['lots.0.addresses.0.keystages.0.items.0.total_amount', '-1.00'],
    'invalid catalog ID' => ['lots.0.addresses.0.keystages.0.items.0.catalog_item_id', 99999],
    'missing new catalog selection' => ['lots.0.addresses.0.keystages.0.items.0.catalog_item_id', null],
]);

it('returns a friendly validation error for duplicate business project IDs without partial records', function () {
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $payload = $this->validBiddingPayload();
    $payload['project_id'] = $project->project_id;

    $this->postJson(route('project.bidding.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('project_id')->assertJsonPath('errors.project_id.0', 'A bidding document with this Project ID already exists.');

    $this->assertDatabaseCount('project_information', 1);
    $this->assertDatabaseCount('lots', 0);
});

it('rolls back the entire new hierarchy when the calculated amount exceeds DECIMAL capacity', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0]['unit_cost'] = '9999999999999.99';
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0]['quantity'] = '2.00';

    $this->postJson(route('project.bidding.store'), $payload)->assertUnprocessable();

    $this->assertDatabaseCount('project_information', 0);
    $this->assertDatabaseCount('lots', 0);
    $this->assertDatabaseCount('delivery_address', 0);
    $this->assertDatabaseCount('keystages', 0);
    $this->assertDatabaseCount('project_items', 0);
});

it('rejects a geographic child belonging to another selected region', function () {
    $this->signInBiddingUser();
    DB::table('psgc')->insert([
        ['psgc_code' => '0100000000', 'name' => 'Region A', 'geographic_level' => 'Reg', 'region_code' => '0100000000'],
        ['psgc_code' => '0201000000', 'name' => 'Province B', 'geographic_level' => 'Prov', 'region_code' => '0200000000'],
    ]);
    $payload = $this->validBiddingPayload();
    $payload['lots'][0]['region_code'] = '0100000000';
    $payload['lots'][0]['province_code'] = '0201000000';

    $this->postJson(route('project.bidding.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('lots.0.province_code');

    $this->assertDatabaseCount('project_information', 0);
});

it('requires clearing or replacing saved geographic children when changing an existing lot region', function (bool $clearChildren) {
    $this->signInBiddingUser();
    DB::table('psgc')->insert([
        ['psgc_code' => '0100000000', 'name' => 'Region A', 'geographic_level' => 'Reg', 'region_code' => '0100000000', 'province_code' => null, 'city_code' => null],
        ['psgc_code' => '0200000000', 'name' => 'Region B', 'geographic_level' => 'Reg', 'region_code' => '0200000000', 'province_code' => null, 'city_code' => null],
        ['psgc_code' => '0101000000', 'name' => 'Province A', 'geographic_level' => 'Prov', 'region_code' => '0100000000', 'province_code' => '0101000000', 'city_code' => null],
        ['psgc_code' => '0101010000', 'name' => 'City A', 'geographic_level' => 'City', 'region_code' => '0100000000', 'province_code' => '0101000000', 'city_code' => '0101010000'],
        ['psgc_code' => '0101010001', 'name' => 'Barangay A', 'geographic_level' => 'Bgy', 'region_code' => '0100000000', 'province_code' => '0101000000', 'city_code' => '0101010000'],
    ]);
    $project = BiddingProjectFixtureFactory::new()->create(['project_name' => 'Original geographic metadata']);
    $lot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id, 'region_code' => '0100000000', 'province_code' => '0101000000', 'city_code' => '0101010000', 'barangay_code' => '0101010001', 'region' => 'Region A', 'province' => 'Province A', 'city_municipality' => 'City A', 'barangay' => 'Barangay A']);
    $item = BiddingItemFixtureFactory::new()->create(['lot_id' => $lot->id]);
    $lotPayload = ['id' => $lot->id, 'lot_no' => 'Lot 1', 'region_code' => '0200000000'];
    if ($clearChildren) {
        $lotPayload += ['province_code' => null, 'city_code' => null, 'barangay_code' => null];
    }

    $response = $this->putJson(route('project.bidding.update', $project), ['project_name' => 'Updated geographic metadata', 'lots' => [$lotPayload]]);

    if ($clearChildren) {
        $response->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'region_code' => '0200000000', 'province_code' => null, 'city_code' => null, 'barangay_code' => null, 'region' => 'Region B', 'province' => null, 'city_municipality' => null, 'barangay' => null]);
        expect($project->fresh()->project_name)->toBe('Updated geographic metadata');
    } else {
        $response->assertUnprocessable()->assertJsonValidationErrors('lots.0.province_code');
        $this->assertDatabaseHas('lots', ['id' => $lot->id, 'region_code' => '0100000000', 'province_code' => '0101000000', 'city_code' => '0101010000', 'barangay_code' => '0101010001', 'region' => 'Region A', 'province' => 'Province A', 'city_municipality' => 'City A', 'barangay' => 'Barangay A']);
        expect($project->fresh()->project_name)->toBe('Original geographic metadata');
    }
    $this->assertModelExists($item);
})->with(['omitted saved children reject' => false, 'explicitly cleared children succeed' => true]);

it('preserves legacy foreign country and uncoded address snapshots through a normal edit', function (string $prefix, string $role) {
    $this->signInBiddingUser($role);
    $project = BiddingProjectFixtureFactory::new()->create();
    $lot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id, 'country' => 'Japan', 'region' => 'Kanto', 'province' => 'Tokyo', 'city_municipality' => 'Shinjuku', 'barangay' => 'Legacy district', 'delivery_address' => 'Legacy Tokyo delivery address']);
    $edit = $this->get(route($prefix.'.edit', $project))->assertSee('Japan')->assertSee('Kanto, Tokyo, Shinjuku, Legacy district')->assertSee('Legacy Tokyo delivery address');
    $document = new DOMDocument;
    $document->loadHTML($edit->getContent(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $lotElement = $xpath->query('//div[@data-bidding-lot and @data-name-prefix="lots[0]"]')->item(0);
    expect($lotElement)->not->toBeNull();
    expect($lotElement->textContent)->toContain('Japan');
    expect($xpath->query('.//*[@name="lots[0][country_code]"]', $lotElement)->length)->toBe(0);
    if (getenv('BIDDING_RENDER_HTML') === '1') {
        $directory = storage_path('framework/testing/bidding-ui');
        File::ensureDirectoryExists($directory);
        File::put($directory.'/'.str_replace('.', '-', $prefix).'-legacy-country-edit.html', $edit->getContent());
    }
    $payload = ['hierarchy_present' => '1', 'hierarchy_complete' => '1', 'project_name' => 'Updated foreign country metadata', 'lots' => [['id' => $lot->id, 'lot_no' => 'Renamed legacy lot', 'region_code' => '', 'province_code' => '', 'city_code' => '', 'barangay_code' => '', 'addresses_present' => '1', 'legacy_items_present' => '1', 'addresses' => [], 'legacy_items' => []]]];

    $this->put(route($prefix.'.update', $project), $payload)->assertRedirect(route($prefix.'.show', $project))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('lots', ['id' => $lot->id, 'lot_no' => 'Renamed legacy lot', 'country' => 'Japan', 'region' => 'Kanto', 'province' => 'Tokyo', 'city_municipality' => 'Shinjuku', 'barangay' => 'Legacy district', 'delivery_address' => 'Legacy Tokyo delivery address', 'region_code' => null, 'province_code' => null, 'city_code' => null, 'barangay_code' => null]);
    expect($project->fresh()->project_name)->toBe('Updated foreign country metadata');
    $this->assertDatabaseCount('lots', 1);
    $this->get(route($prefix.'.show', $project))->assertSee('Japan')->assertSee('Legacy Tokyo delivery address');
})->with(['operation' => ['project.bidding', 'user'], 'finance' => ['bidding', 'finance']]);

it('preserves trusted foreign geography through validation failure and native form resubmission', function (string $prefix, string $role) {
    $this->signInBiddingUser($role);
    $project = BiddingProjectFixtureFactory::new()->create();
    $otherProject = BiddingProjectFixtureFactory::new()->create();
    $lot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id, 'country' => 'Japan', 'region' => 'Kanto', 'province' => 'Tokyo', 'city_municipality' => 'Shinjuku', 'barangay' => 'Legacy district', 'delivery_address' => 'Legacy Tokyo delivery address']);
    $serializeForm = static function (string $html): array {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $form = $xpath->query('//form[@data-bidding-form]')->item(0);
        $parameters = [];
        foreach ($xpath->query('.//*[@name and not(@disabled) and not(ancestor::template) and (self::input or self::select or self::textarea)]', $form) as $control) {
            if ($control->nodeName === 'select') {
                $option = $xpath->query('./option[@selected]', $control)->item(0) ?? $xpath->query('./option', $control)->item(0);
                $value = $option?->getAttribute('value') ?? '';
            } else {
                $value = $control->nodeName === 'textarea' ? $control->textContent : $control->getAttribute('value');
            }
            $parameters[] = rawurlencode($control->getAttribute('name')).'='.rawurlencode($value);
        }
        parse_str(implode('&', $parameters), $payload);

        return $payload;
    };
    $editUrl = route($prefix.'.edit', $project);
    $payload = $serializeForm($this->get($editUrl)->getContent());
    $payload['project_id'] = $otherProject->project_id;
    $payload['project_name'] = 'Corrected foreign-country metadata';
    $this->from($editUrl)->put(route($prefix.'.update', $project), $payload)->assertRedirect($editUrl)->assertSessionHasErrors('project_id');
    $retry = $this->get($editUrl)->assertSee('Japan')->assertSee('Kanto, Tokyo, Shinjuku, Legacy district')->assertSee('Legacy Tokyo delivery address');
    $document = new DOMDocument;
    $document->loadHTML($retry->getContent(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    $lotElement = $xpath->query('//div[@data-bidding-lot and @data-name-prefix="lots[0]"]')->item(0);
    expect($lotElement)->not->toBeNull();
    foreach (['country_code', 'region_code', 'province_code', 'city_code', 'barangay_code'] as $field) {
        expect($xpath->query('.//*[@name="lots[0]['.$field.']"]', $lotElement)->length)->toBe(0);
    }
    $corrected = $serializeForm($retry->getContent());
    $corrected['project_id'] = $project->project_id;

    $this->put(route($prefix.'.update', $project), $corrected)->assertRedirect(route($prefix.'.show', $project))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('lots', ['id' => $lot->id, 'country' => 'Japan', 'region' => 'Kanto', 'province' => 'Tokyo', 'city_municipality' => 'Shinjuku', 'barangay' => 'Legacy district', 'delivery_address' => 'Legacy Tokyo delivery address', 'region_code' => null, 'province_code' => null, 'city_code' => null, 'barangay_code' => null]);
    expect($project->fresh()->project_name)->toBe('Corrected foreign-country metadata');
    $this->assertDatabaseCount('lots', 1);
    $this->assertDatabaseCount('project_information', 2);
})->with(['operation' => ['project.bidding', 'user'], 'finance' => ['bidding', 'finance']]);

it('requires authentication and appropriate operation access before changing a bidding document', function () {
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->deleteJson(route('project.bidding.destroy', $project))->assertUnauthorized();
    $this->signInBiddingUser('Viewer');

    $this->deleteJson(route('project.bidding.destroy', $project))->assertForbidden();

    $this->assertModelExists($project);
});

it('swaps existing lot labels atomically while preserving their IDs and items', function () {
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $firstLot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id, 'lot_no' => 'Lot 1']);
    $secondLot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id, 'lot_no' => 'Lot 2']);
    $firstItem = BiddingItemFixtureFactory::new()->create(['lot_id' => $firstLot->id]);
    $secondItem = BiddingItemFixtureFactory::new()->create(['lot_id' => $secondLot->id]);

    $this->put(route('project.bidding.update', $project), ['lots' => [
        ['id' => $firstLot->id, 'lot_no' => 'Lot 2'],
        ['id' => $secondLot->id, 'lot_no' => 'Lot 1'],
    ]])->assertRedirect()->assertSessionHasNoErrors();

    expect($firstLot->fresh()->lot_no)->toBe('Lot 2');
    expect($secondLot->fresh()->lot_no)->toBe('Lot 1');
    $this->assertModelExists($firstItem);
    $this->assertModelExists($secondItem);
});

it('deletes an explicitly removed stage and its own items while preserving sibling stages', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['lots'][0]['addresses'][0]['keystages'][] = array_replace($payload['lots'][0]['addresses'][0]['keystages'][0], ['name' => 'Keep stage']);
    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $stages = BiddingKeyStage::query()->orderBy('id')->get();
    $removedItem = $stages[0]->items()->sole();
    $keptItem = $stages[1]->items()->sole();
    $update = $this->biddingHierarchyPayload($project);
    array_shift($update['lots'][0]['addresses'][0]['keystages']);

    $this->put(route('project.bidding.update', $project), $update)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertModelMissing($stages[0]);
    $this->assertModelMissing($removedItem);
    $this->assertModelExists($stages[1]);
    $this->assertModelExists($keptItem);
    $this->assertDatabaseCount('project_items', 1);
});

it('deletes an explicitly removed address and its stages and items while retaining siblings', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['lots'][0]['addresses'][] = array_replace($payload['lots'][0]['addresses'][0], ['delivery_address' => 'Keep address']);
    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $addresses = BiddingDeliveryAddress::query()->orderBy('id')->get();
    $removedStage = $addresses[0]->keystages()->sole();
    $removedItem = $removedStage->items()->sole();
    $keptStage = $addresses[1]->keystages()->sole();
    $keptItem = $keptStage->items()->sole();
    $update = $this->biddingHierarchyPayload($project);
    array_shift($update['lots'][0]['addresses']);

    $this->put(route('project.bidding.update', $project), $update)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertModelMissing($addresses[0]);
    $this->assertModelMissing($removedStage);
    $this->assertModelMissing($removedItem);
    $this->assertModelExists($addresses[1]);
    $this->assertModelExists($keptStage);
    $this->assertModelExists($keptItem);
});

it('deletes only an explicitly removed lot hierarchy while retaining another document', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['lots'][] = array_replace($payload['lots'][0], ['lot_no' => 'Keep lot']);
    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $lots = $project->lots()->orderBy('id')->get();
    $removedAddress = $lots[0]->addresses()->sole();
    $removedStage = $removedAddress->keystages()->sole();
    $removedItem = $removedStage->items()->sole();
    $otherProject = BiddingProjectFixtureFactory::new()->create();
    $otherLot = BiddingLotFixtureFactory::new()->create(['project_id' => $otherProject->id]);
    $otherItem = BiddingItemFixtureFactory::new()->create(['lot_id' => $otherLot->id]);
    $update = $this->biddingHierarchyPayload($project);
    $update['lots'] = array_values(array_filter($update['lots'], fn (array $lot): bool => $lot['id'] !== $lots[0]->id));

    $response = $this->put(route('project.bidding.update', $project), $update);
    $response->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertModelMissing($lots[0]);
    $this->assertModelMissing($removedAddress);
    $this->assertModelMissing($removedStage);
    $this->assertModelMissing($removedItem);
    $this->assertModelExists($lots[1]);
    $this->assertModelExists($otherItem);
    $this->assertModelExists($otherProject);
});

it('clears explicitly submitted optional metadata while preserving omitted dates and status', function () {
    $this->signInBiddingUser();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();

    $this->put(route('project.bidding.update', $project), ['prepared_by' => null, 'notes_special_condition' => null])->assertRedirect()->assertSessionHasNoErrors();

    expect($project->fresh()->prepared_by)->toBeNull();
    expect($project->fresh()->notes_special_condition)->toBeNull();
    expect($project->fresh()->prepared_date)->toBe('2026-09-30');
    expect($project->fresh()->status)->toBe('Draft');
    $this->assertDatabaseCount('project_items', 1);
});

it('rounds authoritative fractional currency with exact decimal arithmetic', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0]['quantity'] = '8.50';
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0]['unit_cost'] = '19.99';

    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();

    expect(ProjectItem::query()->sole()->total_amount)->toBe('169.92');
    expect(ProjectInformation::query()->sole()->calculated_total)->toBe('169.92');
    expect(ProjectInformation::query()->sole()->approved_budget_contract_abc)->toBe('1000.00');
});

it('rejects an aggregate calculated total that exceeds capacity even if each item is within range', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0]['quantity'] = '1.00';
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0]['unit_cost'] = '9999999999999.99';
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][] = $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0];

    $this->postJson(route('project.bidding.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('lots');

    $this->assertDatabaseCount('project_information', 0);
    $this->assertDatabaseCount('project_items', 0);
});

it('refuses operation writes when the selected company does not provide MMC membership', function () {
    $this->signInBiddingUser('user', 'MI');

    $this->postJson(route('project.bidding.store'), $this->validBiddingPayload())->assertForbidden();

    $this->assertDatabaseCount('project_information', 0);
});

it('preserves existing finance access to shared bidding records independently of operation company context', function () {
    $this->signInBiddingUser('finance', 'MI');
    $payload = $this->validBiddingPayload();

    $response = $this->post(route('bidding.store'), $payload);

    $project = ProjectInformation::query()->sole();
    $response->assertRedirect(route('bidding.show', $project));
    $this->assertDatabaseCount('project_items', 1);
});

it('rejects duplicate lot labels differing only in case without partial inserts', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['lots'][] = array_replace($payload['lots'][0], ['lot_no' => 'LOT 1']);

    $this->postJson(route('project.bidding.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('lots.1.lot_no');

    $this->assertDatabaseCount('project_information', 0);
    $this->assertDatabaseCount('lots', 0);
});

it('rejects a truncated HTML hierarchy without changing existing children', function () {
    $this->signInBiddingUser();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $item = ProjectItem::query()->sole();
    $payload = $this->biddingHierarchyPayload($project);
    $payload['hierarchy_present'] = '1';
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items'] = [];

    $this->putJson(route('project.bidding.update', $project), $payload)->assertUnprocessable()->assertJsonValidationErrors('hierarchy_complete');

    $this->assertModelExists($item);
    expect($project->fresh()->calculated_total)->toBe('25.50');
});

it('converts a duplicate caught by the final database constraint to a friendly error', function () {
    $project = BiddingProjectFixtureFactory::new()->create();
    $payload = $this->validBiddingPayload();
    $payload['project_id'] = $project->project_id;

    try {
        app(BiddingService::class)->store($payload);
        test()->fail('The final unique constraint must refuse the second document.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe(['project_id' => ['A bidding document with this Project ID already exists.']]);
    }

    $this->assertDatabaseCount('project_information', 1);
    $this->assertDatabaseCount('lots', 0);
    $this->assertDatabaseCount('project_items', 0);
});

it('renders the canonical create edit and show views for operation and finance', function (string $prefix, string $role) {
    $this->signInBiddingUser($role);
    $create = $this->get(route($prefix.'.create'))->assertSee('name="status"', false)->assertSee('data-bidding-form', false);
    $this->post(route($prefix.'.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();

    $edit = $this->get(route($prefix.'.edit', $project))->assertSee('name="status"', false)->assertSee('Fixture delivery address')->assertSee('Fixture key stage')->assertSee('Fixture catalog description')->assertSee('value="2.50"', false)->assertSee('value="10.20"', false);
    $show = $this->get(route($prefix.'.show', $project))->assertSee('Fixture delivery address')->assertSee('Fixture key stage')->assertSee('Fixture catalog description')->assertSee('2.50')->assertSee('10.20')->assertSee('25.50')->assertSee('1,000.00');
    if (getenv('BIDDING_RENDER_HTML') === '1') {
        $directory = storage_path('framework/testing/bidding-ui');
        File::ensureDirectoryExists($directory);
        foreach (['create' => $create, 'edit' => $edit, 'show' => $show] as $page => $response) {
            File::put($directory.'/'.str_replace('.', '-', $prefix).'-'.$page.'.html', $response->getContent());
        }
    }
})->with(['operation' => ['project.bidding', 'user'], 'finance' => ['bidding', 'finance']]);

it('renders create after invalid nested data without throwing on flashed malformed rows', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $payload['lots'] = ['malformed lot'];
    $this->from(route('project.bidding.create'))->post(route('project.bidding.store'), $payload)->assertRedirect(route('project.bidding.create'))->assertSessionHasErrors('lots.0');

    $this->get(route('project.bidding.create'))->assertSee('Create bidding document')->assertSee('data-bidding-form', false);

    $this->assertDatabaseCount('project_information', 0);
});

it('enforces the existing shared bidding policy for each supported role and company context', function (string $role, string $company, bool $allowed) {
    $user = $this->signInBiddingUser($role, $company);
    $project = BiddingProjectFixtureFactory::new()->create();
    $policy = app(ProjectInformationPolicy::class);

    expect([
        $policy->viewAny($user), $policy->create($user), $policy->view($user, $project),
        $policy->update($user, $project), $policy->delete($user, $project),
        $policy->uploadDocument($user, $project), $policy->downloadDocument($user, $project),
        $policy->deleteDocument($user, $project),
    ])->toBe(array_fill(0, 8, $allowed));
})->with([
    'operation MMC' => ['user', 'MMC', true],
    'operation MI' => ['user', 'MI', false],
    'finance MMC' => ['finance', 'MMC', true],
    'finance MI' => ['finance', 'MI', true],
    'viewer MMC' => ['Viewer', 'MMC', false],
    'viewer MI' => ['Viewer', 'MI', false],
    'admin alone MMC' => ['admin', 'MMC', false],
    'admin alone MI' => ['admin', 'MI', false],
]);

it('rejects cross-document address stage and item IDs without changing either document', function (string $field) {
    $this->signInBiddingUser();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $projectA = ProjectInformation::query()->sole();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $projectB = ProjectInformation::query()->whereKeyNot($projectA->id)->sole();
    $payload = $this->biddingHierarchyPayload($projectA);
    $foreignPayload = $this->biddingHierarchyPayload($projectB);
    $payload['project_name'] = 'Must roll back';
    data_set($payload, $field, data_get($foreignPayload, $field));

    $this->putJson(route('project.bidding.update', $projectA), $payload)->assertUnprocessable()->assertJsonValidationErrors($field);

    expect($projectA->fresh()->project_name)->toBe('Bidding workflow fixture');
    $this->assertDatabaseCount('project_items', 2);
    $this->assertDatabaseCount('keystages', 2);
    $this->assertDatabaseCount('delivery_address', 2);
    expect($projectB->fresh()->calculated_total)->toBe('25.50');
})->with([
    'address' => 'lots.0.addresses.0.id',
    'stage' => 'lots.0.addresses.0.keystages.0.id',
    'item' => 'lots.0.addresses.0.keystages.0.items.0.id',
]);

it('uses native empty-collection presence markers to remove the last item intentionally', function () {
    $this->signInBiddingUser();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $item = ProjectItem::query()->sole();
    $payload = $this->biddingHierarchyPayload($project);
    $payload['hierarchy_present'] = '1';
    $payload['hierarchy_complete'] = '1';
    $payload['lots'][0]['addresses'][0]['keystages'][0]['items_present'] = '1';
    unset($payload['lots'][0]['addresses'][0]['keystages'][0]['items']);

    $this->put(route('project.bidding.update', $project), $payload)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertModelMissing($item);
    $this->assertDatabaseCount('keystages', 1);
    expect($project->fresh()->calculated_total)->toBe('0.00');
});

it('bounds catalog search and returns stable identities with description unit and price', function () {
    $this->signInBiddingUser();
    BiddingCatalogFixtureFactory::new()->count(55)->create();
    $rareItem = BiddingCatalogFixtureFactory::new()->create(['item_name' => 'Rare catalog fixture']);
    $this->getJson(route('project.bidding.catalog'))->assertJsonCount(50, 'items')->assertJsonPath('has_more', true);

    $response = $this->getJson(route('project.bidding.catalog', ['q' => 'Rare catalog fixture']))->assertJsonCount(1, 'items')->assertJsonPath('has_more', false)->assertJsonPath('items.0.id', $rareItem->id)->assertJsonPath('items.0.description', 'Fixture catalog description')->assertJsonPath('items.0.unit', 'pcs');

    expect(array_keys($response->json('items.0')))->toBe(['id', 'item_name', 'description', 'unit', 'price']);
});

it('lists and searches actual child lots and reports successful deletion in operation and finance', function (string $prefix, string $role) {
    $this->signInBiddingUser($role);
    $project = BiddingProjectFixtureFactory::new()->create(['project_name' => 'Distinct listing fixture']);
    BiddingLotFixtureFactory::new()->create(['project_id' => $project->id, 'lot_no' => 'Lot 9', 'region' => 'Legacy region']);
    BiddingProjectFixtureFactory::new()->create(['project_name' => 'Unrelated listing fixture']);
    $this->get(route($prefix.'.index'))->assertSee('Lot 9')->assertSee('Legacy region');
    $this->get(route($prefix.'.index', ['search' => 'Lot 9']))->assertSee('Distinct listing fixture')->assertDontSee('Unrelated listing fixture')->assertViewHas('projects', fn ($projects): bool => $projects->total() === 1);

    $this->delete(route($prefix.'.destroy', $project))->assertRedirect(route($prefix.'.index'));

    $this->get(route($prefix.'.index'))->assertSee('Bidding document deleted successfully.')->assertDontSee('Distinct listing fixture');
    $this->assertModelMissing($project);
})->with(['operation' => ['project.bidding', 'user'], 'finance' => ['bidding', 'finance']]);

it('bounds the initial catalog while preserving selected inactive entries and legacy snapshots beyond its limit', function () {
    $this->signInBiddingUser();
    $catalogItems = BiddingCatalogFixtureFactory::new()->count(105)->create();
    $selectedItem = $catalogItems->last();
    $selectedItem->update(['item_name' => 'ZZZ selected inactive catalog', 'active' => false]);
    $otherSelectedItem = $catalogItems[$catalogItems->count() - 2];
    $project = BiddingProjectFixtureFactory::new()->create();
    $lot = BiddingLotFixtureFactory::new()->create(['project_id' => $project->id]);
    $item = BiddingItemFixtureFactory::new()->create(['lot_id' => $lot->id, 'catalog_item_id' => $selectedItem->id, 'item_description' => 'Original quoted snapshot', 'unit' => 'boxes']);
    BiddingItemFixtureFactory::new()->create(['lot_id' => $lot->id, 'item_no' => 2, 'catalog_item_id' => $otherSelectedItem->id]);

    $this->get(route('project.bidding.create'))->assertViewHas('catalogItems', fn ($items): bool => $items->count() === 100 && ! $items->contains('id', $selectedItem->id));
    $edit = $this->get(route('project.bidding.edit', $project))->assertViewHas('catalogItems', fn ($items): bool => $items->count() === 102 && $items->contains('id', $selectedItem->id))->assertSee('ZZZ selected inactive catalog')->assertSee('Original quoted snapshot')->assertSee('value="boxes"', false);
    if (getenv('BIDDING_RENDER_HTML') === '1') {
        $directory = storage_path('framework/testing/bidding-ui');
        File::ensureDirectoryExists($directory);
        File::put($directory.'/project-bidding-selected-edit.html', $edit->getContent());
    }
    $document = new DOMDocument;
    $document->loadHTML($edit->getContent(), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);
    foreach ([$selectedItem->id => $otherSelectedItem->id, $otherSelectedItem->id => $selectedItem->id] as $selectedId => $otherId) {
        $select = $xpath->query('//select[@data-catalog-item and option[@value="'.$selectedId.'" and @selected]]')->item(0);
        expect($select)->not->toBeNull();
        $values = [];
        foreach ($select->getElementsByTagName('option') as $option) {
            if ($option->getAttribute('value') !== '') {
                $values[] = $option->getAttribute('value');
            }
        }
        expect(count($values))->toBeLessThanOrEqual(51);
        expect($values)->toContain((string) $selectedId)->not->toContain((string) $otherId);
    }
    $this->put(route('project.bidding.update', $project), ['project_name' => 'Edited metadata'])->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('project_items', ['id' => $item->id, 'catalog_item_id' => $selectedItem->id, 'item_description' => 'Original quoted snapshot', 'unit' => 'boxes']);
});

it('preserves a canonical item snapshot after its catalog record is deleted', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $catalogId = $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0]['catalog_item_id'];
    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $item = $project->items()->sole();
    Item::query()->findOrFail($catalogId)->delete();
    expect($item->fresh()->catalog_item_id)->toBeNull();
    $update = $this->biddingHierarchyPayload($project);
    $update['project_name'] = 'Edited retained catalog snapshot';

    $this->put(route('project.bidding.update', $project), $update)->assertRedirect(route('project.bidding.show', $project))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('project_items', ['id' => $item->id, 'catalog_item_id' => null, 'item_description' => 'Fixture catalog description', 'unit' => 'pcs', 'quantity' => '2.50', 'unit_cost' => '10.20', 'total_amount' => '25.50']);
    expect($project->fresh()->calculated_total)->toBe('25.50');
    $this->assertDatabaseCount('project_items', 1);
    $this->get(route('project.bidding.edit', $project))->assertSee('Fixture catalog description')->assertSee('value="2.50"', false)->assertSee('value="10.20"', false);
});

it('rejects clearing a canonical item catalog selection while that catalog record still exists', function () {
    $this->signInBiddingUser();
    $this->post(route('project.bidding.store'), $this->validBiddingPayload())->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $item = $project->items()->sole();
    $catalogId = $item->catalog_item_id;
    $update = $this->biddingHierarchyPayload($project);
    $update['project_name'] = 'Rejected cleared selection';
    $update['lots'][0]['addresses'][0]['keystages'][0]['items'][0]['catalog_item_id'] = null;

    $this->putJson(route('project.bidding.update', $project), $update)->assertUnprocessable()->assertJsonValidationErrors('lots.0.addresses.0.keystages.0.items.0.catalog_item_id');

    expect($item->fresh()->catalog_item_id)->toBe($catalogId);
    expect($project->fresh()->project_name)->toBe('Bidding workflow fixture');
    $this->assertDatabaseCount('project_items', 1);
});

it('rejects more than twenty lots before writing any hierarchy records', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $lotTemplate = $payload['lots'][0];
    $payload['lots'] = array_map(fn (int $index): array => array_replace($lotTemplate, ['lot_no' => 'Lot '.($index + 1)]), range(0, 20));

    $this->postJson(route('project.bidding.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('lots');

    $this->assertDatabaseCount('project_information', 0);
    $this->assertDatabaseCount('lots', 0);
    $this->assertDatabaseCount('project_items', 0);
});

it('rejects more than one thousand aggregate items even when each collection is within its limit', function () {
    $this->signInBiddingUser();
    $payload = $this->validBiddingPayload();
    $itemTemplate = $payload['lots'][0]['addresses'][0]['keystages'][0]['items'][0];
    $payload['lots'][0]['addresses'][0]['keystages'] = array_map(fn (int $index): array => [
        'name' => 'Stage '.$index,
        'items' => array_fill(0, $index < 10 ? 100 : 1, $itemTemplate),
    ], range(0, 10));

    $this->postJson(route('project.bidding.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('lots');

    $this->assertDatabaseCount('project_information', 0);
    $this->assertDatabaseCount('lots', 0);
    $this->assertDatabaseCount('project_items', 0);
});
