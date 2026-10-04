<?php

namespace App\Services;

use App\Models\BiddingDocument;
use App\Models\BiddingDocumentFileDeletion;
use App\Models\BiddingDocumentFolder;
use App\Models\BiddingDocumentVersion;
use App\Models\ProjectInformation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use ZipArchive;

class BiddingDocumentService
{
    /** @param array<string, mixed> $data */
    public function createFolder(ProjectInformation $bidding, array $data, User $user): BiddingDocumentFolder
    {
        return DB::transaction(function () use ($bidding, $data, $user): BiddingDocumentFolder {
            $this->lockBidding($bidding);
            if ($bidding->folders()->count() >= config('bidding.documents.max_folders')) {
                throw ValidationException::withMessages(['name' => 'This bidding record has reached its folder limit.']);
            }
            $parent = $this->resolveFolder($bidding, $data['parent_id'] ?? null);
            $this->checkFolderName($bidding, $data['name'], $parent?->id);

            return $bidding->folders()->create([
                'parent_id' => $parent?->id,
                'name' => $data['name'],
                'created_by' => $user->getAuthIdentifier(),
            ]);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateFolder(ProjectInformation $bidding, BiddingDocumentFolder $folder, array $data): BiddingDocumentFolder
    {
        return DB::transaction(function () use ($bidding, $folder, $data): BiddingDocumentFolder {
            $this->lockBidding($bidding);
            $folder = $bidding->folders()->whereKey($folder->id)->lockForUpdate()->firstOrFail();
            $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : $folder->parent_id;
            $parent = $this->resolveFolder($bidding, $parentId);
            $ancestor = $parent;
            $seen = [];
            while ($ancestor) {
                if ($ancestor->id === $folder->id || isset($seen[$ancestor->id])) {
                    throw ValidationException::withMessages(['parent_id' => 'A folder cannot be placed inside itself or its descendants.']);
                }
                $seen[$ancestor->id] = true;
                $ancestor = $this->resolveFolder($bidding, $ancestor->parent_id);
            }
            $name = $data['name'] ?? $folder->name;
            $this->checkFolderName($bidding, $name, $parent?->id, $folder->id);
            $folder->update(['name' => $name, 'parent_id' => $parent?->id]);

            return $folder;
        });
    }

    public function deleteFolder(ProjectInformation $bidding, BiddingDocumentFolder $folder): void
    {
        DB::transaction(function () use ($bidding, $folder): void {
            $this->lockBidding($bidding);
            $folder = $bidding->folders()->whereKey($folder->id)->lockForUpdate()->firstOrFail();
            if ($folder->documents()->exists() || $folder->children()->exists()) {
                throw ValidationException::withMessages(['folder' => 'Only empty folders without subfolders can be deleted.']);
            }
            $folder->delete();
        });
    }

    public function resolveFolder(ProjectInformation $bidding, int|string|null $folderId): ?BiddingDocumentFolder
    {
        return $folderId === null ? null : $bidding->folders()->whereKey($folderId)->firstOrFail();
    }

    /** @param array<string, mixed> $data
     *  @return Collection<int, BiddingDocument>
     */
    public function upload(ProjectInformation $bidding, array $data, User $user): Collection
    {
        $newFiles = [];
        try {
            return DB::transaction(function () use ($bidding, $data, $user, &$newFiles): Collection {
                $this->lockBidding($bidding);
                $folder = $this->resolveFolder($bidding, $data['folder_id'] ?? null);
                $documents = new Collection;
                foreach ($data['files'] as $file) {
                    $metadata = $this->storeFile($bidding, $folder, $file, $user, $newFiles);
                    $document = $bidding->documents()->create(array_merge($metadata, [
                        'folder_id' => $folder?->id,
                        'display_name' => $metadata['original_name'],
                        'description' => $data['description'] ?? null,
                        'version' => 1,
                    ]));
                    $this->recordVersion($document);
                    $documents->push($document);
                }

                return $documents->load('uploader:user_id,name');
            });
        } catch (Throwable $exception) {
            $this->compensateNewFiles($newFiles);
            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function updateDocument(ProjectInformation $bidding, BiddingDocument $document, array $data): BiddingDocument
    {
        return DB::transaction(function () use ($bidding, $document, $data): BiddingDocument {
            $this->lockBidding($bidding);
            $document = $bidding->documents()->whereKey($document->id)->lockForUpdate()->firstOrFail();
            if (array_key_exists('folder_id', $data)) {
                $data['folder_id'] = $this->resolveFolder($bidding, $data['folder_id'])?->id;
            }
            $document->update(Arr::only($data, ['display_name', 'description', 'folder_id']));

            return $document->load('uploader:user_id,name');
        });
    }

    public function replace(ProjectInformation $bidding, BiddingDocument $document, UploadedFile $file, User $user): BiddingDocument
    {
        $newFiles = [];
        try {
            return DB::transaction(function () use ($bidding, $document, $file, $user, &$newFiles): BiddingDocument {
                $this->lockBidding($bidding);
                $document = $bidding->documents()->whereKey($document->id)->lockForUpdate()->firstOrFail();
                $folder = $this->resolveFolder($bidding, $document->folder_id);
                $metadata = $this->storeFile($bidding, $folder, $file, $user, $newFiles);
                $document->update(array_merge($metadata, ['version' => $document->version + 1]));
                $this->recordVersion($document);

                return $document->load('uploader:user_id,name');
            });
        } catch (Throwable $exception) {
            $this->compensateNewFiles($newFiles);
            throw $exception;
        }
    }

    public function deleteDocument(ProjectInformation $bidding, BiddingDocument $document): void
    {
        DB::transaction(function () use ($bidding, $document): void {
            $this->lockBidding($bidding);
            $document = $bidding->documents()->whereKey($document->id)->lockForUpdate()->firstOrFail();
            $this->enqueueFile($bidding->id, $document->storage_disk, $document->storage_path);
            foreach ($document->versions()->lazyById(100) as $version) {
                $this->enqueueFile($bidding->id, $version->storage_disk, $version->storage_path);
            }
            $document->delete();
            $this->scheduleCleanup($bidding->id);
        });
    }

    /** Queue only this bidding record's files inside the parent deletion transaction. */
    public function deleteBiddingFiles(ProjectInformation $bidding): void
    {
        foreach ($bidding->documents()->lazyById(100) as $document) {
            $this->enqueueFile($bidding->id, $document->storage_disk, $document->storage_path);
        }
        foreach (BiddingDocumentVersion::query()->whereHas('document', function (Builder $query) use ($bidding): void {
            $query->where('project_information_id', $bidding->id);
        })->lazyById(100) as $version) {
            $this->enqueueFile($bidding->id, $version->storage_disk, $version->storage_path);
        }
        $this->scheduleCleanup($bidding->id);
    }

    /** @return array{removed: int, failed: int} */
    public function purgePendingFileDeletions(int $limit = 100, ?int $biddingId = null): array
    {
        $removed = 0;
        $failed = 0;
        $query = BiddingDocumentFileDeletion::query()->orderBy('attempts')->orderBy('updated_at')->orderBy('id')->limit(max(1, min($limit, 1000)));
        if ($biddingId !== null) {
            $query->where('project_information_id', $biddingId);
        }
        foreach ($query->get() as $deletion) {
            try {
                if (! $this->isManagedPath($deletion->storage_path, $deletion->project_information_id)) {
                    throw new RuntimeException('Refusing cleanup of an unmanaged bidding path.');
                }
                $disk = $this->privateDisk($deletion->storage_disk);
                if ($disk->exists($deletion->storage_path) && ! $disk->delete($deletion->storage_path)) {
                    throw new RuntimeException('The bidding file could not be deleted.');
                }
                $deletion->delete();
                $removed++;
            } catch (Throwable $exception) {
                $deletion->update(['attempts' => $deletion->attempts + 1, 'last_error' => mb_substr($exception->getMessage(), 0, 2000)]);
                report($exception);
                $failed++;
            }
        }

        return ['removed' => $removed, 'failed' => $failed];
    }

    public function readableDisk(ProjectInformation $bidding, BiddingDocument|BiddingDocumentVersion $file): FilesystemAdapter
    {
        abort_unless($this->isManagedPath($file->storage_path, $bidding->id), 404);
        $disk = $this->privateDisk($file->storage_disk);
        abort_unless($disk->exists($file->storage_path), 404, 'This file is no longer available.');

        return $disk;
    }

    public function safeFilename(string $name, string $fallback = 'document'): string
    {
        $name = str_replace(['/', chr(92)], '', $name);
        $name = preg_replace('/[\x00-\x1f\x7f]/u', '', $name) ?? '';
        $name = trim($name, " .\t\n\r\0\x0B");

        return $name === '' ? $fallback : mb_substr($name, 0, 255);
    }

    public function downloadName(BiddingDocument $document): string
    {
        $name = $this->safeFilename($document->display_name);
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== $document->extension) {
            $name .= '.'.$document->extension;
        }

        return $name;
    }

    public function canPreview(BiddingDocument $document): bool
    {
        return in_array($document->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true);
    }

    /** @param array<int, int|string> $documentIds */
    public function createZip(ProjectInformation $bidding, array $documentIds): string
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'ZIP downloads are not available on this server.');
        $documents = $bidding->documents()->whereIn('id', $documentIds)->orderBy('id')->get();
        abort_unless($documents->count() === count($documentIds), 404);
        $maxBytes = (int) config('bidding.documents.max_zip_bytes');
        if ($documents->sum('file_size') > $maxBytes) {
            throw ValidationException::withMessages(['document_ids' => 'The selected files exceed the ZIP download size limit.']);
        }
        $archivePath = tempnam(sys_get_temp_dir(), 'bidding-zip-');
        if ($archivePath === false) {
            throw new RuntimeException('Could not create a temporary archive.');
        }
        $archive = new ZipArchive;
        $temporaryFiles = [];
        $succeeded = false;
        $archiveOpened = false;
        try {
            if ($archive->open($archivePath, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not open the document archive.');
            }
            $archiveOpened = true;
            $copiedBytes = 0;
            foreach ($documents as $document) {
                $disk = $this->readableDisk($bidding, $document);
                $input = $disk->readStream($document->storage_path);
                abort_unless(is_resource($input), 404);
                $temporary = tempnam(sys_get_temp_dir(), 'bidding-part-');
                if ($temporary === false) {
                    fclose($input);
                    throw new RuntimeException('Could not stage an archive document.');
                }
                $temporaryFiles[] = $temporary;
                $output = fopen($temporary, 'wb');
                try {
                    if ($output === false) {
                        throw new RuntimeException('Could not stage an archive document.');
                    }
                    $bytes = stream_copy_to_stream($input, $output, $maxBytes - $copiedBytes + 1);
                    if ($bytes === false) {
                        throw new RuntimeException('Could not read an archive document.');
                    }
                    $copiedBytes += $bytes;
                    if ($copiedBytes > $maxBytes) {
                        throw ValidationException::withMessages(['document_ids' => 'The selected files exceed the ZIP download size limit.']);
                    }
                } finally {
                    fclose($input);
                    if (is_resource($output)) {
                        fclose($output);
                    }
                }
                if (! $archive->addFile($temporary, $document->id.'-'.$this->downloadName($document))) {
                    throw new RuntimeException('Could not add a document to the archive.');
                }
            }
            $archiveOpened = false;
            if (! $archive->close()) {
                throw new RuntimeException('Could not finish the document archive.');
            }
            $succeeded = true;

            return $archivePath;
        } finally {
            if ($archiveOpened) {
                try {
                    $archive->close();
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
            foreach ($temporaryFiles as $temporary) {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
            if (! $succeeded && is_file($archivePath)) {
                unlink($archivePath);
            }
        }
    }

    /** @param array<int, array{project_information_id: int, storage_disk: string, storage_path: string}> $newFiles
     *  @return array<string, mixed>
     */
    private function storeFile(ProjectInformation $bidding, ?BiddingDocumentFolder $folder, UploadedFile $file, User $user, array &$newFiles): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = Str::uuid().'.'.$extension;
        $directory = 'bidding-documents/'.$bidding->id.'/'.($folder?->storage_uuid ?? 'root');
        $diskName = (string) config('bidding.documents.disk');
        $path = $directory.'/'.$storedName;
        if (! $this->isManagedPath($path, (int) $bidding->id)) {
            throw new RuntimeException('Refusing an unmanaged bidding upload path.');
        }
        $newFiles[] = ['project_information_id' => (int) $bidding->id, 'storage_disk' => $diskName, 'storage_path' => $path];
        $disk = $this->privateDisk($diskName);
        if (! $disk->putFileAs($directory, $file, $storedName, ['visibility' => 'private'])) {
            throw new RuntimeException('The document could not be stored. Please try again.');
        }
        $mime = $file->getMimeType();
        if ($mime === 'application/zip' && in_array($extension, ['docx', 'xlsx'], true)) {
            $mime = config('bidding.documents.mime_types.'.$extension)[0];
        }

        return [
            'original_name' => $this->safeFilename($file->getClientOriginalName(), 'document.'.$extension),
            'stored_name' => $storedName,
            'storage_disk' => $diskName,
            'storage_path' => $path,
            'mime_type' => $mime,
            'extension' => $extension,
            'file_size' => $file->getSize(),
            'uploaded_by' => $user->getAuthIdentifier(),
            'uploaded_at' => now(),
        ];
    }

    private function recordVersion(BiddingDocument $document): void
    {
        $document->versions()->create(array_merge(Arr::only($document->getAttributes(), [
            'version', 'original_name', 'stored_name', 'storage_disk', 'storage_path', 'mime_type', 'extension', 'file_size', 'uploaded_by',
        ]), ['created_at' => now()]));
    }

    private function lockBidding(ProjectInformation $bidding): void
    {
        ProjectInformation::query()->whereKey($bidding->id)->lockForUpdate()->firstOrFail();
    }

    private function checkFolderName(ProjectInformation $bidding, string $name, ?int $parentId, ?int $exceptId = null): void
    {
        $query = $bidding->folders()->where('parent_id', $parentId)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['name' => 'A folder with this name already exists here.']);
        }
    }

    private function enqueueFile(int $biddingId, string $disk, string $path): void
    {
        BiddingDocumentFileDeletion::query()->firstOrCreate(
            ['storage_disk' => $disk, 'storage_path' => $path],
            ['project_information_id' => $biddingId],
        );
    }

    private function scheduleCleanup(int $biddingId): void
    {
        DB::afterCommit(function () use ($biddingId): void {
            try {
                $this->purgePendingFileDeletions(100, $biddingId);
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }

    /** @param array<int, array{project_information_id: int, storage_disk: string, storage_path: string}> $files */
    private function compensateNewFiles(array $files): void
    {
        foreach ($files as $file) {
            try {
                if (! $this->isManagedPath($file['storage_path'], $file['project_information_id'])) {
                    throw new RuntimeException('Refusing cleanup of an unmanaged upload path.');
                }
                $disk = $this->privateDisk($file['storage_disk']);
                if ($disk->exists($file['storage_path']) && ! $disk->delete($file['storage_path'])) {
                    throw new RuntimeException('Could not remove a failed upload.');
                }
            } catch (Throwable $exception) {
                report($exception);
                try {
                    $this->enqueueFile($file['project_information_id'], $file['storage_disk'], $file['storage_path']);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }
        }
    }

    private function privateDisk(string $name): FilesystemAdapter
    {
        if (! preg_match('/\A[a-zA-Z0-9_-]+\z/', $name) || ! config('filesystems.disks.'.$name) || config('filesystems.disks.'.$name.'.visibility') === 'public') {
            throw new RuntimeException('The bidding storage disk must be configured as private.');
        }

        return Storage::disk($name);
    }

    private function isManagedPath(string $path, int $biddingId): bool
    {
        $uuid = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}';

        return preg_match('~\Abidding-documents/'.$biddingId.'/(?:root|'.$uuid.')/'.$uuid.'\.[a-z0-9]{1,10}\z~i', $path) === 1;
    }
}
