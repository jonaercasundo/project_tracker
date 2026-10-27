<?php

use App\Models\BudgetRequest;
use App\Models\FinancialActivity;
use App\Models\Liquidation;
use App\Models\MI_Liquidation;
use App\Models\MI_LiquidationItem;
use App\Policies\BudgetRequestPolicy;
use App\Policies\TravelLiquidationPolicy;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\MIBudgetFixtureFactory;
use Tests\MILiquidationFixtureFactory;
use Tests\MITravelFixtureFactory;
use Tests\MIWorkflowTestCase;

pest()->extend(MIWorkflowTestCase::class);

it('creates a budget with the authenticated primary key and exact server totals', function () {
    $user = $this->miUser();
    $this->signInMI($user);
    $payload = $this->budgetPayload();
    $payload['status'] = 'released';
    $payload['budget_total'] = '999.99';
    $payload['items'][0]['budget_total'] = '999.99';

    $response = $this->post(route('budget_requests.store'), $payload);

    $budget = BudgetRequest::sole();
    $response->assertRedirect(route('budget_requests.show', $budget));
    expect($budget->employee_id)->toBe($user->getKey());
    expect($budget->budget_total)->toBe('0.33');
    expect($budget->status)->toBe('budget_requested');
    $this->get(route('budget_requests.show', $budget))->assertSee($budget->control_id)->assertDontSee('>Approve<', false);
    $this->get(route('budget_requests.index'))->assertViewHas('budgetRequests', fn ($requests): bool => $requests->total() === 1);
});

it('creates an ordinary liquidation with multiple items, server title, rate, and list retrieval', function () {
    Http::preventStrayRequests();
    Http::fake(['open.er-api.com/*' => Http::response(['result' => 'success', 'base_code' => 'USD', 'time_last_update_unix' => 1792886551, 'rates' => ['VND' => 25432.125]], 200)]);
    Cache::flush();
    $owner = $this->miUser();
    $this->signInMI($owner);
    $payload = $this->ordinaryPayload();
    $payload['report_title'] = 'Forged title';
    $payload['items'][] = array_merge($payload['items'][0], ['payee' => 'Second payee', 'amount_vnd' => '200.25']);
    $payload['items'][0]['ref_no'] = 'USER-CHOSEN-REFERENCE';
    $payload['items'][0]['ocr_reference'] = 'RECEIPT-7788';
    $payload['submission_key'] = (string) Str::uuid();

    $this->get(route('liquidation.create'))
        ->assertSee('Assigned when saved')
        ->assertSee('readonly', false)
        ->assertDontSee('name="items[0][ref_no]"', false);

    $response = $this->post(route('liquidation.store'), $payload);
    $report = MI_Liquidation::sole();

    $response->assertRedirect(route('liquidation.show', $report));
    expect($report->title)->toBe('October 2026 Report')
        ->and($report->reference_no)->toBe('LIQ-000001')
        ->and($report->exchange_rate)->toBe('25432.1250')
        ->and($report->exchange_rate_source)->toBe('live')
        ->and($report->company_id)->toBe($owner->currentCompany()->getKey())
        ->and($report->prepared_by)->toBe($owner->getKey())
        ->and($report->items)->toHaveCount(2)
        ->and($report->items[0]->ref_no)->toBe('LF-20261006-001')
        ->and($report->items[1]->ref_no)->toBe('LF-20261006-002')
        ->and($report->items[0]->ocr_reference)->toBe('RECEIPT-7788')
        ->and($report->total_vnd)->toBe(325.75)
        ->and($report->total_usd)->toBe(0.01)
        ->and($report->items->first()->amount_usd)->toBe(0.0);
    $this->get(route('liquidation.index'))->assertOk()->assertSee('October 2026 Report');
    $this->get(route('liquidation.edit', $report))->assertSee('Reference No.')->assertSee('LIQ-000001');
    $this->get(route('liquidation.show', $report))->assertSee('Reference No.')->assertSee('LIQ-000001');
});

it('assigns the next unique reference to expenses added while editing and preserves saved references', function () {
    Http::preventStrayRequests();
    Http::fake(['open.er-api.com/*' => Http::response(['result' => 'success', 'base_code' => 'USD', 'time_last_update_unix' => 1792886551, 'rates' => ['VND' => 25432.125]], 200)]);
    Cache::flush();
    $owner = $this->miUser();
    $this->signInMI($owner);
    $this->post(route('liquidation.store'), $this->ordinaryPayload())->assertRedirect();
    $report = MI_Liquidation::sole();
    $originalItem = $report->items()->sole();
    $this->get(route('liquidation.edit', $report))
        ->assertSee('LF-20261006-001')
        ->assertSee('readonly', false)
        ->assertDontSee('name="items[0][ref_no]"', false);

    $payload = $this->ordinaryPayload();
    $payload['items'][0]['id'] = $originalItem->getKey();
    $payload['items'][0]['ocr_reference'] = 'ORIGINAL-RECEIPT-REF';
    $payload['items'][] = array_replace($payload['items'][0], [
        'id' => null,
        'payee' => 'New expense',
        'amount_vnd' => '25.00',
        'ocr_reference' => 'NEW-RECEIPT-REF',
    ]);
    $payload['items'][0]['ref_no'] = 'ATTEMPTED-OVERWRITE';

    $this->put(route('liquidation.update', $report), $payload)->assertRedirect(route('liquidation.show', $report));

    $items = $report->items()->orderBy('line_no')->get();
    expect($items)->toHaveCount(2)
        ->and($items[0]->ref_no)->toBe('LF-20261006-001')
        ->and($items[1]->ref_no)->toBe('LF-20261006-002')
        ->and($items[0]->ocr_reference)->toBe('ORIGINAL-RECEIPT-REF')
        ->and($items[1]->ocr_reference)->toBe('NEW-RECEIPT-REF');
});

it('continues after a legacy expense reference and enforces unique expense references', function () {
    $owner = $this->miUser();
    $report = MILiquidationFixtureFactory::new()->create([
        'prepared_by' => $owner->getKey(),
        'company_id' => $owner->companies()->first()->getKey(),
    ]);
    $row = $this->ordinaryPayload()['items'][0];
    $legacyItem = $report->items()->create(array_merge($row, [
        'line_no' => 1,
        'ref_no' => 'LF-20261006-009',
    ]));

    expect(fn () => $report->items()->create(array_merge($row, [
        'line_no' => 2,
        'ref_no' => $legacyItem->ref_no,
    ])))->toThrow(QueryException::class);

    $this->signInMI($owner);
    $this->post(route('liquidation.store'), $this->ordinaryPayload())->assertRedirect();

    $newItem = MI_Liquidation::query()->latest('id')->firstOrFail()->items()->sole();
    expect($legacyItem->fresh()->ref_no)->toBe('LF-20261006-009')
        ->and($newItem->ref_no)->toBe('LF-20261006-010');
});

it('assigns sequential reference numbers from unique database-generated liquidation IDs', function () {
    $first = MI_Liquidation::query()->create(['title' => 'First reference test', 'date_prepared' => '2026-10-06', 'status' => 'Pending']);
    $second = MI_Liquidation::query()->create(['title' => 'Second reference test', 'date_prepared' => '2026-10-06', 'status' => 'Pending']);

    expect($first->reference_no)->toBe('LIQ-000001')
        ->and($second->reference_no)->toBe('LIQ-000002')
        ->and(MI_Liquidation::query()->whereNotNull('reference_no')->pluck('reference_no')->unique()->count())->toBe(2);
});

it('rejects duplicate persisted liquidation reference numbers at the database constraint', function () {
    $first = MI_Liquidation::query()->create(['title' => 'First unique reference', 'date_prepared' => '2026-10-06', 'status' => 'Pending']);
    $second = MI_Liquidation::query()->create(['title' => 'Second unique reference', 'date_prepared' => '2026-10-06', 'status' => 'Pending']);

    expect(fn () => DB::table('mi_liquidations')->where('id', $second->getKey())->update(['reference_no' => $first->reference_no]))
        ->toThrow(QueryException::class);
});

it('keeps the existing reference for liquidation record six unchanged after editing', function () {
    Http::preventStrayRequests();
    Http::fake(['open.er-api.com/*' => Http::response(['result' => 'success', 'base_code' => 'USD', 'time_last_update_unix' => 1792886551, 'rates' => ['VND' => 25432.125]], 200)]);
    Cache::flush();
    $owner = $this->miUser();
    $report = new MI_Liquidation([
        'title' => 'Legacy report six',
        'date_prepared' => '2026-10-06',
        'status' => 'Pending',
        'prepared_by' => $owner->getKey(),
        'company_id' => $owner->companies()->first()->getKey(),
    ]);
    $report->id = 6;
    $report->reference_no = 'LIQ-000006';
    $report->saveQuietly();
    $this->signInMI($owner);

    $this->get(route('liquidation.edit', $report))->assertOk()->assertSee('LIQ-000006')->assertSee('readonly', false);
    $payload = $this->ordinaryPayload();
    $payload['reference_no'] = 'FORGED-REFERENCE';
    $this->put(route('liquidation.update', $report), $payload)->assertRedirect(route('liquidation.show', $report));

    expect($report->fresh()->reference_no)->toBe('LIQ-000006');
    $this->get(route('liquidation.show', $report))->assertSee('LIQ-000006');
});

it('saves the report with conversion pending when forex lookup fails and never invents a rate', function () {
    Http::preventStrayRequests();
    Http::fake(['open.er-api.com/*' => Http::failedConnection()]);
    Cache::flush();
    $this->signInMI($this->miUser());
    $payload = $this->ordinaryPayload();
    $payload['exchange_rate'] = '26160.0000';

    $this->post(route('liquidation.store'), $payload)->assertRedirect();

    $report = MI_Liquidation::sole();
    expect($report->exchange_rate)->toBeNull()
        ->and($report->exchange_rate_source)->toBe('pending');
    $this->get(route('liquidation.forex'))->assertOk()->assertJsonPath('available', false);
});

it('uses a validated cached forex rate when the provider is unavailable', function () {
    Http::preventStrayRequests();
    Cache::flush();
    $this->signInMI($this->miUser());
    Http::fake(['open.er-api.com/*' => Http::response(['result' => 'success', 'base_code' => 'USD', 'time_last_update_unix' => 1792886551, 'rates' => ['VND' => 25123.45]], 200)]);
    $this->get(route('liquidation.forex'))->assertOk()->assertJsonPath('source', 'live');
    Cache::forget('mi.forex.usd_vnd.fresh');
    Http::fake(['open.er-api.com/*' => Http::failedConnection()]);

    $this->get(route('liquidation.forex'))->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('source', 'cached')
        ->assertJsonPath('rate', '25123.4500');
});

it('accepts the provider decimal precision and stores the rate at the database scale', function () {
    Http::preventStrayRequests();
    Http::fake(['open.er-api.com/*' => Http::response(['result' => 'success', 'base_code' => 'USD', 'time_last_update_unix' => 1792886551, 'rates' => ['VND' => 25829.651426]], 200)]);
    Cache::flush();
    $this->signInMI($this->miUser());

    $this->get(route('liquidation.forex'))->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('rate', '25829.6514');
});

it('returns field validation errors for missing data, impossible dates, and invalid amounts', function () {
    $this->signInMI($this->miUser());
    $payload = $this->ordinaryPayload();
    $payload['date_prepared'] = '2026-02-31';
    $payload['items'][0]['amount_vnd'] = '-4';
    $payload['items'][0]['payee'] = '';

    $this->from(route('liquidation.create'))->post(route('liquidation.store'), $payload)
        ->assertRedirect(route('liquidation.create'))
        ->assertSessionHasErrors(['date_prepared', 'items.0.amount_vnd', 'items.0.payee']);
    $this->assertDatabaseCount('mi_liquidations', 0);
});

it('deduplicates a repeated liquidation submission key', function () {
    Http::preventStrayRequests();
    Http::fake(['open.er-api.com/*' => Http::response(['result' => 'success', 'base_code' => 'USD', 'time_last_update_unix' => 1792886551, 'rates' => ['VND' => 25000]], 200)]);
    Cache::flush();
    $this->signInMI($this->miUser());
    $payload = $this->ordinaryPayload();
    $payload['submission_key'] = (string) Str::uuid();
    $this->post(route('liquidation.store'), $payload)->assertRedirect();
    $first = MI_Liquidation::sole();

    $this->post(route('liquidation.store'), $payload)->assertRedirect(route('liquidation.show', $first));

    $this->assertDatabaseCount('mi_liquidations', 1);
    $this->assertDatabaseCount('mi_liquidation_items', 1);
});

it('rolls back the header and items when audit recording fails and preserves submitted inputs', function () {
    Http::preventStrayRequests();
    Http::fake(['open.er-api.com/*' => Http::response(['result' => 'success', 'base_code' => 'USD', 'time_last_update_unix' => 1792886551, 'rates' => ['VND' => 25000]], 200)]);
    Cache::flush();
    $this->signInMI($this->miUser());
    $payload = $this->ordinaryPayload();
    $payload['submission_key'] = (string) Str::uuid();
    FinancialActivity::creating(fn () => throw new RuntimeException('audit unavailable'));

    $this->from(route('liquidation.create'))->post(route('liquidation.store'), $payload)
        ->assertRedirect(route('liquidation.create'))
        ->assertSessionHasInput('items.0.payee', 'Fixture payee')
        ->assertSessionHas('error');
    $this->assertDatabaseCount('mi_liquidations', 0);
    $this->assertDatabaseCount('mi_liquidation_items', 0);
});

it('persists Vietnam as the selected predefined country across budget views and approvals', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $this->get(route('budget_requests.create'))
        ->assertSee('option value="Vietnam"', false)
        ->assertSee('Others — Specify Country');
    $payload = $this->budgetPayload();
    $payload['country'] = 'Vietnam';
    $payload['custom_country'] = 'Stale value';

    $this->post(route('budget_requests.store'), $payload)->assertRedirect();
    $budget = BudgetRequest::sole();

    expect($budget->country)->toBe('Vietnam');
    $this->get(route('budget_requests.show', $budget))->assertSee('Vietnam');
    $this->get(route('budget_requests.index'))->assertSee('Vietnam');

    $this->signInMI($this->miUser('Executive'));
    $this->get(route('mi.approvals'))->assertSee('Vietnam');

    $this->signInMI($this->miUser('accounting'));
    $this->get(route('accounting.mi.budgets.show', $budget))->assertSee('Vietnam');
});

it('trims and persists a specified country and retains it when editing a legacy custom value', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $payload = $this->budgetPayload();
    $payload['country'] = 'Other';
    $payload['custom_country'] = '  Taiwan  ';
    $this->post(route('budget_requests.store'), $payload)->assertRedirect();
    $budget = BudgetRequest::sole();

    expect($budget->country)->toBe('Taiwan');
    $this->get(route('budget_requests.edit', $budget))
        ->assertSee('Others — Specify Country')
        ->assertSee('value="Taiwan"', false);

    $this->put(route('budget_requests.update', $budget), [
        'department' => 'Trading',
        'country' => 'Other',
        'custom_country' => '  Taiwan updated  ',
    ])->assertRedirect(route('budget_requests.show', $budget));

    expect($budget->fresh()->country)->toBe('Taiwan updated');
    expect($budget->fresh()->status)->toBe('budget_requested');
    $this->get(route('budget_requests.show', $budget))->assertSee('Taiwan updated');

    $this->from(route('budget_requests.edit', $budget))->put(route('budget_requests.update', $budget), [
        'department' => 'Trading',
        'country' => 'Other',
        'custom_country' => '   ',
    ])->assertRedirect(route('budget_requests.edit', $budget))->assertSessionHasErrors('custom_country');
    $this->get(route('budget_requests.edit', $budget))
        ->assertSee('value="Other" selected', false)
        ->assertSee('The custom country field is required.');
    expect($budget->fresh()->country)->toBe('Taiwan updated');
});

it('requires a specified country for Others and preserves the selection and text after validation fails', function () {
    $this->signInMI($this->miUser());
    $payload = $this->budgetPayload();
    $payload['department'] = '';
    $payload['country'] = 'Other';
    $payload['custom_country'] = '   ';

    $this->from(route('budget_requests.create'))->post(route('budget_requests.store'), $payload)
        ->assertRedirect(route('budget_requests.create'))
        ->assertSessionHasErrors(['department', 'custom_country']);

    $this->get(route('budget_requests.create'))
        ->assertSee('value="Other" selected', false)
        ->assertSee('name="custom_country"', false)
        ->assertSee('The custom country field is required.');

    $payload['custom_country'] = '  Taiwan  ';
    $this->from(route('budget_requests.create'))->post(route('budget_requests.store'), $payload)
        ->assertRedirect(route('budget_requests.create'))
        ->assertSessionHasErrors('department');
    $this->get(route('budget_requests.create'))->assertSee('value="Taiwan"', false);
    $this->assertDatabaseCount('budget_requests', 0);
});

it('denies access and mutation of another employees budget', function (string $method, string $action) {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $this->signInMI($this->miUser());

    $this->{$method}(route('budget_requests.'.$action, $budget), ['department' => 'Changed'])->assertForbidden();

    expect($budget->fresh()->department)->toBe('Design');
    $this->assertModelExists($budget);
})->with(['show' => ['get', 'show'], 'edit' => ['get', 'edit'], 'update' => ['put', 'update'], 'delete' => ['delete', 'destroy']]);

it('denies employee approval and accounting actions without changing the budget', function (string $action, string $status) {
    $user = $this->miUser();
    $this->signInMI($user);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $user->getKey(), 'status' => $status]);

    $this->post(route('budget_requests.'.$action, $budget))->assertForbidden();

    expect($budget->fresh()->status)->toBe($status);
    expect($budget->fresh()->approved_by)->toBeNull();
    expect($budget->fresh()->noted_by)->toBeNull();
    expect($budget->fresh()->released_by)->toBeNull();
})->with(['approve' => ['approve', 'budget_requested'], 'note' => ['note', 'approved'], 'release' => ['release', 'approved']]);

it('allows accounting to note and release once and the owner to receive once', function () {
    $employee = $this->miUser();
    $accountant = $this->miUser('accounting');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $employee->getKey(), 'status' => 'approved']);
    $this->signInMI($accountant);

    $this->post(route('budget_requests.release', $budget))->assertUnprocessable();
    expect($budget->fresh()->released_at)->toBeNull();
    $this->post(route('budget_requests.note', $budget))->assertRedirect();
    $notedAt = $budget->fresh()->noted_at;
    $this->post(route('budget_requests.note', $budget))->assertUnprocessable();
    expect($budget->fresh()->noted_at->equalTo($notedAt))->toBeTrue();
    expect($budget->fresh()->noted_by)->toBe($accountant->getKey());
    $this->post(route('budget_requests.release', $budget))->assertRedirect();
    expect($budget->fresh()->status)->toBe('released');
    expect($budget->fresh()->released_by)->toBe($accountant->getKey());
    $releasedAt = $budget->fresh()->released_at;
    $this->post(route('budget_requests.release', $budget))->assertUnprocessable();
    expect($budget->fresh()->released_at->equalTo($releasedAt))->toBeTrue();
    $this->signInMI($employee);
    $this->post(route('budget_requests.received', $budget))->assertRedirect();
    expect($budget->fresh()->status)->toBe('in_progress');
    $receivedAt = $budget->fresh()->received_at;
    $this->post(route('budget_requests.received', $budget))->assertUnprocessable();
    expect($budget->fresh()->received_at->equalTo($receivedAt))->toBeTrue();
});

it('rejects accounting transitions outside their required status', function (string $action, string $status) {
    $owner = $this->miUser();
    $this->signInMI($this->miUser('accounting'));
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => $status]);

    $this->post(route('budget_requests.'.$action, $budget))->assertUnprocessable();

    expect($budget->fresh()->status)->toBe($status);
})->with(['note pending' => ['note', 'budget_requested'], 'release pending' => ['release', 'budget_requested'], 'note released' => ['note', 'released']]);

it('rejects receipt by another employee and premature receipt by the owner', function () {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'approved']);
    $this->signInMI($this->miUser());
    $this->post(route('budget_requests.received', $budget))->assertForbidden();
    $this->signInMI($owner);
    $this->post(route('budget_requests.received', $budget))->assertUnprocessable();
    expect($budget->fresh()->received_at)->toBeNull();
});

it('protects signed off budgets against edits and deletion', function (string $action) {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'released']);
    $this->{$action}(route('budget_requests.'.($action === 'put' ? 'update' : 'destroy'), $budget), ['department' => 'Changed'])->assertForbidden();
    $this->assertModelExists($budget);
    expect($budget->fresh()->department)->toBe('Design');
})->with(['edit' => 'put', 'delete' => 'delete']);

it('rejects a pending budget deletion when related financial records exist', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey()]);
    $this->delete(route('budget_requests.destroy', $budget))->assertForbidden();
    $this->assertModelExists($budget);
    $this->assertModelExists($travel);
});

it('creates travel for an owned received budget with canonical receipts and server calculated variance', function (string $cash, string $expectedTotal, string $expectedVariance) {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress', 'budget_total' => '0.33']);
    $payload = $this->travelPayload($budget);
    $payload['items'][0]['actual_cash'] = $cash;
    $payload['items'][0]['receipt_attached'] = 'N/A';
    $payload['actual_total'] = '999';
    $payload['items'][0]['actual_total'] = '999';

    $response = $this->post(route('travel_liquidation.store'), $payload);

    $travel = Liquidation::sole();
    $response->assertRedirect(route('travel_liquidation.show', $travel));
    expect($travel->actual_total)->toBe($expectedTotal);
    expect($travel->variance)->toBe($expectedVariance);
    expect($travel->liquidated_by)->toBe($owner->getKey());
    expect($travel->items()->sole()->receipt_attached)->toBe('n_a');
    $this->get(route('travel_liquidation.show', $travel))->assertSee(route('travel_liquidation.pdf', $travel))->assertDontSee('Note (Accounting)');
    $this->get(route('travel_liquidation.index'))->assertSee(route('travel_liquidation.show', $travel));
    $this->post(route('travel_liquidation.store'), $payload)->assertUnprocessable();
    $this->assertDatabaseCount('liquidations', 1);
})->with(['balanced' => ['0.10', '0.33', '0.00'], 'unused' => ['0.00', '0.23', '0.10'], 'overrun' => ['0.20', '0.43', '-0.10']]);

it('rejects travel before budget receipt', function (string $status) {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => $status]);
    $this->postJson(route('travel_liquidation.store'), $this->travelPayload($budget))->assertUnprocessable();
    $this->assertDatabaseCount('liquidations', 0);
})->with(['pending' => 'budget_requested', 'approved' => 'approved', 'released' => 'released', 'closed' => 'closed']);

it('rejects travel against another employees received budget', function () {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $this->signInMI($this->miUser());
    $this->get(route('travel_liquidation.create', ['budget_request' => $budget->getKey()]))->assertForbidden();
    $this->postJson(route('travel_liquidation.store'), $this->travelPayload($budget))->assertForbidden();
    $this->assertDatabaseCount('liquidations', 0);
});

it('rejects nonexistent budgets and cross budget item identities', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $foreign = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $foreignItem = $foreign->items()->create(['expense_category' => 'Travel', 'particular' => 'Foreign item', 'budget_cash' => '1.00']);
    $payload = $this->travelPayload($budget);
    $payload['budget_request_id'] = 999999;
    $this->postJson(route('travel_liquidation.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('budget_request_id');
    $payload['budget_request_id'] = $budget->getKey();
    $payload['items'][0]['budget_request_item_id'] = $foreignItem->getKey();
    $this->postJson(route('travel_liquidation.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.budget_request_item_id');
    $this->assertDatabaseCount('liquidations', 0);
});

it('denies other employee travel reads edits deletes and pdf downloads', function (string $method, string $action) {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'draft']);
    $this->signInMI($this->miUser());
    $this->{$method}(route('travel_liquidation.'.$action, $travel), $this->travelPayload($budget))->assertForbidden();
    $this->assertModelExists($travel);
})->with(['show' => ['get', 'show'], 'edit' => ['get', 'edit'], 'update' => ['put', 'update'], 'delete' => ['delete', 'destroy'], 'pdf' => ['get', 'pdf']]);

it('allows accounting travel review once but denies employee review and unassigned final approval', function () {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey()]);
    $this->signInMI($owner);
    $this->post(route('travel_liquidation.note', $travel))->assertForbidden();
    $accountant = $this->miUser('accounting');
    $this->signInMI($accountant);
    $this->post(route('travel_liquidation.note', $travel))->assertRedirect();
    expect($travel->fresh()->noted_by)->toBe($accountant->getKey());
    expect($travel->fresh()->status)->toBe('noted');
    $this->post(route('travel_liquidation.note', $travel))->assertUnprocessable();
    $this->signInMI($owner);
    $this->post(route('travel_liquidation.approve', $travel))->assertForbidden();
    expect($budget->fresh()->status)->toBe('in_progress');
});

it('rejects foreign travel item IDs without altering either report', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $foreignBudget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey()]);
    $foreign = MITravelFixtureFactory::new()->create(['budget_request_id' => $foreignBudget->getKey(), 'liquidated_by' => $owner->getKey()]);
    $foreignItem = $foreign->items()->create($this->travelPayload($foreignBudget)['items'][0]);
    $payload = $this->travelPayload($budget);
    $payload['items'][0]['id'] = $foreignItem->getKey();
    $this->putJson(route('travel_liquidation.update', $travel), $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.id');
    expect($foreignItem->fresh()->actual_total)->toBe('0.33');
    $this->assertDatabaseCount('liquidation_items', 1);
});

it('rejects negative malformed and excessive budget money without partial inserts', function (string $amount) {
    $this->signInMI($this->miUser());
    $payload = $this->budgetPayload();
    $payload['items'][0]['budget_cash'] = $amount;
    $this->postJson(route('budget_requests.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.budget_cash');
    $this->assertDatabaseCount('budget_requests', 0);
})->with(['negative' => '-0.01', 'exponent' => '1e3', 'fractional cents' => '0.001', 'capacity' => '10000000000.00', 'malformed' => 'one']);

it('rejects aggregate overflow after valid line amounts and rolls back', function () {
    $this->signInMI($this->miUser());
    $payload = $this->budgetPayload();
    $payload['items'][0]['budget_cash'] = '9999999999.99';
    $this->postJson(route('budget_requests.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items');
    $this->assertDatabaseCount('budget_requests', 0);
    $this->assertDatabaseCount('budget_request_items', 0);
});

it('enforces MI membership and selected company before a financial write', function () {
    $this->signInMI($this->miUser('user', 'MMC'));
    $this->postJson(route('budget_requests.store'), $this->budgetPayload())->assertForbidden();
    $this->assertDatabaseCount('budget_requests', 0);
});

it('denies ordinary record access across employees and companies including accounting pdf', function (string $role, string $companyCode, string $method, string $route) {
    $owner = $this->miUser('user', $companyCode);
    $report = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey()]);
    $this->signInMI($this->miUser($role));
    $this->{$method}(route($route, $report), $this->ordinaryPayload())->assertForbidden();
    $this->assertModelExists($report);
})->with([
    'employee show' => ['user', 'MI', 'get', 'liquidation.show'],
    'employee edit' => ['user', 'MI', 'get', 'liquidation.edit'],
    'employee update' => ['user', 'MI', 'put', 'liquidation.update'],
    'employee delete' => ['user', 'MI', 'delete', 'liquidation.destroy'],
    'employee PDF' => ['user', 'MI', 'get', 'liquidation.pdf'],
    'company show' => ['accounting', 'MMC', 'get', 'accounting.mi.liquidation.show'],
    'company PDF' => ['accounting', 'MMC', 'get', 'accounting.mi.liquidation.pdf'],
]);

it('soft deletes an owned pending ordinary report while retaining its items and receipt', function () {
    Storage::fake('public');
    $owner = $this->miUser();
    $this->signInMI($owner);
    $report = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey()]);
    $path = 'liquidations/receipts/legacy.png';
    Storage::disk('public')->put($path, 'fixture receipt');
    $item = $report->items()->create(array_merge($this->ordinaryPayload()['items'][0], ['line_no' => 1, 'ref_no' => 'FIX-1', 'receipt_image' => $path]));
    $this->delete(route('liquidation.destroy', $report))->assertRedirect(route('liquidation.index'));
    $this->assertSoftDeleted($report);
    $this->assertModelExists($item);
    Storage::disk('public')->assertExists($path);
});

it('denies edits and deletion of a signed off ordinary report', function (string $method, string $action) {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $report = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey(), 'status' => 'Approved']);
    $this->{$method}(route('liquidation.'.$action, $report), $this->ordinaryPayload())->assertForbidden();
    $this->assertModelExists($report);
})->with(['edit' => ['get', 'edit'], 'update' => ['put', 'update'], 'delete' => ['delete', 'destroy']]);

it('stores new receipt uploads privately and authorizes retrieval', function () {
    Storage::fake('local');
    Storage::fake('public');
    $owner = $this->miUser();
    $this->signInMI($owner);
    $payload = $this->ordinaryPayload();
    $payload['items'][0]['receipt_image'] = UploadedFile::fake()->image('receipt.png');
    $response = $this->post(route('liquidation.store'), $payload);
    $report = MI_Liquidation::sole();
    $response->assertRedirect(route('liquidation.show', $report));
    $item = $report->items()->sole();
    expect($item->receipt_image)->toStartWith('private/liquidations/receipts/');
    Storage::disk('local')->assertExists($item->receipt_image);
    expect(Storage::disk('public')->allFiles())->toBe([]);
    $this->get(route('liquidation.receipt', $item))->assertOk();
    $this->signInMI($this->miUser());
    $this->get(route('liquidation.receipt', $item))->assertForbidden();
});

it('keeps the surviving ordinary item receipt attached by identity when rows are removed', function () {
    Storage::fake('public');
    $owner = $this->miUser();
    $this->signInMI($owner);
    $report = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey()]);
    $row = $this->ordinaryPayload()['items'][0];
    $first = $report->items()->create(array_merge($row, ['line_no' => 1, 'ref_no' => 'FIX-1', 'receipt_image' => 'liquidations/receipts/first.png']));
    $second = $report->items()->create(array_merge($row, ['line_no' => 2, 'ref_no' => 'FIX-2', 'receipt_image' => 'liquidations/receipts/second.png']));
    $payload = $this->ordinaryPayload();
    $payload['items'][0]['id'] = $second->getKey();
    $this->put(route('liquidation.update', $report), $payload)->assertRedirect(route('liquidation.show', $report));
    expect($second->fresh()->receipt_image)->toBe('liquidations/receipts/second.png');
    expect($second->fresh()->line_no)->toBe(1);
    expect($second->fresh()->ref_no)->toBe('FIX-2');
    $this->assertModelMissing($first);
});

it('returns field validation errors for invalid ordinary uploads', function (string $kind) {
    $this->signInMI($this->miUser());
    $payload = $this->ordinaryPayload();
    $payload['items'][0]['receipt_image'] = $kind === 'oversized'
        ? UploadedFile::fake()->image('receipt.png')->size(10241)
        : UploadedFile::fake()->create('receipt.pdf', 10, 'application/pdf');
    $this->postJson(route('liquidation.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.receipt_image');
    $this->assertDatabaseCount('mi_liquidations', 0);
})->with(['unsupported' => 'unsupported', 'oversized' => 'oversized']);

it('scopes accounting dashboard totals to the selected company', function () {
    $miOwner = $this->miUser();
    $otherOwner = $this->miUser('user', 'MMC');
    $miReport = MILiquidationFixtureFactory::new()->create(['prepared_by' => $miOwner->getKey(), 'company_id' => $miOwner->companies()->first()->getKey()]);
    $otherReport = MILiquidationFixtureFactory::new()->create(['prepared_by' => $otherOwner->getKey(), 'company_id' => $otherOwner->companies()->first()->getKey(), 'title' => 'Foreign report']);
    $miReport->items()->create(array_merge($this->ordinaryPayload()['items'][0], ['line_no' => 1, 'ref_no' => 'FIX-1']));
    $otherReport->items()->create(array_merge($this->ordinaryPayload()['items'][0], ['line_no' => 1, 'ref_no' => 'FIX-2', 'amount_vnd' => '999.99']));
    $this->signInMI($this->miUser('accounting'));
    $this->get(route('accounting.mi.dashboard'))->assertViewHas('totalLiquidatedVnd', '125.50')->assertViewHas('pendingCount', 1)->assertDontSee('Foreign report')->assertSee(route('accounting.mi.liquidation.index'));
});

it('keeps approval authority unassigned in all financial policies', function (string $role) {
    $user = $this->miUser($role);
    $this->signInMI($user);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $user->getKey()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $user->getKey()]);
    expect((new BudgetRequestPolicy)->approve($user, $budget))->toBeFalse();
    expect((new TravelLiquidationPolicy)->approve($user, $travel))->toBeFalse();
})->with(['employee' => 'user', 'accountant' => 'accounting', 'manager' => 'Manager', 'administrator' => 'Administrator']);

it('lets employees correct and submit missing particulars without losing budget rows', function () {
    $this->signInMI($this->miUser());
    $payload = $this->budgetPayload();
    $payload['items'][0]['expense_category'] = 'Airfare';
    $payload['items'][0]['particular'] = '';
    $payload['items'][0]['currency'] = 'PHP';
    $payload['items'][] = array_merge($payload['items'][0], ['expense_category' => 'Transportation', 'budget_cash' => '12.34']);

    $this->from(route('budget_requests.create'))->post(route('budget_requests.store'), $payload)
        ->assertRedirect(route('budget_requests.create'))->assertSessionHasErrors(['items.0.particular', 'items.1.particular']);
    $response = $this->get(route('budget_requests.create'))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    foreach ([0, 1] as $index) {
        $input = $xpath->query('//input[@name="items['.$index.'][particular]"]')->item(0);
        expect($input)->not->toBeNull();
        expect($input->getAttribute('type'))->toBe('text');
        expect($input->hasAttribute('required'))->toBeTrue();
    }
    expect($xpath->query('//input[@name="items[1][budget_cash]"]')->item(0)->getAttribute('value'))->toBe('12.34');
    expect($xpath->query('//select[@name="items[1][expense_category]"]/option[@selected]')->item(0)->getAttribute('value'))->toBe('Transportation');
    $payload['items'][0]['particular'] = 'Flight to project site';
    $payload['items'][1]['particular'] = 'Taxi to airport';
    $this->post(route('budget_requests.store'), $payload)->assertRedirect();

    $this->assertDatabaseHas('budget_request_items', ['particular' => 'Flight to project site']);
    $this->assertDatabaseHas('budget_request_items', ['particular' => 'Taxi to airport', 'budget_cash' => '12.34']);
});

it('retains and escapes particulars when another budget field fails validation', function () {
    $this->signInMI($this->miUser());
    $payload = $this->budgetPayload();
    $payload['department'] = '';
    $payload['items'][0]['particular'] = '"><script>alert(1)</script>';

    $this->from(route('budget_requests.create'))->post(route('budget_requests.store'), $payload)->assertSessionHasErrors('department');
    $this->get(route('budget_requests.create'))->assertOk()->assertSee($payload['items'][0]['particular'])->assertDontSee($payload['items'][0]['particular'], false);
    $this->assertDatabaseCount('budget_requests', 0);
});

it('renders and saves pending budget metadata without replacing its expenses', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $item = $budget->items()->create($this->budgetPayload()['items'][0]);
    $this->get(route('budget_requests.edit', $budget))->assertSee(route('budget_requests.update', $budget));
    $this->put(route('budget_requests.update', $budget), ['department' => 'Trading', 'status' => 'released'])->assertRedirect(route('budget_requests.show', $budget));
    expect($budget->fresh()->department)->toBe('Trading');
    expect($budget->fresh()->status)->toBe('budget_requested');
    expect($budget->fresh()->budget_total)->toBe('0.33');
    $this->assertModelExists($item);
});

it('renders and updates a draft travel report using its actual item identities', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress', 'budget_total' => '0.50']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'draft']);
    $item = $travel->items()->create($this->travelPayload($budget)['items'][0]);
    $this->get(route('travel_liquidation.edit', $travel))->assertSee(route('travel_liquidation.update', $travel))->assertSee('name="items[0][id]"', false);
    $payload = $this->travelPayload($budget);
    $payload['items'][0]['id'] = $item->getKey();
    $payload['items'][0]['actual_cash'] = '0.20';
    $this->put(route('travel_liquidation.update', $travel), $payload)->assertRedirect(route('travel_liquidation.show', $travel));
    expect($travel->fresh()->actual_total)->toBe('0.43');
    expect($travel->fresh()->variance)->toBe('0.07');
    $this->assertDatabaseCount('liquidation_items', 1);
});

it('directs standalone travel creation to budget selection', function () {
    $this->signInMI($this->miUser());
    $this->get(route('travel_liquidation.create'))->assertRedirect(route('budget_requests.index'))->assertSessionHas('status', 'Choose a received budget request to file a liquidation.');
});

it('rejects travel edits after review without changing expense rows', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'noted']);
    $this->putJson(route('travel_liquidation.update', $travel), $this->travelPayload($budget))->assertForbidden();
    expect($travel->fresh()->status)->toBe('noted');
    $this->assertDatabaseCount('liquidation_items', 0);
});

it('rejects duplicate travel item IDs rather than updating an expense twice', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey()]);
    $item = $travel->items()->create($this->travelPayload($budget)['items'][0]);
    $payload = $this->travelPayload($budget);
    $payload['items'][0]['id'] = $item->getKey();
    $payload['items'][] = $payload['items'][0];
    $this->putJson(route('travel_liquidation.update', $travel), $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.id');
    $this->assertDatabaseCount('liquidation_items', 1);
});

it('rejects travel aggregate overflow without inserting a liquidation', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $payload = $this->travelPayload($budget);
    $payload['items'][0]['actual_cash'] = '9999999999.99';
    $this->postJson(route('travel_liquidation.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items');
    $this->assertDatabaseCount('liquidations', 0);
    $this->assertDatabaseCount('liquidation_items', 0);
});

it('rejects missing required budget fields and invalid date ranges', function () {
    $this->signInMI($this->miUser());
    $this->postJson(route('budget_requests.store'), [])->assertUnprocessable()->assertJsonValidationErrors(['department', 'items']);
    $payload = $this->budgetPayload();
    $payload['travel_date_from'] = '2026-10-10';
    $payload['travel_date_to'] = '2026-10-01';
    $this->postJson(route('budget_requests.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('travel_date_to');
    $this->assertDatabaseCount('budget_requests', 0);
});

it('does not show other employees ordinary records or totals in their index', function () {
    $owner = $this->miUser();
    $ownReport = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey()]);
    $other = $this->miUser();
    MILiquidationFixtureFactory::new()->create(['prepared_by' => $other->getKey(), 'company_id' => $other->companies()->first()->getKey(), 'title' => 'Private other report']);
    $this->signInMI($owner);
    $this->get(route('liquidation.index'))->assertViewHas('reports', fn ($reports): bool => $reports->total() === 1)->assertViewHas('pendingCount', 1)->assertDontSee('Private other report');
    $this->get(route('liquidation.edit', $ownReport))->assertSee(route('liquidation.update', $ownReport));
});

it('rejects foreign ordinary item identities while retaining both reports', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $attributes = ['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey()];
    $report = MILiquidationFixtureFactory::new()->create($attributes);
    $foreign = MILiquidationFixtureFactory::new()->create($attributes);
    $item = $foreign->items()->create(array_merge($this->ordinaryPayload()['items'][0], ['line_no' => 1, 'ref_no' => 'FIX-1']));
    $payload = $this->ordinaryPayload();
    $payload['items'][0]['id'] = $item->getKey();
    $this->putJson(route('liquidation.update', $report), $payload)->assertUnprocessable()->assertJsonValidationErrors('items.0.id');
    $this->assertModelExists($item);
    $this->assertDatabaseCount('mi_liquidation_items', 1);
});

it('rejects ordinary aggregate capacity overflow before creating records', function () {
    $this->signInMI($this->miUser());
    $payload = $this->ordinaryPayload();
    $payload['items'][0]['amount_vnd'] = '999999999999.99';
    $payload['items'][] = array_replace($payload['items'][0], ['amount_vnd' => '0.01']);
    $this->postJson(route('liquidation.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('items');
    $this->assertDatabaseCount('mi_liquidations', 0);
});

it('does not let receipt paths escape the upload directory', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $report = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey()]);
    $item = $report->items()->create(array_merge($this->ordinaryPayload()['items'][0], ['line_no' => 1, 'ref_no' => 'FIX-1', 'receipt_image' => '../private/secrets.txt']));
    $this->get(route('liquidation.receipt', $item))->assertNotFound();
});

it('retains original receipt files and cleans new uploads when an ordinary edit rolls back', function () {
    Storage::fake('local');
    $owner = $this->miUser();
    $this->signInMI($owner);
    $report = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey()]);
    $path = 'private/liquidations/receipts/original.png';
    Storage::disk('local')->put($path, 'original receipt');
    $item = $report->items()->create(array_merge($this->ordinaryPayload()['items'][0], ['line_no' => 1, 'ref_no' => 'FIX-1', 'receipt_image' => $path]));
    $payload = $this->ordinaryPayload();
    $payload['items'][0]['id'] = $item->getKey();
    $payload['items'][0]['receipt_image'] = UploadedFile::fake()->image('replacement.png');
    $payload['items'][0]['ref_no'] = 'Attempt update';
    MI_LiquidationItem::updating(function (): void {
        throw new RuntimeException('Fixture persistence failure');
    });
    try {
        $this->putJson(route('liquidation.update', $report), $payload)->assertInternalServerError();
    } finally {
        MI_LiquidationItem::flushEventListeners();
    }
    expect($item->fresh()->receipt_image)->toBe($path);
    expect(Storage::disk('local')->allFiles())->toBe([$path]);
});

it('generates authorized ordinary and travel PDF downloads', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'budget_total' => '0.33']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey()]);
    $travel->items()->create($this->travelPayload($budget)['items'][0]);
    $response = $this->get(route('travel_liquidation.pdf', $travel))->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-');
    $report = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey()]);
    $report->items()->create(array_merge($this->ordinaryPayload()['items'][0], ['line_no' => 1, 'ref_no' => 'FIX-1']));
    $response = $this->get(route('liquidation.pdf', $report))->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF-');
    $this->signInMI($this->miUser('accounting'));
    $this->get(route('accounting.mi.liquidation.pdf', $report))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('sends accounting to its MI dashboard after company selection and switching', function (string $routeName) {
    $accountant = $this->miUser('accounting');
    $this->signInMI($accountant);
    $companyId = $accountant->companies()->first()->getKey();
    $this->post(route($routeName), ['company_id' => $companyId])->assertRedirect(route('accounting.mi.dashboard'))->assertSessionHas('company_id', $companyId);
})->with(['selection' => 'company.select.store', 'switching' => 'company.switch']);

it('hides invalid ordinary mutation links and keeps accounting navigation in its own routes', function () {
    $owner = $this->miUser();
    $this->signInMI($owner);
    $report = MILiquidationFixtureFactory::new()->create(['prepared_by' => $owner->getKey(), 'company_id' => $owner->companies()->first()->getKey(), 'status' => 'Approved']);
    $this->get(route('liquidation.show', $report))->assertDontSee(route('liquidation.edit', $report))->assertDontSee('action="'.route('liquidation.destroy', $report).'"', false);
    $this->get(route('liquidation.index'))->assertDontSee(route('liquidation.edit', $report))->assertDontSee('action="'.route('liquidation.destroy', $report).'"', false);
    $this->signInMI($this->miUser('accounting'));
    $this->get(route('accounting.mi.liquidation.show', $report))->assertSee(route('accounting.mi.liquidation.index'))->assertDontSee(route('liquidation.index').'"', false);
});
