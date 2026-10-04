<?php

use App\Models\BiddingDocumentFileDeletion;
use App\Models\BiddingDocumentVersion;
use App\Services\BiddingDocumentService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\BiddingProjectFixtureFactory;
use Tests\BiddingTestCase;

pest()->extend(BiddingTestCase::class)->in(__FILE__);

function biddingDocumentPdf(string $name = 'specification.pdf', string $body = 'Original specification'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".$body."\n%%EOF");
}

it('uploads private files with complete initial history in both areas', function (string $prefix, string $role) {
    $disk = Storage::fake('bidding');
    $user = $this->signInBiddingUser($role);
    $project = BiddingProjectFixtureFactory::new()->create();

    $response = $this->postJson(route($prefix.'.documents.store', $project), [
        'files' => [biddingDocumentPdf()], 'description' => 'Contract requirements',
    ]);

    $response->assertCreated()->assertJsonPath('documents.0.original_name', 'specification.pdf')
        ->assertJsonPath('documents.0.uploaded_by.id', $user->user_id)->assertJsonPath('documents.0.version', 1);
    $document = $project->documents()->sole();
    $version = $document->versions()->sole();
    expect($document->storage_path)->toMatch('~^bidding-documents/'.$project->id.'/root/[0-9a-f-]{36}\\.pdf$~');
    expect($version->storage_path)->toBe($document->storage_path);
    expect($version->version)->toBe(1);
    expect($document->uploaded_at)->not->toBeNull();
    expect($response->json('documents.0.download_url'))->toBe(route($prefix.'.documents.download', [$project, $document]));
    expect($response->json('documents.0'))->not->toHaveKeys(['storage_path', 'stored_name', 'storage_disk']);
    $disk->assertExists($document->storage_path);
    expect(config('filesystems.disks.bidding.visibility'))->toBe('private');
    expect(config('filesystems.disks.bidding.root'))->toBe(storage_path('app/private/bidding'));
})->with(['operation' => ['project.bidding', 'user'], 'finance' => ['bidding', 'finance']]);

it('stores duplicate filenames under distinct UUID paths', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), [
        'files' => [biddingDocumentPdf('same.pdf', 'First'), biddingDocumentPdf('same.pdf', 'Second')],
    ])->assertCreated()->assertJsonCount(2, 'documents');
    $documents = $project->documents()->get();
    expect($documents->pluck('original_name')->all())->toBe(['same.pdf', 'same.pdf']);
    expect($documents->pluck('storage_path')->unique())->toHaveCount(2);
    expect(BiddingDocumentVersion::query()->count())->toBe(2);
    foreach ($documents as $document) {
        $disk->assertExists($document->storage_path);
    }
});

it('creates nested custom folders uses UUID storage and only deletes empty folders', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.folders.store', $project), ['name' => 'Requirements / Phase 1'])->assertCreated();
    $folder = $project->folders()->sole();
    $this->postJson(route('project.bidding.folders.store', $project), ['name' => 'Supporting files', 'parent_id' => $folder->id])->assertCreated();
    $child = $project->folders()->where('parent_id', $folder->id)->sole();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()], 'folder_id' => $child->id])->assertCreated();
    $document = $project->documents()->sole();
    expect($document->storage_path)->toStartWith('bidding-documents/'.$project->id.'/'.$child->storage_uuid.'/');
    $this->putJson(route('project.bidding.folders.update', [$project, $folder]), ['name' => 'Renamed requirements'])->assertOk();
    expect($document->fresh()->storage_path)->toBe($document->storage_path);
    $this->deleteJson(route('project.bidding.folders.destroy', [$project, $folder]))->assertUnprocessable();
    $this->deleteJson(route('project.bidding.folders.destroy', [$project, $child]))->assertUnprocessable();
    $this->deleteJson(route('project.bidding.documents.destroy', [$project, $document]))->assertOk();
    $this->deleteJson(route('project.bidding.folders.destroy', [$project, $child]))->assertOk();
    $this->deleteJson(route('project.bidding.folders.destroy', [$project, $folder]))->assertOk();
    expect($disk->allFiles())->toBe([]);
    $this->assertDatabaseCount('bidding_document_folders', 0);
});

it('rejects duplicate sibling names and cyclic parents', function () {
    Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.folders.store', $project), ['name' => 'Contracts'])->assertCreated();
    $parent = $project->folders()->sole();
    $this->postJson(route('project.bidding.folders.store', $project), ['name' => 'contracts'])->assertUnprocessable()->assertJsonValidationErrors('name');
    $this->postJson(route('project.bidding.folders.store', $project), ['name' => 'Child', 'parent_id' => $parent->id])->assertCreated();
    $child = $project->folders()->where('parent_id', $parent->id)->sole();
    $this->putJson(route('project.bidding.folders.update', [$project, $parent]), ['parent_id' => $child->id])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
    expect($parent->fresh()->parent_id)->toBeNull();
    expect($child->fresh()->parent_id)->toBe($parent->id);
});

it('moves and renames documents while retaining original names paths history and upload dates', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.folders.store', $project), ['name' => 'Contracts'])->assertCreated();
    $folder = $project->folders()->sole();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()], 'description' => 'Retained'])->assertCreated();
    $document = $project->documents()->sole();
    $path = $document->storage_path;
    $uploadedAt = $document->uploaded_at->toIso8601String();
    $this->travel(1)->hours();
    $this->putJson(route('project.bidding.documents.update', [$project, $document]), ['display_name' => 'Signed agreement', 'folder_id' => $folder->id])
        ->assertOk()->assertJsonPath('document.uploaded_at', $uploadedAt);
    $document->refresh();
    expect($document->folder_id)->toBe($folder->id);
    expect($document->original_name)->toBe('specification.pdf');
    expect($document->description)->toBe('Retained');
    expect($document->version)->toBe(1);
    expect($document->storage_path)->toBe($path);
    $this->assertDatabaseCount('bidding_document_versions', 1);
    $disk->assertExists($path);
    $this->putJson(route('project.bidding.documents.update', [$project, $document]), ['folder_id' => null, 'description' => null])
        ->assertOk()->assertJsonPath('document.folder_id', null)->assertJsonPath('document.description', null);
    $this->get(route('project.bidding.documents.download', [$project, $document]))->assertDownload('Signed agreement.pdf');
});

it('rejects unsafe document display names', function (string $name) {
    Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()]])->assertCreated();
    $document = $project->documents()->sole();
    $this->putJson(route('project.bidding.documents.update', [$project, $document]), ['display_name' => $name])->assertUnprocessable()->assertJsonValidationErrors('display_name');
    expect($document->fresh()->display_name)->toBe('specification.pdf');
})->with(['slash' => ['../outside.pdf'], 'backslash' => ['bad\\file.pdf'], 'header newline' => ["name\r\nInjected: value"]]);

it('rejects foreign folders for parents filtering uploads moves and folder mutations', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $other = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.folders.store', $other), ['name' => 'Other contracts'])->assertCreated();
    $foreign = $other->folders()->sole();
    $this->postJson(route('project.bidding.folders.store', $project), ['name' => 'Wrong parent', 'parent_id' => $foreign->id])->assertNotFound();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()], 'folder_id' => $foreign->id])->assertNotFound();
    $this->getJson(route('project.bidding.documents.index', $project).'?folder_id='.$foreign->id)->assertNotFound();
    $this->putJson(route('project.bidding.folders.update', [$project, $foreign]), ['name' => 'Hijacked'])->assertNotFound();
    $this->deleteJson(route('project.bidding.folders.destroy', [$project, $foreign]))->assertNotFound();
    expect($disk->allFiles())->toBe([]);
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()]])->assertCreated();
    $document = $project->documents()->sole();
    $this->putJson(route('project.bidding.documents.update', [$project, $document]), ['folder_id' => $foreign->id])->assertNotFound();
    expect($document->fresh()->folder_id)->toBeNull();
    expect($foreign->fresh()->name)->toBe('Other contracts');
});

it('rejects cross-bidding document and version URLs', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $other = BiddingProjectFixtureFactory::new()->create();
    foreach ([$project, $other] as $bidding) {
        $this->postJson(route('project.bidding.documents.store', $bidding), ['files' => [biddingDocumentPdf()]])->assertCreated();
    }
    $document = $other->documents()->sole();
    $version = $document->versions()->sole();
    foreach (['download', 'preview', 'history'] as $action) {
        $this->getJson(route('project.bidding.documents.'.$action, [$project, $document]))->assertNotFound();
    }
    $this->putJson(route('project.bidding.documents.update', [$project, $document]), ['display_name' => 'Hijacked'])->assertNotFound();
    $this->postJson(route('project.bidding.documents.replace', [$project, $document]), ['file' => biddingDocumentPdf()])->assertNotFound();
    $this->deleteJson(route('project.bidding.documents.destroy', [$project, $document]))->assertNotFound();
    $this->getJson(route('project.bidding.documents.versions.download', [$project, $project->documents()->sole(), $version]))->assertNotFound();
    expect($disk->allFiles())->toHaveCount(2);
    $this->assertModelExists($document);
});

it('preserves existing role middleware for document management', function (string $prefix, string $role) {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser($role);
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->getJson(route($prefix.'.documents.index', $project))->assertForbidden();
    $this->postJson(route($prefix.'.documents.store', $project), ['files' => [biddingDocumentPdf()]])->assertForbidden();
    $this->postJson(route($prefix.'.folders.store', $project), ['name' => 'Denied'])->assertForbidden();
    $this->assertDatabaseCount('bidding_documents', 0);
    expect($disk->allFiles())->toBe([]);
})->with(['operation finance role' => ['project.bidding', 'finance'], 'finance user role' => ['bidding', 'user']]);

it('requires authentication for document metadata and upload', function () {
    Storage::fake('bidding');
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->getJson(route('project.bidding.documents.index', $project))->assertUnauthorized();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()]])->assertUnauthorized();
});

it('rejects invalid extensions and mismatched content before writing any batch member', function (string $name, string $content) {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $source = UploadedFile::fake()->createWithContent($name, $content);
    $upload = new UploadedFile($source->getPathname(), $name, null, null, true);
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf(), $upload]])
        ->assertUnprocessable()->assertJsonValidationErrors('files.1');
    $this->assertDatabaseCount('bidding_documents', 0);
    $this->assertDatabaseCount('bidding_document_versions', 0);
    expect($disk->allFiles())->toBe([]);
})->with(['executable' => ['payload.php', '<?php echo "not allowed";'], 'disguised HTML' => ['payload.pdf', '<html><script>alert(1)</script></html>'], 'disguised CSV' => ['payload.png', "Name,Amount\nA,1"]]);

it('enforces file size and early collection caps without partial writes', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [UploadedFile::fake()->create('large.pdf', 25601, 'application/pdf')]])
        ->assertUnprocessable()->assertJsonValidationErrors('files.0');
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => array_fill(0, 21, 'invalid input')])->assertUnprocessable()->assertJsonValidationErrors('files');
    $this->postJson(route('project.bidding.documents.zip', $project), ['document_ids' => range(1, 101)])->assertUnprocessable()->assertJsonValidationErrors('document_ids');
    $this->assertDatabaseCount('bidding_documents', 0);
    expect($disk->allFiles())->toBe([]);
});

it('retains all physical versions and downloadable history after replacement', function () {
    $disk = Storage::fake('bidding');
    $originalUser = $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf('original.pdf', 'Original body')]])->assertCreated();
    $document = $project->documents()->sole();
    $versionOne = $document->versions()->sole();
    $oldPath = $document->storage_path;
    $uploadedAt = $document->uploaded_at;
    $replacementUser = $this->signInBiddingUser();
    $this->travel(1)->hours();
    $this->postJson(route('project.bidding.documents.replace', [$project, $document]), ['file' => biddingDocumentPdf('replacement.pdf', 'Replacement body')])
        ->assertOk()->assertJsonPath('document.version', 2)->assertJsonPath('document.display_name', 'original.pdf')
        ->assertJsonPath('document.original_name', 'replacement.pdf')->assertJsonPath('document.uploaded_by.id', $replacementUser->user_id);
    $document->refresh();
    expect($document->storage_path)->not->toBe($oldPath);
    expect($document->uploaded_at->greaterThan($uploadedAt))->toBeTrue();
    expect($versionOne->uploaded_by)->toBe($originalUser->user_id);
    $disk->assertExists([$oldPath, $document->storage_path]);
    $this->getJson(route('project.bidding.documents.history', [$project, $document]).'?per_page=1')
        ->assertOk()->assertJsonCount(1, 'versions')->assertJsonPath('versions.0.version', 2)->assertJsonPath('meta.total', 2)->assertJsonPath('meta.last_page', 2);
    $oldDownload = $this->get(route('project.bidding.documents.versions.download', [$project, $document, $versionOne]));
    $oldDownload->assertOk()->assertDownload('original.pdf');
    expect($oldDownload->streamedContent())->toContain('Original body');
    $latest = $this->get(route('project.bidding.documents.download', [$project, $document]));
    $latest->assertOk();
    expect($latest->streamedContent())->toContain('Replacement body');
});

it('previews supported types with inline sandboxed responses and rejects CSV preview', function () {
    Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), [
        'files' => [biddingDocumentPdf(), UploadedFile::fake()->createWithContent('data.csv', "Name,Value\nA,1\n")],
    ])->assertCreated();
    $pdf = $project->documents()->where('extension', 'pdf')->sole();
    $csv = $project->documents()->where('extension', 'csv')->sole();
    $preview = $this->get(route('project.bidding.documents.preview', [$project, $pdf]));
    $preview->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Security-Policy', "sandbox; default-src 'none';");
    expect($preview->headers->get('Content-Disposition'))->toStartWith('inline;');
    $this->getJson(route('project.bidding.documents.preview', [$project, $csv]))->assertStatus(415);
    $this->getJson(route('project.bidding.documents.index', $project))->assertOk()->assertJsonPath('documents.0.preview_url', null);
});

it('returns paginated searchable scoped metadata and client limits', function () {
    Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $other = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf('alpha.pdf'), biddingDocumentPdf('beta.pdf')]])->assertCreated();
    $this->postJson(route('project.bidding.documents.store', $other), ['files' => [biddingDocumentPdf('foreign.pdf')]])->assertCreated();
    $this->getJson(route('project.bidding.documents.index', $project).'?per_page=1')
        ->assertOk()->assertJsonCount(1, 'documents')->assertJsonPath('meta.total', 2)->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('permissions.upload', true)->assertJsonPath('permissions.update', true)->assertJsonPath('permissions.delete', true)
        ->assertJsonPath('limits.max_files', 20)->assertJsonPath('limits.max_zip_files', 100)->assertJsonPath('limits.max_zip_bytes', 268435456);
    $this->getJson(route('project.bidding.documents.index', $project).'?search=alpha&folder_id=root')
        ->assertOk()->assertJsonCount(1, 'documents')->assertJsonPath('documents.0.display_name', 'alpha.pdf')->assertJsonPath('meta.total', 1);
    $this->getJson(route('project.bidding.documents.index', $project).'?per_page=101')->assertUnprocessable()->assertJsonValidationErrors('per_page');
});

it('handles missing physical files as unavailable and allows their deletion', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()]])->assertCreated();
    $document = $project->documents()->sole();
    $disk->delete($document->storage_path);
    $this->getJson(route('project.bidding.documents.download', [$project, $document]))->assertNotFound();
    $this->deleteJson(route('project.bidding.documents.destroy', [$project, $document]))->assertOk();
    $this->assertDatabaseCount('bidding_documents', 0);
    $this->assertDatabaseCount('bidding_document_versions', 0);
    $this->assertDatabaseCount('bidding_document_file_deletions', 0);
});

it('deletes one document and all versions while preserving other bidding files', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $other = BiddingProjectFixtureFactory::new()->create();
    foreach ([$project, $other] as $bidding) {
        $this->postJson(route('project.bidding.documents.store', $bidding), ['files' => [biddingDocumentPdf()]])->assertCreated();
    }
    $document = $project->documents()->sole();
    $otherDocument = $other->documents()->sole();
    $oldPath = $document->storage_path;
    $this->postJson(route('project.bidding.documents.replace', [$project, $document]), ['file' => biddingDocumentPdf('new.pdf')])->assertOk();
    $latestPath = $document->fresh()->storage_path;
    $this->deleteJson(route('project.bidding.documents.destroy', [$project, $document]))->assertOk();
    $disk->assertMissing([$oldPath, $latestPath]);
    $disk->assertExists($otherDocument->storage_path);
    $this->assertModelMissing($document);
    $this->assertModelExists($otherDocument);
    $this->assertDatabaseCount('bidding_document_versions', 1);
    $this->assertDatabaseCount('bidding_document_file_deletions', 0);
});

it('cleans only the selected bidding document tree through the actual bidding delete route', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $other = BiddingProjectFixtureFactory::new()->create();
    foreach ([$project, $other] as $bidding) {
        $this->postJson(route('project.bidding.folders.store', $bidding), ['name' => 'Contracts'])->assertCreated();
        $this->postJson(route('project.bidding.documents.store', $bidding), ['files' => [biddingDocumentPdf()], 'folder_id' => $bidding->folders()->sole()->id])->assertCreated();
    }
    $document = $project->documents()->sole();
    $oldPath = $document->storage_path;
    $this->postJson(route('project.bidding.documents.replace', [$project, $document]), ['file' => biddingDocumentPdf('new.pdf')])->assertOk();
    $latestPath = $document->fresh()->storage_path;
    $otherDocument = $other->documents()->sole();
    $this->delete(route('project.bidding.destroy', $project))->assertRedirect(route('project.bidding.index'));
    $this->assertModelMissing($project);
    $this->assertModelMissing($document);
    $this->assertModelExists($other);
    $this->assertModelExists($otherDocument);
    $disk->assertMissing([$oldPath, $latestPath]);
    $disk->assertExists($otherDocument->storage_path);
    $this->assertDatabaseCount('bidding_document_folders', 1);
    $this->assertDatabaseCount('bidding_document_versions', 1);
    $this->assertDatabaseCount('bidding_document_file_deletions', 0);
});

it('persists cleanup failures and retries successfully after storage recovers', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()]])->assertCreated();
    $document = $project->documents()->sole();
    $unavailable = Mockery::mock(FilesystemAdapter::class, [$disk->getDriver(), $disk->getAdapter(), $disk->getConfig()])->makePartial();
    $unavailable->shouldReceive('delete')->andReturn(false);
    Storage::set('bidding', $unavailable);
    $this->deleteJson(route('project.bidding.documents.destroy', [$project, $document]))->assertOk();
    $this->assertModelMissing($document);
    $pending = BiddingDocumentFileDeletion::query()->sole();
    expect($pending->storage_path)->toBe($document->storage_path);
    expect($pending->attempts)->toBe(1);
    $disk->assertExists($document->storage_path);
    Storage::set('bidding', $disk);
    expect(app(BiddingDocumentService::class)->purgePendingFileDeletions())->toBe(['removed' => 1, 'failed' => 0]);
    $disk->assertMissing($document->storage_path);
    $this->assertDatabaseCount('bidding_document_file_deletions', 0);
});

it('does not starve healthy cleanup entries behind permanent failures', function () {
    $disk = Storage::fake('bidding');
    $project = BiddingProjectFixtureFactory::new()->create();
    $disk->put('outside-owned-prefix.pdf', 'Keep this file');
    $blocked = BiddingDocumentFileDeletion::query()->create(['project_information_id' => $project->id, 'storage_disk' => 'bidding', 'storage_path' => 'outside-owned-prefix.pdf']);
    $healthyPath = 'bidding-documents/'.$project->id.'/root/'.Str::uuid().'.pdf';
    $disk->put($healthyPath, 'Delete this file');
    BiddingDocumentFileDeletion::query()->create(['project_information_id' => $project->id, 'storage_disk' => 'bidding', 'storage_path' => $healthyPath]);
    $service = app(BiddingDocumentService::class);
    expect($service->purgePendingFileDeletions(1))->toBe(['removed' => 0, 'failed' => 1]);
    expect($service->purgePendingFileDeletions(1))->toBe(['removed' => 1, 'failed' => 0]);
    expect($blocked->fresh()->attempts)->toBe(1);
    $disk->assertExists('outside-owned-prefix.pdf');
    $disk->assertMissing($healthyPath);
    $this->assertDatabaseCount('bidding_document_file_deletions', 1);
});

it('refuses corrupted paths for downloads and cleanup', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()]])->assertCreated();
    $document = $project->documents()->sole();
    $disk->put('other-feature/private.pdf', 'Never touch');
    $document->update(['storage_path' => 'other-feature/private.pdf']);
    $this->getJson(route('project.bidding.documents.download', [$project, $document]))->assertNotFound();
    $this->deleteJson(route('project.bidding.documents.destroy', [$project, $document]))->assertOk();
    $disk->assertExists('other-feature/private.pdf');
    $this->assertDatabaseHas('bidding_document_file_deletions', ['storage_path' => 'other-feature/private.pdf', 'attempts' => 1]);
});

it('compensates physical uploads when the database insert fails', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $inserts = 0;
    DB::listen(function (QueryExecuted $query) use (&$inserts): void {
        if (str_starts_with($query->sql, 'insert into "bidding_documents"') && ++$inserts === 2) {
            throw new RuntimeException('Isolated simulated document insert failure');
        }
    });
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf('first.pdf'), biddingDocumentPdf('second.pdf')]])->assertStatus(500);
    $this->assertDatabaseCount('bidding_documents', 0);
    $this->assertDatabaseCount('bidding_document_versions', 0);
    expect($disk->allFiles())->toBe([]);
});

it('rolls back failed replacements while retaining the original version and file', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()]])->assertCreated();
    $document = $project->documents()->sole();
    $path = $document->storage_path;
    DB::listen(function (QueryExecuted $query): void {
        if (str_starts_with($query->sql, 'insert into "bidding_document_versions"')) {
            throw new RuntimeException('Isolated simulated version insert failure');
        }
    });
    $this->postJson(route('project.bidding.documents.replace', [$project, $document]), ['file' => biddingDocumentPdf('replacement.pdf')])->assertStatus(500);
    expect($document->fresh()->version)->toBe(1);
    expect($document->fresh()->storage_path)->toBe($path);
    $this->assertDatabaseCount('bidding_document_versions', 1);
    expect($disk->allFiles())->toBe([$path]);
});

it('creates ZIP selections with safe distinct entries', function () {
    Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf('same.pdf', 'One'), biddingDocumentPdf('same.pdf', 'Two')]])->assertCreated();
    $documents = $project->documents()->orderBy('id')->get();
    $response = $this->postJson(route('project.bidding.documents.zip', $project), ['document_ids' => $documents->modelKeys()]);
    $response->assertOk()->assertDownload('bidding-'.$project->id.'-documents.zip');
    $path = $response->baseResponse->getFile()->getPathname();
    $archive = new ZipArchive;
    try {
        expect($archive->open($path))->toBeTrue();
        expect($archive->numFiles)->toBe(2);
        expect($archive->getNameIndex(0))->toBe($documents[0]->id.'-same.pdf');
        expect($archive->getFromIndex(0))->toContain('One');
        expect($archive->getNameIndex(1))->toBe($documents[1]->id.'-same.pdf');
    } finally {
        $archive->close();
        if (is_file($path)) {
            unlink($path);
        }
    }
});

it('rejects foreign duplicate and oversized ZIP selections', function () {
    Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $other = BiddingProjectFixtureFactory::new()->create();
    foreach ([$project, $other] as $bidding) {
        $this->postJson(route('project.bidding.documents.store', $bidding), ['files' => [biddingDocumentPdf()]])->assertCreated();
    }
    $document = $project->documents()->sole();
    $foreign = $other->documents()->sole();
    $this->postJson(route('project.bidding.documents.zip', $project), ['document_ids' => [$document->id, $foreign->id]])->assertNotFound();
    $this->postJson(route('project.bidding.documents.zip', $project), ['document_ids' => [$document->id, $document->id]])->assertUnprocessable()->assertJsonValidationErrors('document_ids.0');
    config()->set('bidding.documents.max_zip_bytes', 1);
    $this->postJson(route('project.bidding.documents.zip', $project), ['document_ids' => [$document->id]])->assertUnprocessable()->assertJsonValidationErrors('document_ids');
});

it('accepts Office containers only when their expected document structure is present', function (string $extension, string $entry, bool $valid) {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $path = tempnam(sys_get_temp_dir(), 'bidding-office-test-');
    expect($path)->not->toBeFalse();
    $archive = new ZipArchive;
    expect($archive->open($path, ZipArchive::OVERWRITE))->toBeTrue();
    $archive->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>');
    $archive->addFromString($entry, '<?xml version="1.0"?><document/>');
    $archive->close();
    $upload = new UploadedFile($path, 'office.'.$extension, null, null, true);

    try {
        $response = $this->postJson(route('project.bidding.documents.store', $project), ['files' => [$upload]]);
        if ($valid) {
            $response->assertCreated()->assertJsonPath('documents.0.extension', $extension);
            $disk->assertExists($project->documents()->sole()->storage_path);
        } else {
            $response->assertUnprocessable()->assertJsonValidationErrors('files.0');
            expect($disk->allFiles())->toBe([]);
            $this->assertDatabaseCount('bidding_documents', 0);
        }
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }
})->with(['Word document' => ['docx', 'word/document.xml', true], 'Excel workbook' => ['xlsx', 'xl/workbook.xml', true], 'renamed arbitrary ZIP' => ['docx', 'unrelated.xml', false]]);

it('accepts and previews real JPEG and PNG uploads', function (string $extension, string $mime) {
    Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $source = UploadedFile::fake()->image('photo.'.$extension, 16, 16);
    $upload = new UploadedFile($source->getPathname(), 'photo.'.$extension, null, null, true);
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [$upload]])->assertCreated();
    $document = $project->documents()->sole();

    $this->get(route('project.bidding.documents.preview', [$project, $document]))->assertOk()->assertHeader('Content-Type', $mime);
})->with(['JPEG' => ['jpg', 'image/jpeg'], 'PNG' => ['png', 'image/png']]);

it('keeps cleanup records after the parent bidding record is deleted during storage failure', function () {
    $disk = Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf()]])->assertCreated();
    $path = $project->documents()->sole()->storage_path;
    $unavailable = Mockery::mock(FilesystemAdapter::class, [$disk->getDriver(), $disk->getAdapter(), $disk->getConfig()])->makePartial();
    $unavailable->shouldReceive('delete')->andReturn(false);
    Storage::set('bidding', $unavailable);

    $this->delete(route('project.bidding.destroy', $project))->assertRedirect(route('project.bidding.index'));

    $this->assertModelMissing($project);
    $this->assertDatabaseCount('bidding_documents', 0);
    $this->assertDatabaseHas('bidding_document_file_deletions', ['project_information_id' => $project->id, 'storage_path' => $path, 'attempts' => 1]);
    Storage::set('bidding', $disk);
    expect(app(BiddingDocumentService::class)->purgePendingFileDeletions())->toBe(['removed' => 1, 'failed' => 0]);
    $disk->assertMissing($path);
});

it('rejects downloading another document version within the same bidding record', function () {
    Storage::fake('bidding');
    $this->signInBiddingUser();
    $project = BiddingProjectFixtureFactory::new()->create();
    $this->postJson(route('project.bidding.documents.store', $project), ['files' => [biddingDocumentPdf('one.pdf'), biddingDocumentPdf('two.pdf')]])->assertCreated();
    $documents = $project->documents()->orderBy('id')->get();
    $version = $documents[1]->versions()->sole();

    $this->getJson(route('project.bidding.documents.versions.download', [$project, $documents[0], $version]))->assertNotFound();
});
