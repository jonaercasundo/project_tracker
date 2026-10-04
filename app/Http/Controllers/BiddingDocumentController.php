<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadBiddingDocumentsRequest;
use App\Http\Requests\IndexBiddingDocumentsRequest;
use App\Http\Requests\ReplaceBiddingDocumentRequest;
use App\Http\Requests\StoreBiddingDocumentRequest;
use App\Http\Requests\UpdateBiddingDocumentRequest;
use App\Models\BiddingDocument;
use App\Models\BiddingDocumentFolder;
use App\Models\BiddingDocumentVersion;
use App\Models\ProjectInformation;
use App\Services\BiddingDocumentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BiddingDocumentController extends Controller
{
    public function __construct(private BiddingDocumentService $documents) {}

    public function index(IndexBiddingDocumentsRequest $request, ProjectInformation $bidding): JsonResponse
    {
        $filters = $request->validated();
        $query = $bidding->documents()->with('uploader:user_id,name');
        if (array_key_exists('folder_id', $filters)) {
            $folderId = $filters['folder_id'];
            if ($folderId === null || $folderId === 'root') {
                $query->whereNull('folder_id');
            } else {
                $folder = $this->documents->resolveFolder($bidding, $folderId);
                $query->where('folder_id', $folder->id);
            }
        }
        if (! empty($filters['search'])) {
            $query->where('display_name', 'like', '%'.$filters['search'].'%');
        }
        $page = $query->orderByDesc('id')->paginate($filters['per_page'] ?? config('bidding.documents.per_page'));
        $folders = $bidding->folders()->withCount('documents')->orderBy('name')->orderBy('id')->limit(config('bidding.documents.max_folders'))->get();

        return response()->json([
            'folders' => $folders->map(fn (BiddingDocumentFolder $folder): array => [
                'id' => $folder->id, 'parent_id' => $folder->parent_id, 'name' => $folder->name,
                'document_count' => $folder->documents_count,
            ]),
            'documents' => $page->getCollection()->map(fn (BiddingDocument $document): array => $this->presentDocument($request, $bidding, $document)),
            'permissions' => [
                'upload' => Gate::allows('uploadDocument', $bidding),
                'update' => Gate::allows('update', $bidding),
                'delete' => Gate::allows('deleteDocument', $bidding),
            ],
            'limits' => [
                'max_file_size_kb' => config('bidding.documents.max_file_size_kb'),
                'max_files' => config('bidding.documents.max_files'),
                'max_zip_files' => config('bidding.documents.max_zip_files'),
                'max_zip_bytes' => config('bidding.documents.max_zip_bytes'),
                'extensions' => config('bidding.documents.extensions'),
            ],
            'meta' => $this->pagination($page),
        ]);
    }

    public function store(StoreBiddingDocumentRequest $request, ProjectInformation $bidding): JsonResponse
    {
        $documents = $this->documents->upload($bidding, $request->validated(), $request->user());

        return response()->json([
            'message' => 'Documents uploaded.',
            'documents' => $documents->map(fn (BiddingDocument $document): array => $this->presentDocument($request, $bidding, $document)),
        ], 201);
    }

    public function update(UpdateBiddingDocumentRequest $request, ProjectInformation $bidding, BiddingDocument $document): JsonResponse
    {
        $document = $this->documents->updateDocument($bidding, $document, $request->validated());

        return response()->json(['message' => 'Document updated.', 'document' => $this->presentDocument($request, $bidding, $document)]);
    }

    public function replace(ReplaceBiddingDocumentRequest $request, ProjectInformation $bidding, BiddingDocument $document): JsonResponse
    {
        $document = $this->documents->replace($bidding, $document, $request->file('file'), $request->user());

        return response()->json(['message' => 'New version uploaded.', 'document' => $this->presentDocument($request, $bidding, $document)]);
    }

    public function destroy(ProjectInformation $bidding, BiddingDocument $document): JsonResponse
    {
        $this->authorizeDocument('deleteDocument', $bidding, $document);
        $this->documents->deleteDocument($bidding, $document);

        return response()->json(['message' => 'Document deleted.']);
    }

    public function download(ProjectInformation $bidding, BiddingDocument $document): StreamedResponse
    {
        $this->authorizeDocument('downloadDocument', $bidding, $document);

        return $this->documents->readableDisk($bidding, $document)->download(
            $document->storage_path, $this->documents->downloadName($document), $this->fileHeaders($document->mime_type),
        );
    }

    public function preview(ProjectInformation $bidding, BiddingDocument $document): StreamedResponse
    {
        $this->authorizeDocument('downloadDocument', $bidding, $document);
        abort_unless($this->documents->canPreview($document), 415, 'This file type cannot be previewed.');

        return $this->documents->readableDisk($bidding, $document)->response(
            $document->storage_path, $this->documents->downloadName($document),
            array_merge($this->fileHeaders($document->mime_type), ['Content-Security-Policy' => "sandbox; default-src 'none';"]),
        );
    }

    public function history(Request $request, ProjectInformation $bidding, BiddingDocument $document): JsonResponse
    {
        $this->authorizeDocument('downloadDocument', $bidding, $document);
        $filters = $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1']]);
        $versions = $document->versions()->with('uploader:user_id,name')->orderByDesc('version')
            ->paginate($filters['per_page'] ?? config('bidding.documents.per_page'));

        return response()->json([
            'versions' => $versions->getCollection()->map(fn (BiddingDocumentVersion $version): array => [
                'id' => $version->id, 'version' => $version->version, 'original_name' => $version->original_name,
                'mime_type' => $version->mime_type, 'extension' => $version->extension, 'file_size' => $version->file_size,
                'uploaded_by' => $version->uploader ? ['id' => $version->uploader->user_id, 'name' => $version->uploader->name] : null,
                'created_at' => $version->created_at->toIso8601String(),
                'download_url' => route($this->prefix($request).'documents.versions.download', [$bidding, $document, $version]),
            ]),
            'meta' => $this->pagination($versions),
        ]);
    }

    public function downloadVersion(ProjectInformation $bidding, BiddingDocument $document, BiddingDocumentVersion $version): StreamedResponse
    {
        $this->authorizeDocument('downloadDocument', $bidding, $document);
        abort_unless((int) $version->bidding_document_id === (int) $document->id, 404);

        return $this->documents->readableDisk($bidding, $version)->download(
            $version->storage_path, $this->documents->safeFilename($version->original_name), $this->fileHeaders($version->mime_type),
        );
    }

    public function zip(DownloadBiddingDocumentsRequest $request, ProjectInformation $bidding): BinaryFileResponse
    {
        $archivePath = $this->documents->createZip($bidding, $request->validated('document_ids'));

        return response()->download($archivePath, 'bidding-'.$bidding->id.'-documents.zip', $this->fileHeaders('application/zip'))->deleteFileAfterSend(true);
    }

    private function authorizeDocument(string $ability, ProjectInformation $bidding, BiddingDocument $document): void
    {
        Gate::authorize($ability, $bidding);
        abort_unless((int) $document->project_information_id === (int) $bidding->id, 404);
    }

    /** @return array<string, mixed> */
    private function presentDocument(Request $request, ProjectInformation $bidding, BiddingDocument $document): array
    {
        $prefix = $this->prefix($request).'documents.';
        $parameters = [$bidding, $document];

        return [
            'id' => $document->id, 'folder_id' => $document->folder_id, 'display_name' => $document->display_name,
            'original_name' => $document->original_name, 'mime_type' => $document->mime_type, 'extension' => $document->extension,
            'file_size' => $document->file_size, 'description' => $document->description, 'version' => $document->version,
            'uploaded_by' => $document->uploader ? ['id' => $document->uploader->user_id, 'name' => $document->uploader->name] : null,
            'created_at' => $document->created_at->toIso8601String(), 'updated_at' => $document->updated_at->toIso8601String(),
            'uploaded_at' => $document->uploaded_at->toIso8601String(),
            'download_url' => route($prefix.'download', $parameters),
            'preview_url' => $this->documents->canPreview($document) ? route($prefix.'preview', $parameters) : null,
            'history_url' => route($prefix.'history', $parameters), 'update_url' => route($prefix.'update', $parameters),
            'replace_url' => route($prefix.'replace', $parameters), 'delete_url' => route($prefix.'destroy', $parameters),
        ];
    }

    private function prefix(Request $request): string
    {
        return $request->routeIs('project.bidding.*') ? 'project.bidding.' : 'bidding.';
    }

    /** @return array{current_page: int, last_page: int, per_page: int, total: int} */
    private function pagination(LengthAwarePaginator $page): array
    {
        return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()];
    }

    /** @return array<string, string> */
    private function fileHeaders(string $mime): array
    {
        return ['Content-Type' => $mime, 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
    }
}
