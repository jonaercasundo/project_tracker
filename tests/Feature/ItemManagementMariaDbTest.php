<?php

use App\Http\Middleware\CheckCompany;
use App\Http\Middleware\RoleMiddleware;
use App\Models\CatalogItem;
use App\Models\Company;
use App\Models\User;
use App\Services\ItemImportService;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__FILE__);

it('creates searches edits and imports standalone items against the configured MariaDB connection', function () {
    if (getenv('ITEM_MARIADB_TESTS') !== '1') {
        $this->markTestSkipped('Set ITEM_MARIADB_TESTS=1 to exercise item management against the configured MariaDB schema.');
    }

    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->markTestSkipped('Requires a MySQL or MariaDB connection.');
    }

    $connection = DB::connection();
    $connection->beginTransaction();

    try {
        $user = new class extends User
        {
            public function hasRole($roles, ?string $guard = null): bool
            {
                return true;
            }

            public function currentCompany(): ?Company
            {
                return null;
            }
        };
        $user->forceFill(['id' => 1]);
        $user->setRelation('companies', collect());
        $this->withoutMiddleware([Authenticate::class, CheckCompany::class, RoleMiddleware::class])
            ->actingAs($user);

        $prefix = 'IT'.Str::upper(Str::random(8));
        $payload = [
            'code_prefix' => $prefix,
            'item_name' => 'MariaDB integration item',
            'description' => 'Created only inside the integration transaction',
            'unit' => 'piece',
            'price' => '25.50',
            'supplier_price' => '20.00',
            'active' => '1',
        ];

        $this->post(route('catalog-items.store'), $payload)
            ->assertRedirect(route('catalog-items.index'))
            ->assertSessionHasNoErrors();
        $item = CatalogItem::query()->whereNull('project_id')->where('code_prefix', $prefix)->sole();

        $this->get(route('catalog-items.index', ['search' => $prefix, 'active' => '1']))
            ->assertOk()
            ->assertSee($item->item_id);

        $this->put(route('catalog-items.update', ['item' => $item->id]), array_replace($payload, [
            'item_name' => 'Updated MariaDB item',
            'price' => '30.00',
        ]))->assertRedirect(route('catalog-items.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'item_id' => $item->item_id,
            'item_name' => 'Updated MariaDB item',
            'price' => '30.00',
            'project_id' => null,
        ]);

        $importPrefix = 'IM'.Str::upper(Str::random(8));
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ItemImportService::HEADERS);
        fputcsv($stream, [$importPrefix, 'MariaDB imported item', 'Import test', 'box', '11.25', '9.50', '1']);
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);
        $preview = $this->post(route('catalog-items.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('mariadb-items.csv', $csv),
        ])->assertOk()->viewData('preview');
        $this->withCookie(config('session.cookie'), session()->getId());

        $this->post(route('catalog-items.import.confirm'), ['token' => $preview['token']])
            ->assertRedirect(route('catalog-items.import'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('items', [
            'code_prefix' => $importPrefix,
            'item_name' => 'MariaDB imported item',
            'project_id' => null,
        ]);
    } finally {
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
    }
});
