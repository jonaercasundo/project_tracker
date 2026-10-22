<?php

use App\Models\Company;
use App\Models\Project;
use App\Models\ProjectInformation;
use App\Models\ProjectLifecycleTrack;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\FinancialPlanTestCase;

pest()->extend(FinancialPlanTestCase::class)->in(__FILE__);

function financeLifecycleTrack(Company $company, User $user): ProjectLifecycleTrack
{
    $identifier = fake()->unique()->numerify('####');
    $project = Project::query()->create([
        'project_name' => 'Lifecycle Finance View '.$identifier,
        'project_code' => 'FIN-LC-'.$identifier,
        'ref_no' => 'FIN-LC-REF-'.$identifier,
        'agency' => 'Finance Test Agency',
        'contract_amount' => '250.00',
        'ABC' => '300.00',
        'status' => 'Implementation',
    ]);

    $bidding = ProjectInformation::query()->create([
        'project_id' => 'FIN-LC-'.$identifier,
        'project_code' => 'FIN-LC-'.$identifier,
        'project_name' => 'Lifecycle Finance View '.$identifier,
        'procuring_entity' => 'Finance Test Agency',
        'approved_budget_contract_abc' => '350.00',
        'status' => 'Awarded',
    ]);

    return ProjectLifecycleTrack::query()->create([
        'company_id' => $company->company_id,
        'project_id' => $project->project_id,
        'project_information_id' => $bidding->getKey(),
        'scope_key' => 'project:'.$project->project_id,
        'current_stage' => 'Implementation',
        'next_action' => 'Confirm delivery schedule',
        'created_by' => $user->user_id,
    ]);
}

it('shows the existing lifecycle dashboard in the MMC Finance sidebar as a read-only view', function () {
    $user = $this->signInBiddingUser('finance');
    $company = $user->currentCompany();
    $track = financeLifecycleTrack($company, $user);

    $this->get(route('finance.operations.lifecycle.index'))
        ->assertOk()
        ->assertSee('Bidding & Project Monitoring')
        ->assertSee(route('finance.operations.lifecycle.index'))
        ->assertSee('Lifecycle Finance View')
        ->assertSee('Finance view is read only')
        ->assertDontSee('Start tracking existing records');

    $this->get(route('finance.operations.lifecycle.show', $track))
        ->assertOk()
        ->assertSee('Stage history')
        ->assertSee('Project List operational status')
        ->assertSee('Finance view is read only')
        ->assertDontSee('Save lifecycle change')
        ->assertDontSee('Save action &amp; owner')
        ->assertDontSee('Add task');
});

it('keeps finance users from writing lifecycle changes and hides another company records', function () {
    $user = $this->signInBiddingUser('finance');
    $company = $user->currentCompany();
    $track = financeLifecycleTrack($company, $user);

    $this->patch(route('operations.lifecycle.update', $track), ['stage' => 'Delivery'])->assertForbidden();

    $otherCompany = Company::query()->create(['code' => 'MI', 'name' => 'MI', 'is_active' => true]);
    $otherTrack = financeLifecycleTrack($otherCompany, $user);
    $this->get(route('finance.operations.lifecycle.show', $otherTrack))->assertNotFound();
});

it('does not expose the finance dashboard route to a user outside MMC context', function () {
    $miCompany = Company::query()->firstOrCreate(['code' => 'MI'], ['name' => 'MI', 'is_active' => true]);
    $user = User::factory()->create(['username' => fake()->unique()->userName(), 'role' => 'finance']);
    $user->assignRole(Role::findOrCreate('finance', 'web'));
    $user->companies()->attach($miCompany);
    $this->actingAs($user)->withSession(['company_id' => $miCompany->company_id]);

    $this->get(route('finance.operations.lifecycle.index'))->assertForbidden();
    $this->get(route('finance.dashboard'))->assertOk()->assertDontSee('Bidding & Project Monitoring');
});
