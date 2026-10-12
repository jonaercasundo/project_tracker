<?php

use App\Models\ProjectInformation;
use App\Models\School;
use App\Services\BiddingSchoolService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\BiddingCatalogFixtureFactory;
use Tests\BiddingTestCase;

pest()->extend(BiddingTestCase::class)->in(__FILE__);

class BiddingSchoolFixtureFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return ['school_id' => (string) fake()->unique()->numberBetween(100000, 999999), 'school_name' => 'Sample Primary School', 'region' => 'Region I', 'division' => 'Ilocos Norte', 'municipality' => 'Bacarra', 'address' => 'School Master address'];
    }
}

function schoolPackagePlan(string $basis = 'per_school'): array
{
    $item = BiddingCatalogFixtureFactory::new()->create();

    return ['packages' => [[
        'key' => (string) Str::uuid(), 'lot_no' => 'Lot 1', 'stage' => 'KS1', 'grade_alias' => 'G1toG3', 'code' => 'C1', 'quantity_basis' => $basis,
        'cartons' => 1, 'cbm' => '0.25', 'volumetric_weight' => '2', 'gross_weight' => '3',
        'items' => [['catalog_item_id' => $item->id, 'item_description' => 'Shared package item', 'unit' => 'pcs', 'quantity' => '2', 'unit_cost' => '10.50', 'brand' => '', 'remarks' => '']],
    ]]];
}

function schoolWorkspace(array $data): array
{
    return test()->postJson(route('project.bidding.schools.workspace'), $data)->assertSuccessful()->json();
}

function confirmedSchoolDraft(array $plan, array $ids, ?int $projectId = null): string
{
    $draft = schoolWorkspace(['action' => 'begin', 'project_id' => $projectId]);
    $preview = schoolWorkspace(['action' => 'preview', 'token' => $draft['token'], 'project_id' => $projectId, 'school_plan' => json_encode($plan), 'ids' => implode("\n", $ids), 'package_key' => $plan['packages'][0]['key']]);

    return schoolWorkspace(['action' => 'confirm', 'token' => $preview['token'], 'project_id' => $projectId])['token'];
}

it('looks up School Master by school ID and name without creating records', function (string $query) {
    $this->signInBiddingUser();
    BiddingSchoolFixtureFactory::new()->create(['school_id' => '134967', 'school_name' => 'Bacsil Elementary School']);

    $this->getJson(route('project.bidding.schools.search', ['q' => $query]))->assertJsonPath('data.0.school_id', '134967')->assertJsonPath('data.0.address', 'School Master address');
    $this->assertDatabaseCount('school', 1);
})->with(['134967', 'Bacsil']);

it('previews unknown and duplicate IDs and only confirms existing schools', function () {
    $this->signInBiddingUser();
    BiddingSchoolFixtureFactory::new()->create(['school_id' => '134967']);
    $plan = schoolPackagePlan();
    $draft = schoolWorkspace(['action' => 'begin']);
    $preview = schoolWorkspace(['action' => 'preview', 'token' => $draft['token'], 'school_plan' => json_encode($plan), 'ids' => "134967\n134967\n999999\n=BAD", 'package_key' => $plan['packages'][0]['key']]);

    expect($preview['summary'])->toMatchArray(['valid' => 1, 'rejected' => 3, 'added' => 1]);
    $this->assertDatabaseCount('bidding_school_assignments', 0);
    $confirmed = schoolWorkspace(['action' => 'confirm', 'token' => $preview['token']]);
    expect($confirmed['count'])->toBe(1);
    $this->assertDatabaseCount('school', 1);
});

it('stores and edits shared packages and calculates the selected quantity basis', function (string $basis, string $expected) {
    $this->signInBiddingUser();
    $schools = BiddingSchoolFixtureFactory::new()->count(2)->create();
    $plan = schoolPackagePlan($basis);
    $token = confirmedSchoolDraft($plan, $schools->pluck('school_id')->all());
    $payload = $this->validBiddingPayload();
    $payload['lots'][0]['addresses'] = [];
    $payload['school_plan'] = json_encode($plan);
    $payload['school_token'] = $token;

    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    expect($project->calculated_total)->toBe($expected);
    $this->assertDatabaseCount('bidding_packages', 1);
    $this->assertDatabaseCount('project_items', 1);
    $this->assertDatabaseCount('bidding_school_assignments', 2);
    $this->get(route('project.bidding.show', $project))->assertSee('School assignments');
    $this->get(route('project.bidding.edit', $project))->assertSee('School assignments');
    $saved = app(BiddingSchoolService::class)->plan($project);
    $draft = schoolWorkspace(['action' => 'begin', 'project_id' => $project->id]);
    $this->put(route('project.bidding.update', $project), ['school_plan' => json_encode($saved), 'school_token' => $draft['token']])->assertRedirect()->assertSessionHasNoErrors();
    expect($project->fresh()->calculated_total)->toBe($expected);
})->with([['per_school', '42.00'], ['lot_total', '21.00']]);

it('allows the same school in different key stages and lots', function () {
    $this->signInBiddingUser();
    $school = BiddingSchoolFixtureFactory::new()->create();
    $plan = schoolPackagePlan();
    $second = $plan['packages'][0];
    $second['key'] = (string) Str::uuid();
    $second['stage'] = 'KS2';
    $second['grade_alias'] = 'G4toG6';
    $third = $second;
    $third['key'] = (string) Str::uuid();
    $third['lot_no'] = 'Lot 2';
    $plan['packages'][] = $second;
    $plan['packages'][] = $third;
    $token = schoolWorkspace(['action' => 'begin'])['token'];
    foreach ($plan['packages'] as $package) {
        $preview = schoolWorkspace(['action' => 'preview', 'token' => $token, 'school_plan' => json_encode($plan), 'ids' => $school->school_id, 'package_key' => $package['key']]);
        $token = schoolWorkspace(['action' => 'confirm', 'token' => $preview['token']])['token'];
    }
    $payload = $this->validBiddingPayload();
    $payload['lots'] = [['lot_no' => 'Lot 1'], ['lot_no' => 'Lot 2']];
    $payload['school_plan'] = json_encode($plan);
    $payload['school_token'] = $token;

    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseCount('bidding_school_assignments', 3);
    $this->assertDatabaseCount('school', 1);
});

it('uploads maps previews and confirms a masterlist before saving', function () {
    $this->signInBiddingUser();
    BiddingSchoolFixtureFactory::new()->create(['school_id' => '134967']);
    $plan = schoolPackagePlan();
    $token = schoolWorkspace(['action' => 'begin'])['token'];
    $uploaded = schoolWorkspace(['action' => 'upload', 'token' => $token, 'file' => UploadedFile::fake()->createWithContent('masterlist.csv', "SchoolID,GradeLevel,Allocation\n134967,G1toG3,C1 LOT 1 KS1 G1toG3\n")]);
    $preview = schoolWorkspace(['action' => 'preview', 'token' => $uploaded['token'], 'school_plan' => json_encode($plan), 'mapping' => ['school' => 'SchoolID', 'grade' => 'GradeLevel', 'lots' => ['Allocation' => 'Lot 1']]]);

    expect($preview['summary']['valid'])->toBe(1);
    $this->assertDatabaseCount('bidding_school_assignments', 0);
    $token = schoolWorkspace(['action' => 'confirm', 'token' => $preview['token']])['token'];
    $payload = $this->validBiddingPayload();
    $payload['school_plan'] = json_encode($plan);
    $payload['school_token'] = $token;
    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('bidding_imports', ['filename' => 'masterlist.csv', 'valid_assignments' => 1]);
    $this->assertDatabaseCount('project_items', 2);
});

it('rolls back the entire bidding save when a package refers to a missing lot', function () {
    $this->signInBiddingUser();
    $plan = schoolPackagePlan();
    $plan['packages'][0]['lot_no'] = 'Lot 9';
    $token = schoolWorkspace(['action' => 'begin'])['token'];
    $payload = $this->validBiddingPayload();
    $payload['school_plan'] = json_encode($plan);
    $payload['school_token'] = $token;

    $this->post(route('project.bidding.store'), $payload)->assertSessionHasErrors('school_plan');
    $this->assertDatabaseCount('project_information', 0);
    $this->assertDatabaseCount('project_items', 0);
});

it('rejects foreign draft tokens and unauthorized users', function () {
    $this->signInBiddingUser();
    $token = schoolWorkspace(['action' => 'begin'])['token'];
    $this->signInBiddingUser();
    $this->postJson(route('project.bidding.schools.workspace'), ['action' => 'list', 'token' => $token])->assertUnprocessable()->assertJsonValidationErrors('school_token');
    $this->signInBiddingUser('user', 'MI');
    $this->getJson(route('project.bidding.schools.search'))->assertForbidden();
});

it('requires an error free replacement preview and reports removed assignments', function () {
    $this->signInBiddingUser();
    $schools = BiddingSchoolFixtureFactory::new()->count(2)->create();
    $plan = schoolPackagePlan();
    $token = confirmedSchoolDraft($plan, $schools->pluck('school_id')->all());
    $preview = schoolWorkspace(['action' => 'preview', 'token' => $token, 'school_plan' => json_encode($plan), 'ids' => $schools[0]->school_id."\n999999", 'package_key' => $plan['packages'][0]['key'], 'mode' => 'replace']);

    expect($preview['summary']['removed'])->toBe(1);
    $this->postJson(route('project.bidding.schools.workspace'), ['action' => 'confirm', 'token' => $preview['token']])->assertUnprocessable();
    $preview = schoolWorkspace(['action' => 'preview', 'token' => $token, 'school_plan' => json_encode($plan), 'ids' => $schools[0]->school_id, 'package_key' => $plan['packages'][0]['key'], 'mode' => 'replace']);
    expect(schoolWorkspace(['action' => 'confirm', 'token' => $preview['token']])['count'])->toBe(1);
});

it('paginates school assignments without rendering thousands of inputs', function () {
    $this->signInBiddingUser();
    $schools = BiddingSchoolFixtureFactory::new()->count(60)->create();
    $plan = schoolPackagePlan();
    $token = confirmedSchoolDraft($plan, $schools->pluck('school_id')->all());

    $result = schoolWorkspace(['action' => 'list', 'token' => $token, 'page' => 2]);
    expect($result['total'])->toBe(60)->and($result['rows'])->toHaveCount(25);
});

it('rejects stale edits and protects delivery linked assignments', function () {
    $this->signInBiddingUser();
    $school = BiddingSchoolFixtureFactory::new()->create();
    $plan = schoolPackagePlan();
    $token = confirmedSchoolDraft($plan, [$school->school_id]);
    $payload = $this->validBiddingPayload();
    $payload['school_plan'] = json_encode($plan);
    $payload['school_token'] = $token;
    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $project = ProjectInformation::query()->sole();
    $draft = schoolWorkspace(['action' => 'begin', 'project_id' => $project->id]);
    DB::table('bidding_packages')->update(['cartons' => 5]);

    $this->put(route('project.bidding.update', $project), ['school_plan' => json_encode($plan), 'school_token' => $draft['token']])->assertSessionHasErrors('school_plan');
    $draft = schoolWorkspace(['action' => 'begin', 'project_id' => $project->id]);
    DB::table('deliveries')->insert(['delivery_id' => 123]);
    DB::table('bidding_school_assignments')->update(['delivery_id' => 123]);
    $this->put(route('project.bidding.update', $project), ['school_plan' => json_encode($plan), 'school_token' => $draft['token']])->assertSessionHasErrors('school_plan');
    $this->assertDatabaseHas('bidding_school_assignments', ['delivery_id' => 123]);
});

it('parses an XLSX masterlist and rejects conflicting lot text', function () {
    $this->signInBiddingUser();
    BiddingSchoolFixtureFactory::new()->create(['school_id' => '134967']);
    $plan = schoolPackagePlan();
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $book->getActiveSheet()->fromArray([['ID', 'Grade', 'Package'], ['134967', 'G1toG3', 'C1 LOT 9 KS1 G1toG3']]);
    ob_start();
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
    $bytes = ob_get_clean();
    $book->disconnectWorksheets();
    $token = schoolWorkspace(['action' => 'begin'])['token'];
    $upload = schoolWorkspace(['action' => 'upload', 'token' => $token, 'file' => UploadedFile::fake()->createWithContent('masterlist.xlsx', $bytes)]);

    $preview = schoolWorkspace(['action' => 'preview', 'token' => $upload['token'], 'school_plan' => json_encode($plan), 'mapping' => ['school' => 'ID', 'grade' => 'Grade', 'lots' => ['Package' => 'Lot 1']]]);
    expect($preview['summary']['valid'])->toBe(0)->and($preview['summary']['rejected'])->toBe(1);
    expect($preview['errors'][0]['problem'])->toContain('disagrees');
});

it('escapes spreadsheet formula values in error downloads', function () {
    $this->signInBiddingUser();
    $plan = schoolPackagePlan();
    $token = schoolWorkspace(['action' => 'begin'])['token'];
    $preview = schoolWorkspace(['action' => 'preview', 'token' => $token, 'school_plan' => json_encode($plan), 'ids' => '=DANGER', 'package_key' => $plan['packages'][0]['key']]);

    $response = $this->postJson(route('project.bidding.schools.workspace'), ['action' => 'errors', 'token' => $preview['token']])->assertDownload('masterlist-errors.csv');
    expect($response->streamedContent())->toContain("'=DANGER");
});

it('reassigns a school to another package and refuses duplicate assignments', function () {
    $this->signInBiddingUser();
    $school = BiddingSchoolFixtureFactory::new()->create();
    $plan = schoolPackagePlan();
    $other = $plan['packages'][0];
    $other['key'] = (string) Str::uuid();
    $other['code'] = 'C2';
    $plan['packages'][] = $other;
    $token = confirmedSchoolDraft($plan, [$school->school_id]);

    $changed = schoolWorkspace(['action' => 'reassign', 'token' => $token, 'school_plan' => json_encode($plan), 'school_id' => $school->school_id, 'old_package_key' => $plan['packages'][0]['key'], 'package_key' => $other['key']]);
    $rows = schoolWorkspace(['action' => 'list', 'token' => $changed['token']]);
    expect($rows['rows'][0]['package_key'])->toBe($other['key']);
    $this->postJson(route('project.bidding.schools.workspace'), ['action' => 'reassign', 'token' => $changed['token'], 'school_plan' => json_encode($plan), 'school_id' => $school->school_id, 'old_package_key' => $other['key'], 'package_key' => $other['key']])->assertUnprocessable();
});

it('saves 20000 assignments with bounded queries and shared item definitions', function () {
    $this->signInBiddingUser();
    $schools = BiddingSchoolFixtureFactory::new()->count(10000)->make()->map(fn ($s) => $s->getAttributes())->all();
    foreach (array_chunk($schools, 500) as $chunk) {
        DB::table('school')->insert($chunk);
    }
    $plan = schoolPackagePlan();
    $second = $plan['packages'][0];
    $second['key'] = (string) Str::uuid();
    $second['stage'] = 'KS2';
    $second['grade_alias'] = 'G4toG6';
    $plan['packages'][] = $second;
    $payload = $this->validBiddingPayload();
    $payload['lots'][0]['addresses'] = [];
    DB::enableQueryLog();
    $token = schoolWorkspace(['action' => 'begin'])['token'];
    foreach ($plan['packages'] as $package) {
        $preview = schoolWorkspace(['action' => 'preview', 'token' => $token, 'school_plan' => json_encode($plan), 'ids' => implode("\n", array_column($schools, 'school_id')), 'package_key' => $package['key']]);
        $token = schoolWorkspace(['action' => 'confirm', 'token' => $preview['token']])['token'];
    }
    $payload['school_plan'] = json_encode($plan);
    $payload['school_token'] = $token;

    $this->post(route('project.bidding.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();
    DB::flushQueryLog();
    expect($queryCount)->toBeLessThan(250);
    $this->assertDatabaseCount('bidding_school_assignments', 20000);
    $this->assertDatabaseCount('project_items', 2);
    expect(ProjectInformation::query()->sole()->calculated_total)->toBe('420000.00');
});
