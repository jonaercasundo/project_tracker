<?php

use Tests\MIBudgetFixtureFactory;
use Tests\MITravelFixtureFactory;
use Tests\MIWorkflowTestCase;

class MIExecutiveTestCase extends MIWorkflowTestCase
{
    protected function migrateFreshUsing(): array
    {
        $options = parent::migrateFreshUsing();
        $options['--path'][] = 'database/migrations/2026_10_06_080510_enable_mi_executive_approval_workflow.php';

        return $options;
    }
}

pest()->extend(MIExecutiveTestCase::class);

it('preserves normal MI pages and own requests for users and both Executive role spellings', function (string $role) {
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
    $user = $this->miUser($role);
    $own = MIBudgetFixtureFactory::new()->create(['employee_id' => $user->getKey()]);
    $this->signInMI($user);

    foreach (['mi_app.dashboard', 'mi_app.index', 'mi_app.create', 'mi_app.settings', 'budget_requests.index', 'budget_requests.create', 'travel_liquidation.index', 'liquidation.index', 'liquidation.create'] as $route) {
        $this->get(route($route))->assertOk();
    }
    $this->get(route('budget_requests.show', $own))->assertOk();
    $this->post(route('budget_requests.store'), $this->budgetPayload())->assertRedirect();
})->with(['user', 'Executive', 'executive']);

it('allows Executive and MI administrators into approvals without granting ordinary user authority', function (string $role, bool $allowed) {
    $user = $this->miUser($role);
    $this->signInMI($user);

    $response = $this->get(route('mi.approvals'));

    if ($allowed) {
        $response->assertOk()->assertSee('Pending Approval')->assertSee('Approval');
    } else {
        $response->assertForbidden();
        expect($user->can('mi.budget.approve'))->toBeFalse();
    }
})->with([['executive', true], ['Executive', true], ['Administrator', true], ['user', false], ['accounting', false]]);

it('records reviewed budget and travel decisions with immutable actor and status history', function (string $type, string $action, string $status) {
    $owner = $this->miUser();
    $actor = $this->miUser('executive');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => $type === 'travel' ? 'in_progress' : 'budget_requested']);
    $record = $type === 'budget' ? $budget : MITravelFixtureFactory::new()->create([
        'budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'noted',
        'noted_at' => now(), 'submitted_at' => now(),
    ]);
    $this->signInMI($actor);
    $this->get(route($type === 'budget' ? 'budget_requests.processing' : 'travel_liquidation.processing', $record))->assertOk()->assertSee('Executive decision');

    $this->post(route('mi.approvals.decide', ['type' => $type, 'recordId' => $record->getKey()]), [
        'action' => $action, 'remarks' => 'Reviewed supporting documents.', 'approved_by' => $owner->getKey(), 'status' => 'closed',
    ])->assertRedirect();

    expect($record->fresh()->status)->toBe($status);
    $event = $record->activities()->sole();
    expect($event->previous_status)->toBe($type === 'budget' ? 'budget_requested' : 'noted');
    expect($event->new_status)->toBe($status);
    expect((int) $event->actor_user_id)->toBe($actor->getKey());
    expect($event->actor_name_snapshot)->toBe($actor->name);
    expect($event->actor_role_snapshot)->toContain('executive');
    expect($event->note)->toBe('Reviewed supporting documents.');
    expect($event->created_at)->not->toBeNull();
    expect(fn () => $event->update(['note' => 'Changed']))->toThrow(LogicException::class);
    if ($action === 'approve') {
        expect((int) $record->fresh()->approved_by)->toBe($actor->getKey());
    }
    if ($type === 'travel') {
        expect($budget->fresh()->status)->toBe('in_progress');
        expect($record->fresh()->settlement)->toBeNull();
    }
    if ($action !== 'approve') {
        $this->signInMI($this->miUser('accounting'));
        $this->get(route('accounting.mi.workspace', ['section' => 'returned', 'type' => $type, 'workflow_status' => $status]))
            ->assertOk()->assertViewHas('rows', fn ($rows): bool => $rows->total() === 1 && $rows->first()->status === $status);
    }
})->with([
    ['budget', 'approve', 'approved'], ['budget', 'reject', 'rejected'], ['budget', 'return', 'returned_for_revision'],
    ['travel', 'approve', 'approved'], ['travel', 'reject', 'rejected'], ['travel', 'return', 'returned_for_revision'],
]);

it('requires remarks for rejection and return without changing the request', function (string $action) {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $this->signInMI($this->miUser('executive'));
    $this->get(route('budget_requests.processing', $budget))->assertOk();

    $this->post(route('mi.approvals.decide', ['type' => 'budget', 'recordId' => $budget->getKey()]), ['action' => $action, 'remarks' => '   '])
        ->assertSessionHasErrors('remarks');

    expect($budget->fresh()->status)->toBe('budget_requested');
    expect($budget->activities()->count())->toBe(0);
})->with(['reject', 'return']);

it('requires review and refuses decisions when reviewed details changed', function (bool $openDetails) {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $this->signInMI($this->miUser('executive'));
    if ($openDetails) {
        $this->get(route('budget_requests.processing', $budget))->assertOk();
        $budget->update(['objectives' => 'Changed after review']);
    }

    $this->post(route('budget_requests.approve', $budget))->assertUnprocessable();

    expect($budget->fresh()->status)->toBe('budget_requested');
    expect($budget->activities()->count())->toBe(0);
})->with([false, true]);

it('prevents repeated decisions and preserves the original history', function (string $action) {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $this->signInMI($this->miUser('executive'));
    $this->get(route('budget_requests.processing', $budget))->assertOk();
    $url = route('mi.approvals.decide', ['type' => 'budget', 'recordId' => $budget->getKey()]);
    $payload = ['action' => $action, 'remarks' => 'Decision reason'];
    $this->post($url, $payload)->assertRedirect();

    $this->post($url, $payload)->assertUnprocessable();

    expect($budget->activities()->count())->toBe(1);
})->with(['approve', 'reject', 'return']);

it('requires each Executive to review details even when the session is reused', function () {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $this->signInMI($this->miUser('executive'));
    $this->get(route('budget_requests.processing', $budget))->assertOk();
    $this->signInMI($this->miUser('executive'));

    $this->post(route('budget_requests.approve', $budget))->assertUnprocessable();

    expect($budget->fresh()->status)->toBe('budget_requested');
    expect($budget->activities()->count())->toBe(0);
    $this->get(route('budget_requests.processing', $budget))->assertOk();
    $this->post(route('budget_requests.approve', $budget))->assertRedirect();
});

it('denies own requests foreign requests and ordinary users at both review and decision boundaries', function (string $scenario) {
    $actor = $this->miUser($scenario === 'ordinary' ? 'user' : 'executive');
    $owner = $scenario === 'self' ? $actor : $this->miUser('user', $scenario === 'foreign' ? 'MMC' : 'MI');
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'company_id' => $owner->companies()->sole()->getKey()]);
    $this->signInMI($actor);

    $review = $this->get(route('budget_requests.processing', $budget));
    if ($scenario === 'self') {
        $review->assertOk();
    } else {
        $review->assertForbidden();
    }
    $this->post(route('mi.approvals.decide', ['type' => 'budget', 'recordId' => $budget->getKey()]), ['action' => 'approve'])->assertForbidden();

    expect($budget->fresh()->status)->toBe('budget_requested');
    expect($budget->activities()->count())->toBe(0);
})->with(['self', 'foreign', 'ordinary']);

it('returns budgets for owner correction and resubmission while retaining approval history', function () {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey()]);
    $executive = $this->miUser('executive');
    $this->signInMI($executive);
    $this->get(route('budget_requests.processing', $budget))->assertOk();
    $this->post(route('mi.approvals.decide', ['type' => 'budget', 'recordId' => $budget->getKey()]), ['action' => 'return', 'remarks' => 'Clarify objectives'])->assertRedirect();
    $this->signInMI($owner);
    $this->get(route('budget_requests.show', $budget))->assertSee('Clarify objectives')->assertSee('Resubmit for approval');
    $this->put(route('budget_requests.update', $budget), ['department' => 'Design', 'objectives' => 'Corrected'])->assertRedirect();

    $this->post(route('budget_requests.resubmit', $budget))->assertRedirect();

    expect($budget->fresh()->status)->toBe('budget_requested');
    expect($budget->activities()->pluck('event')->all())->toBe(['budget_returned_for_revision', 'budget_metadata_updated', 'budget_resubmitted']);
    $this->signInMI($executive);
    $this->post(route('budget_requests.approve', $budget))->assertUnprocessable();
    $this->get(route('budget_requests.processing', $budget))->assertOk();
    $this->post(route('budget_requests.approve', $budget))->assertRedirect();
});

it('requires a fresh accounting review after a returned travel report is resubmitted', function () {
    $owner = $this->miUser();
    $budget = MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'in_progress']);
    $travel = MITravelFixtureFactory::new()->create(['budget_request_id' => $budget->getKey(), 'liquidated_by' => $owner->getKey(), 'status' => 'noted', 'noted_at' => now(), 'submitted_at' => now()]);
    $executive = $this->miUser('executive');
    $this->signInMI($executive);
    $this->get(route('travel_liquidation.processing', $travel))->assertOk();
    $this->post(route('mi.approvals.decide', ['type' => 'travel', 'recordId' => $travel->getKey()]), ['action' => 'return', 'remarks' => 'Correct receipt detail'])->assertRedirect();
    $this->signInMI($owner);
    $this->get(route('travel_liquidation.show', $travel))->assertSee('Correct receipt detail');
    $this->post(route('travel_liquidation.submit', $travel))->assertRedirect();
    expect($travel->fresh()->status)->toBe('submitted');
    expect($travel->fresh()->noted_at)->toBeNull();
    $this->signInMI($executive);

    $this->post(route('travel_liquidation.approve', $travel))->assertUnprocessable();

    $this->signInMI($this->miUser('accounting'));
    $this->post(route('travel_liquidation.note', $travel))->assertRedirect();
    $this->signInMI($executive);
    $this->get(route('travel_liquidation.processing', $travel))->assertOk();
    $this->post(route('travel_liquidation.approve', $travel))->assertRedirect();
    expect($travel->activities()->pluck('event')->all())->toBe(['liquidation_returned_for_revision', 'liquidation_submitted', 'liquidation_reviewed', 'liquidation_approved']);
});

it('shows scoped summaries filters pagination and the pending navigation badge', function () {
    $owner = $this->miUser();
    $executive = $this->miUser('executive');
    for ($index = 0; $index < 16; $index++) {
        MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'department' => 'Design']);
    }
    MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'rejected']);
    MIBudgetFixtureFactory::new()->create(['employee_id' => $owner->getKey(), 'status' => 'returned_for_revision']);
    MIBudgetFixtureFactory::new()->create(['employee_id' => $executive->getKey()]);
    $foreignOwner = $this->miUser('user', 'MMC');
    $foreign = MIBudgetFixtureFactory::new()->create(['employee_id' => $foreignOwner->getKey(), 'company_id' => $foreignOwner->companies()->sole()->getKey()]);
    $this->signInMI($executive);

    $this->get(route('mi.approvals'))->assertOk()->assertViewHas('summary', ['pending' => 16, 'approved' => 0, 'rejected' => 1, 'returned' => 1, 'total' => 18])
        ->assertViewHas('budgets', fn ($rows): bool => $rows->total() === 16 && $rows->count() === 15)->assertDontSee($foreign->control_id);
    $this->get(route('mi.approvals', ['status' => 'returned', 'type' => 'budget', 'requester' => $owner->getKey(), 'department' => 'Design']))
        ->assertViewHas('budgets', fn ($rows): bool => $rows->total() === 1)->assertViewHas('travel', fn ($rows): bool => $rows->total() === 0);
    $this->get(route('mi.approvals', ['search' => 'unmatched-search']))->assertViewHas('budgets', fn ($rows): bool => $rows->total() === 0);
    $this->get(route('mi.approvals', ['date_to' => today()->toDateString()]))->assertOk()
        ->assertViewHas('budgets', fn ($rows): bool => $rows->total() === 16);
    $this->get(route('mi.approvals', ['date_from' => today()->addDay()->toDateString()]))->assertOk()
        ->assertViewHas('budgets', fn ($rows): bool => $rows->total() === 0);
    $this->get(route('mi.approvals', ['date_from' => today()->addDay()->toDateString(), 'date_to' => today()->toDateString()]))
        ->assertSessionHasErrors('date_to');
    $this->get(route('mi.approvals', ['status' => 'invalid']))->assertSessionHasErrors('status');
});

it('does not grant Executive privileges in MMC context', function () {
    $user = $this->miUser('executive', 'MMC');
    $this->signInMI($user);

    $this->get(route('mi.approvals'))->assertForbidden();
    $this->get(route('mi_app.dashboard'))->assertForbidden();
    $this->get(route('projects.dashboard'))->assertForbidden();
});
