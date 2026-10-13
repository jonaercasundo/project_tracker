<?php

use App\Models\CatalogItem;
use App\Services\ItemImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\BiddingCatalogFixtureFactory;
use Tests\BiddingProjectFixtureFactory;
use Tests\BiddingTestCase;

pest()->extend(BiddingTestCase::class)->in(__FILE__);

/** @return array<string, mixed> */
function catalogItemPayload(array $overrides = []): array
{
    return array_replace([
        'code_prefix' => 'CAT',
        'item_name' => 'Test standalone item',
        'description' => 'A test item description',
        'unit' => 'piece',
        'price' => '12.50',
        'supplier_price' => '10.25',
        'active' => '1',
    ], $overrides);
}

/** @param list<array<string, mixed>> $rows */
function catalogItemsCsv(array $rows): UploadedFile
{
    $stream = fopen('php://temp', 'r+');
    fputcsv($stream, ItemImportService::HEADERS);
    foreach ($rows as $row) {
        fputcsv($stream, array_map(fn (string $column): mixed => $row[$column] ?? null, ItemImportService::HEADERS));
    }
    rewind($stream);
    $content = stream_get_contents($stream);
    fclose($stream);

    return UploadedFile::fake()->createWithContent('items.csv', $content);
}

describe('access and listing', function () {
    it('requires authentication for item management routes', function () {
        $this->get(route('catalog-items.index'))->assertRedirect(route('login'));
        $this->get(route('catalog-items.import'))->assertRedirect(route('login'));
    });

    it('searches and filters standalone records and preserves sorting and pagination', function () {
        $this->signInBiddingUser();
        foreach (range(1, 28) as $number) {
            CatalogItem::query()->create(catalogItemPayload([
                'code_prefix' => 'PAGE'.$number,
                'item_name' => 'Catalog result '.$number,
                'active' => '1',
            ]));
        }

        $response = $this->get(route('catalog-items.index', [
            'search' => 'Catalog result',
            'active' => '1',
            'sort' => 'item_name',
            'direction' => 'desc',
        ]))
            ->assertOk()
            ->assertViewHas('items', fn ($items): bool => $items->total() === 28 && $items->hasMorePages())
            ->assertSee('Catalog result 28')
            ->assertDontSee('Unrelated linked item');

        parse_str(parse_url($response->viewData('items')->nextPageUrl(), PHP_URL_QUERY), $nextPage);
        expect($nextPage)->toMatchArray([
            'search' => 'Catalog result',
            'active' => '1',
            'sort' => 'item_name',
            'direction' => 'desc',
            'page' => '2',
        ]);
    });

    it('does not expose linked catalog records in listing, detail, edit, or update routes', function () {
        $this->signInBiddingUser();
        $project = BiddingProjectFixtureFactory::new()->create();
        $linkedItem = BiddingCatalogFixtureFactory::new()->create(['item_name' => 'Unrelated linked item']);
        DB::table('items')->where('id', $linkedItem->id)->update(['project_id' => $project->id]);

        $this->get(route('catalog-items.index'))->assertOk()->assertDontSee('Unrelated linked item');
        $this->get(route('catalog-items.show', ['item' => $linkedItem->id]))->assertNotFound();
        $this->get(route('catalog-items.edit', ['item' => $linkedItem->id]))->assertNotFound();
        $this->put(route('catalog-items.update', ['item' => $linkedItem->id]), catalogItemPayload())
            ->assertNotFound();

        $this->assertDatabaseHas('items', ['id' => $linkedItem->id, 'project_id' => $project->id, 'item_name' => 'Unrelated linked item']);
    });

    it('validates requested sort columns against supported item attributes', function () {
        $this->signInBiddingUser();

        $this->get(route('catalog-items.index', ['sort' => 'item_name desc']))
            ->assertSessionHasErrors('sort');
    });
});

describe('saving items', function () {
    it('creates a standalone item and ignores unsupported request attributes', function () {
        $this->signInBiddingUser();

        $response = $this->post(route('catalog-items.store', [
            'search' => 'Test',
            'active' => '1',
            'sort' => 'item_name',
            'direction' => 'desc',
            'page' => '2',
        ]), [
            ...catalogItemPayload(['code_prefix' => ' new ']),
            'project_id' => 'not-accepted',
            'delivery_address_id' => 'not-accepted',
            'lot_id' => 'not-accepted',
            'keystage_id' => 'not-accepted',
        ]);

        $response->assertRedirect(route('catalog-items.index', [
            'search' => 'Test',
            'active' => '1',
            'sort' => 'item_name',
            'direction' => 'desc',
            'page' => '2',
        ]))->assertSessionHas('success')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('items', [
            'item_id' => 'NEW-0001',
            'code_prefix' => 'NEW',
            'item_name' => 'Test standalone item',
            'project_id' => null,
        ]);
        $this->assertDatabaseCount('items', 1);
    });

    it('updates item attributes while preserving generated identifier and the list state', function () {
        $this->signInBiddingUser();
        $item = CatalogItem::query()->create(catalogItemPayload(['code_prefix' => 'EDIT']));
        $state = ['search' => 'Test', 'active' => '1', 'sort' => 'item_name', 'direction' => 'desc', 'page' => '2'];

        $this->put(route('catalog-items.update', ['item' => $item->id, ...$state]), [
            ...catalogItemPayload(['item_name' => 'Updated standalone item', 'price' => '20.00']),
            'item_id' => 'CHANGED-0001',
            'project_id' => '999',
        ])->assertRedirect(route('catalog-items.index', $state))->assertSessionHas('success')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'item_id' => 'EDIT-0001',
            'item_name' => 'Updated standalone item',
            'price' => '20.00',
            'project_id' => null,
        ]);
        $this->assertDatabaseMissing('items', ['item_id' => 'CHANGED-0001']);
    });

    it('rejects missing required fields and negative or over-precision prices', function () {
        $this->signInBiddingUser();

        $this->from(route('catalog-items.create'))
            ->post(route('catalog-items.store'), [])
            ->assertSessionHasErrors(['code_prefix', 'item_name', 'price', 'supplier_price', 'active']);

        $this->from(route('catalog-items.create'))
            ->post(route('catalog-items.store'), catalogItemPayload(['price' => '-1.00', 'supplier_price' => '1.234']))
            ->assertSessionHasErrors(['price', 'supplier_price']);

        $this->assertDatabaseCount('items', 0);
    });

    it('renders supported standalone item fields without exposing project or relationship inputs', function () {
        $this->signInBiddingUser();
        $item = CatalogItem::query()->create(catalogItemPayload(['item_name' => '<script>alert("item")</script>']));

        $this->get(route('catalog-items.create'))
            ->assertSee('name="code_prefix"', false)
            ->assertSee('name="supplier_price"', false)
            ->assertDontSee('name="project_id"', false)
            ->assertDontSee('name="lot_id"', false);
        $this->get(route('catalog-items.show', ['item' => $item->id]))
            ->assertSee('&lt;script&gt;')
            ->assertDontSee('<script>alert("item")</script>', false)
            ->assertDontSee('project_id');
        $this->get(route('catalog-items.edit', ['item' => $item->id]))
            ->assertSee($item->item_id)
            ->assertDontSee('name="project_id"', false);
    });
});

describe('item imports', function () {
    it('downloads a template with only the supported item columns', function () {
        $this->signInBiddingUser();

        $response = $this->get(route('catalog-items.template'))->assertDownload('item-import-template.csv');

        expect(str_getcsv(trim($response->streamedContent(), "\xEF\xBB\xBF\r\n")))
            ->toBe(ItemImportService::HEADERS);
    });

    it('previews file duplicates, existing duplicates, and invalid rows before importing ready items', function () {
        $this->signInBiddingUser();
        CatalogItem::query()->create(catalogItemPayload(['code_prefix' => 'OLD', 'item_name' => 'Existing standalone item']));
        $ready = catalogItemPayload(['code_prefix' => 'NEW', 'item_name' => 'New imported item']);

        $preview = $this->post(route('catalog-items.import.preview'), ['file' => catalogItemsCsv([
            $ready,
            $ready,
            catalogItemPayload(['code_prefix' => 'OLD', 'item_name' => 'Existing standalone item']),
            catalogItemPayload(['item_name' => 'Invalid import', 'price' => '-2']),
        ])])->assertOk()->assertViewIs('items.import')->viewData('preview');

        expect($preview['summary'])->toMatchArray(['ready' => 1, 'skipped' => 2, 'failed' => 1, 'total' => 4]);
        expect($preview['rows'][3])->toMatchArray(['row' => 5, 'status' => 'failed']);
        expect(implode(' ', $preview['rows'][3]['errors']))->toContain('price');
        $this->assertDatabaseCount('items', 1);

        $this->post(route('catalog-items.import.confirm'), ['token' => $preview['token']])
            ->assertRedirect(route('catalog-items.import'))
            ->assertSessionHas('import_result_token');
        $this->get(route('catalog-items.import'))
            ->assertViewHas('importResult', fn (array $result): bool => $result['summary']['imported'] === 1
                && $result['summary']['skipped'] === 2 && $result['summary']['failed'] === 1);

        $this->assertDatabaseHas('items', ['item_id' => 'NEW-0001', 'item_name' => 'New imported item', 'project_id' => null]);
        $this->assertDatabaseHas('items', ['item_name' => 'Existing standalone item']);
        $this->assertDatabaseCount('items', 2);
    });

    it('previews a single-sheet XLSX workbook', function () {
        $this->signInBiddingUser();
        $book = new Spreadsheet;
        $book->getActiveSheet()->fromArray([
            ItemImportService::HEADERS,
            array_values(catalogItemPayload(['code_prefix' => 'XLSX', 'item_name' => 'Workbook item'])),
        ]);
        ob_start();
        (new Xlsx($book))->save('php://output');
        $bytes = ob_get_clean();
        $book->disconnectWorksheets();

        $response = $this->post(route('catalog-items.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('items.xlsx', $bytes),
        ])->assertOk()->assertViewHas('preview');

        expect($response->viewData('preview')['summary']['ready'])->toBe(1);
        expect($response->viewData('preview')['rows'][0]['data'])->toMatchArray([
            'code_prefix' => 'XLSX',
            'item_name' => 'Workbook item',
            'price' => '12.50',
        ]);
    });

    it('rejects import headers outside the standalone item template', function () {
        $this->signInBiddingUser();
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['project_id', 'project_no', 'item_name']);
        fputcsv($stream, ['1', 'PRJ-1', 'Item']);
        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        $this->from(route('catalog-items.import'))
            ->post(route('catalog-items.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent('legacy.csv', $content),
            ])
            ->assertSessionHasErrors('file');
        $this->assertDatabaseCount('items', 0);
    });
});
