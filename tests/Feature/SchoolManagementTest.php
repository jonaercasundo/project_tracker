<?php

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\BiddingProjectFixtureFactory;
use Tests\BiddingTestCase;

pest()->extend(BiddingTestCase::class)->in(__FILE__);

/** @extends Factory<School> */
class SchoolManagementFixtureFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return array_replace(schoolManagementPayload(), [
            'school_id' => (string) fake()->unique()->numberBetween(100000, 999999),
        ]);
    }
}

/** @return array<string, string|null> */
function schoolManagementPayload(): array
{
    return [
        'school_id' => '000123',
        'school_name' => 'Bacsil Elementary School',
        'address' => '12 Main Street',
        'contact_person' => 'Ana Reyes',
        'contact' => '09171234567',
        'municipality' => 'Bacarra',
        'division' => 'Ilocos Norte',
        'region' => 'Region I',
    ];
}

/** @param list<array<string, string|null>> $rows */
function schoolManagementCsv(array $rows): UploadedFile
{
    $stream = fopen('php://temp', 'r+');
    fputcsv($stream, array_keys(schoolManagementPayload()));
    foreach ($rows as $row) {
        fputcsv($stream, array_map(fn (string $column): ?string => $row[$column] ?? null, array_keys(schoolManagementPayload())));
    }
    rewind($stream);
    $content = stream_get_contents($stream);
    fclose($stream);

    return UploadedFile::fake()->createWithContent('schools.csv', $content);
}

/**
 * @param  list<array<string, string|null>>  $rows
 * @return array<string, mixed>
 */
function schoolManagementPreview(array $rows): array
{
    $response = test()->post(route('schools.import.preview'), ['file' => schoolManagementCsv($rows)])
        ->assertOk()->assertViewIs('schools.import')->viewData('preview');
    test()->withCookie(config('session.cookie'), session()->getId());

    return $response;
}

describe('access', function () {
    it('requires authentication for school routes', function (string $method, string $name, array $parameters) {
        $this->{$method}(route($name, $parameters))->assertRedirect(route('login'));
    })->with([
        ['get', 'schools.index', []],
        ['get', 'schools.create', []],
        ['get', 'schools.show', ['school' => '000123']],
        ['get', 'schools.edit', ['school' => '000123']],
        ['get', 'schools.import', []],
        ['get', 'schools.template', []],
        ['post', 'schools.store', []],
        ['put', 'schools.update', ['school' => '000123']],
        ['post', 'schools.import.preview', []],
        ['post', 'schools.import.confirm', []],
    ]);

    it('returns 403 to users outside the authorized role or company', function (string $role, string $company) {
        $this->signInBiddingUser($role, $company);

        $this->get(route('schools.index'))->assertForbidden();
        $this->post(route('schools.store'), schoolManagementPayload())->assertForbidden();
        $this->post(route('schools.import.preview'), ['file' => schoolManagementCsv([schoolManagementPayload()])])->assertForbidden();
        $this->assertDatabaseCount('school', 0);
    })->with([
        'wrong company' => ['user', 'MI'],
        'wrong role' => ['Accounting', 'MMC'],
    ]);
});

describe('listing and forms', function () {
    it('finds schools by a global search without exposing other records', function (string $search) {
        $this->signInBiddingUser();
        SchoolManagementFixtureFactory::new()->create(schoolManagementPayload());
        SchoolManagementFixtureFactory::new()->create(['school_name' => 'Unrelated School', 'address' => 'Other address', 'contact_person' => 'Other person']);

        $this->get(route('schools.index', ['search' => $search]))
            ->assertViewHas('schools', fn ($schools): bool => $schools->total() === 1 && $schools->first()->school_id === '000123')
            ->assertSee('Bacsil Elementary School')->assertDontSee('Unrelated School');
    })->with(['000123', 'Bacsil', '12 Main', 'Ana Reyes']);

    it('combines municipality division and region filters and keeps them on pagination links', function () {
        $this->signInBiddingUser();
        SchoolManagementFixtureFactory::new()->count(30)->create();
        SchoolManagementFixtureFactory::new()->create(['school_name' => 'Other Municipality', 'municipality' => 'Laoag']);
        SchoolManagementFixtureFactory::new()->create(['school_name' => 'Other Division', 'division' => 'Ilocos Sur']);
        SchoolManagementFixtureFactory::new()->create(['school_name' => 'Other Region', 'region' => 'Region II']);
        $filters = ['search' => 'Bacsil', 'municipality' => 'Bacarra', 'division' => 'Ilocos Norte', 'region' => 'Region I'];

        $response = $this->get(route('schools.index', $filters))
            ->assertViewHas('schools', fn ($schools): bool => $schools->total() === 30 && $schools->hasMorePages());

        parse_str(parse_url($response->viewData('schools')->nextPageUrl(), PHP_URL_QUERY), $nextPage);
        expect($nextPage)->toMatchArray($filters)->toHaveKey('page', '2');
    });

    it('renders a new form and existing school details and edit data with escaped text', function () {
        $this->signInBiddingUser();
        $school = SchoolManagementFixtureFactory::new()->create(['school_id' => '000123', 'school_name' => '<script>alert("school")</script>', 'address' => '<img src=x onerror=alert(1)>']);

        $this->get(route('schools.create'))
            ->assertSee('name="school_id"', false)->assertSee('name="contact_person"', false)
            ->assertDontSee('Project details')->assertDontSee('name="project_id"', false);
        $this->get(route('schools.show', $school))
            ->assertSee($school->school_name)->assertSee($school->address)
            ->assertDontSee($school->school_name, false)->assertDontSee($school->address, false)
            ->assertDontSee('Project ID')->assertDontSee('Project number')->assertDontSee('Total contract price');
        $this->get(route('schools.edit', $school))
            ->assertSee($school->school_name)->assertSee('000123')
            ->assertDontSee('name="project_id"', false);
        $this->get(route('schools.index'))
            ->assertSee($school->school_name)->assertDontSee($school->school_name, false)
            ->assertDontSee('Contract Price')->assertDontSee('Project No.');
    });

    it('returns 404 for a school that does not exist', function () {
        $this->signInBiddingUser();

        $this->get(route('schools.show', ['school' => 'missing']))->assertNotFound();
        $this->get(route('schools.edit', ['school' => 'missing']))->assertNotFound();
        $this->put(route('schools.update', ['school' => 'missing']), schoolManagementPayload())->assertNotFound();
    });
});

describe('saving schools', function () {
    it('creates school details and ignores removed project fields', function () {
        $this->signInBiddingUser();
        $state = ['search' => 'Bacsil', 'municipality' => 'Bacarra', 'division' => 'Ilocos Norte', 'region' => 'Region I', 'page' => '2'];
        $payload = schoolManagementPayload();

        $this->post(route('schools.store', $state), [
            ...$payload,
            'project_id' => '999',
            'project_no' => 'REMOVED',
            'project' => 'Removed project data',
            'total_contract_price' => '1000.00',
        ])
            ->assertRedirect(route('schools.index', $state))->assertSessionHas('success')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('school', $payload);
        $this->assertDatabaseHas('school', [
            'school_id' => '000123',
            'project_id' => null,
            'project_no' => null,
            'project' => null,
            'total_contract_price' => null,
        ]);
        $this->assertDatabaseCount('school', 1);
    });

    it('accepts nullable optional fields without inserting empty strings', function () {
        $this->signInBiddingUser();
        $payload = array_replace(schoolManagementPayload(), array_fill_keys(['contact_person', 'contact', 'municipality'], ''));

        $this->post(route('schools.store'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('schools.index'));

        $this->assertDatabaseHas('school', ['school_id' => '000123', 'contact_person' => null, 'contact' => null, 'municipality' => null]);
    });

    it('requires the five mandatory school fields', function () {
        $this->signInBiddingUser();

        $this->post(route('schools.store'), [])->assertInvalid([
            'school_id' => 'required', 'school_name' => 'required', 'address' => 'required', 'division' => 'required', 'region' => 'required',
        ]);

        $this->assertDatabaseCount('school', 0);
    });

    it('rejects an import template containing removed project columns', function () {
        $this->signInBiddingUser();
        $legacyFile = UploadedFile::fake()->createWithContent(
            'schools.csv',
            "school_id,project_id,project_no,project,total_contract_price,school_name,address,contact_person,contact,municipality,division,region\n000123,,,,,School,Address,,,,Division,Region\n",
        );

        $this->post(route('schools.import.preview'), ['file' => $legacyFile])->assertInvalid(['file']);

        $this->assertDatabaseCount('school', 0);
    });

    it('rejects fields exceeding the existing table string sizes', function () {
        $this->signInBiddingUser();
        $fields = ['school_id', 'school_name', 'address', 'contact_person', 'contact', 'municipality', 'division', 'region'];
        $payload = array_replace(schoolManagementPayload(), array_fill_keys($fields, str_repeat('x', 256)));

        $this->post(route('schools.store'), $payload)->assertInvalid(array_fill_keys($fields, 'greater than'));

        $this->assertDatabaseCount('school', 0);
    });

    it('refuses duplicate school IDs without overwriting the original', function () {
        $this->signInBiddingUser();
        SchoolManagementFixtureFactory::new()->create(['school_id' => '000123', 'school_name' => 'Original School']);

        $this->post(route('schools.store'), schoolManagementPayload())->assertInvalid(['school_id' => 'already']);

        $this->assertDatabaseHas('school', ['school_id' => '000123', 'school_name' => 'Original School']);
        $this->assertDatabaseCount('school', 1);
    });

    it('updates school details while preserving project and coordinate data', function () {
        $this->signInBiddingUser();
        BiddingProjectFixtureFactory::new()->create(['id' => 73]);
        $school = SchoolManagementFixtureFactory::new()->create([
            'school_id' => '000123',
            'project_id' => 73,
            'project_no' => 'EXISTING-73',
            'project' => 'Existing project data',
            'total_contract_price' => '800.00',
            'latitude' => '18.2500000',
            'longitude' => '120.6200000',
        ]);
        $state = ['search' => 'School', 'municipality' => 'Bacarra', 'division' => 'Ilocos Norte', 'region' => 'Region I', 'page' => '3'];
        $payload = array_replace(schoolManagementPayload(), ['school_name' => 'Updated School', 'address' => 'Updated address', 'contact_person' => 'Updated contact', 'contact' => '00123', 'municipality' => 'Laoag', 'division' => 'New division', 'region' => 'New region']);

        $this->put(route('schools.update', ['school' => $school, ...$state]), [
            ...$payload,
            'project_id' => '999',
            'project_no' => 'CHANGED',
            'project' => 'Changed project data',
            'total_contract_price' => '1000.00',
            'latitude' => '0',
            'longitude' => '0',
        ])
            ->assertRedirect(route('schools.index', $state))->assertSessionHas('success')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('school', $payload);
        $this->assertDatabaseHas('school', [
            'school_id' => '000123',
            'project_id' => 73,
            'project_no' => 'EXISTING-73',
            'project' => 'Existing project data',
            'total_contract_price' => '800.00',
            'latitude' => '18.2500000',
            'longitude' => '120.6200000',
        ]);
        $this->assertDatabaseCount('school', 1);
    });

    it('refuses primary key changes while preserving the existing school', function () {
        $this->signInBiddingUser();
        $school = SchoolManagementFixtureFactory::new()->create(['school_id' => '000123', 'school_name' => 'Original School']);

        $this->put(route('schools.update', $school), array_replace(schoolManagementPayload(), ['school_id' => '999999']))->assertInvalid(['school_id']);

        $this->assertDatabaseHas('school', ['school_id' => '000123', 'school_name' => 'Original School']);
        $this->assertDatabaseMissing('school', ['school_id' => '999999']);
    });

    it('shows a safe error and preserves input when a database write fails', function () {
        $this->signInBiddingUser();
        DB::unprepared("CREATE TRIGGER refuse_school_insert BEFORE INSERT ON school BEGIN SELECT RAISE(ABORT, 'private database password'); END");

        $this->from(route('schools.create'))->post(route('schools.store'), schoolManagementPayload())
            ->assertRedirect(route('schools.create'))->assertSessionHas('error')->assertSessionHasInput('school_name', 'Bacsil Elementary School');

        expect(session('error'))->not->toContain('private database password', 'SQLSTATE');
        $this->assertDatabaseCount('school', 0);
    });

    it('returns a safe 503 when a school query fails before the controller can load the record', function (string $routeName, array $parameters) {
        $this->signInBiddingUser();
        Schema::drop('school');

        $this->get(route($routeName, $parameters))->assertServiceUnavailable()
            ->assertSee('School records are temporarily unavailable. Please try again later.')
            ->assertDontSee('SQLSTATE')->assertDontSee('no such table')->assertDontSee('Connection:');
    })->with([
        'listing' => ['schools.index', []],
        'route binding' => ['schools.show', ['school' => '000123']],
    ]);
});

describe('importing schools', function () {
    it('downloads a CSV template containing the eight supported columns', function () {
        $this->signInBiddingUser();

        $response = $this->get(route('schools.template'))->assertDownload();

        expect(str_getcsv(trim($response->streamedContent(), "\xEF\xBB\xBF\r\n")))->toBe([
            'school_id', 'school_name', 'address', 'contact_person', 'contact', 'municipality', 'division', 'region',
        ]);
    });

    it('previews row errors and both duplicate sources before importing only ready rows', function () {
        $this->signInBiddingUser();
        SchoolManagementFixtureFactory::new()->create(['school_id' => 'existing', 'school_name' => 'Original existing school']);
        $preview = schoolManagementPreview([
            schoolManagementPayload(),
            array_replace(schoolManagementPayload(), ['school_name' => 'Duplicate in file']),
            array_replace(schoolManagementPayload(), ['school_id' => 'existing']),
            array_replace(schoolManagementPayload(), ['school_id' => 'bad-row', 'address' => '']),
        ]);

        expect($preview['summary'])->toMatchArray(['ready' => 1, 'duplicate' => 2, 'failed' => 1, 'total' => 4]);
        expect($preview['rows'][3])->toMatchArray(['row' => 5, 'status' => 'failed']);
        expect(implode(' ', $preview['rows'][3]['errors']))->toContain('address');
        $this->assertDatabaseCount('school', 1);

        $this->post(route('schools.import.confirm'), ['token' => $preview['token']])->assertRedirect(route('schools.import'))->assertSessionHas('import_result_token');
        $this->get(route('schools.import'))->assertViewHas('importResult', fn (array $result): bool => $result['summary']['imported'] === 1 && $result['summary']['duplicate'] === 2 && $result['summary']['failed'] === 1);

        $this->assertDatabaseHas('school', schoolManagementPayload());
        $this->assertDatabaseHas('school', ['school_id' => 'existing', 'school_name' => 'Original existing school']);
        $this->assertDatabaseCount('school', 2);
    });

    it('previews XLSX files while preserving text school IDs and contacts', function () {
        $this->signInBiddingUser();
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([array_keys(schoolManagementPayload()), array_values(schoolManagementPayload())]);
        ob_start();
        (new Xlsx($book))->save('php://output');
        $bytes = ob_get_clean();
        $book->disconnectWorksheets();

        $response = $this->post(route('schools.import.preview'), ['file' => UploadedFile::fake()->createWithContent('schools.xlsx', $bytes)])
            ->assertOk()->assertViewHas('preview');

        expect($response->viewData('preview')['rows'][0]['data'])->toMatchArray(['school_id' => '000123', 'contact' => '09171234567']);
        $this->assertDatabaseCount('school', 0);
    });

    it('rejects missing template headers before creating an import draft', function () {
        $this->signInBiddingUser();

        $this->post(route('schools.import.preview'), ['file' => UploadedFile::fake()->createWithContent('schools.csv', "school_id,school_name\n000123,Incomplete\n")])->assertInvalid(['file']);

        $this->assertDatabaseCount('school', 0);
    });

    it('rejects unsupported or invalid uploaded files', function (string $filename, string $content) {
        $this->signInBiddingUser();

        $this->post(route('schools.import.preview'), ['file' => UploadedFile::fake()->createWithContent($filename, $content)])->assertInvalid(['file']);

        $this->assertDatabaseCount('school', 0);
    })->with([
        'unsupported extension' => ['schools.txt', "school_id,school_name\n000123,School\n"],
        'corrupted workbook' => ['schools.xlsx', 'not a workbook'],
    ]);

    it('rejects files larger than five megabytes', function () {
        $this->signInBiddingUser();

        $this->post(route('schools.import.preview'), ['file' => UploadedFile::fake()->create('schools.csv', 5121, 'text/csv')])->assertInvalid(['file' => '5120']);

        $this->assertDatabaseCount('school', 0);
    });

    it('rejects imports larger than one thousand school rows', function () {
        $this->signInBiddingUser();

        $this->post(route('schools.import.preview'), ['file' => schoolManagementCsv(array_fill(0, 1001, schoolManagementPayload()))])->assertInvalid(['file']);

        $this->assertDatabaseCount('school', 0);
    });

    it('does not trust caller supplied import rows or reuse a confirmed draft', function () {
        $this->signInBiddingUser();
        $preview = schoolManagementPreview([schoolManagementPayload()]);

        $this->post(route('schools.import.confirm'), ['token' => $preview['token'], 'rows' => [array_replace(schoolManagementPayload(), ['school_id' => 'injected'])]])->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('schools.import.confirm'), ['token' => $preview['token']])->assertInvalid(['token']);

        $this->assertDatabaseHas('school', ['school_id' => '000123']);
        $this->assertDatabaseMissing('school', ['school_id' => 'injected']);
        $this->assertDatabaseCount('school', 1);
    });

    it('refuses a preview token belonging to another user', function () {
        $this->signInBiddingUser();
        $preview = schoolManagementPreview([schoolManagementPayload()]);
        $this->signInBiddingUser();

        $this->post(route('schools.import.confirm'), ['token' => $preview['token']])->assertInvalid(['token']);

        $this->assertDatabaseCount('school', 0);
    });

    it('refuses an expired preview without inserting any schools', function () {
        $this->signInBiddingUser();
        $preview = schoolManagementPreview([schoolManagementPayload()]);
        $this->travel(16)->minutes();

        $this->post(route('schools.import.confirm'), ['token' => $preview['token']])->assertInvalid(['token']);

        $this->assertDatabaseCount('school', 0);
    });

    it('skips a school created after preview without overwriting it', function () {
        $this->signInBiddingUser();
        $preview = schoolManagementPreview([schoolManagementPayload()]);
        SchoolManagementFixtureFactory::new()->create(['school_id' => '000123', 'school_name' => 'Created during review']);

        $this->post(route('schools.import.confirm'), ['token' => $preview['token']])->assertRedirect(route('schools.import'))->assertSessionHas('import_result_token');
        $this->get(route('schools.import'))->assertViewHas('importResult', fn (array $result): bool => $result['summary']['imported'] === 0 && $result['summary']['duplicate'] === 1);

        $this->assertDatabaseHas('school', ['school_id' => '000123', 'school_name' => 'Created during review']);
        $this->assertDatabaseCount('school', 1);
    });

    it('rolls back every imported row and hides internal errors when a write fails', function () {
        $this->signInBiddingUser();
        $preview = schoolManagementPreview([schoolManagementPayload(), array_replace(schoolManagementPayload(), ['school_id' => 'failure'])]);
        DB::unprepared("CREATE TRIGGER refuse_second_school BEFORE INSERT ON school WHEN NEW.school_id = 'failure' BEGIN SELECT RAISE(ABORT, 'private database password'); END");

        $this->post(route('schools.import.confirm'), ['token' => $preview['token']])->assertRedirect()->assertSessionHas('error');

        expect(session('error'))->not->toContain('private database password', 'SQLSTATE');
        $this->get(route('schools.import'))->assertViewHas('importResult', fn (array $result): bool => $result['rolled_back'] && $result['summary']['imported'] === 0 && $result['summary']['failed'] === 2);
        $this->assertDatabaseCount('school', 0);
    });
});
