<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBiddingFolderRequest;
use App\Http\Requests\UpdateBiddingFolderRequest;
use App\Models\BiddingDocumentFolder;
use App\Models\ProjectInformation;
use App\Services\BiddingDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class BiddingFolderController extends Controller
{
    public function __construct(private BiddingDocumentService $documents) {}

    public function store(StoreBiddingFolderRequest $request, ProjectInformation $bidding): JsonResponse
    {
        $folder = $this->documents->createFolder($bidding, $request->validated(), $request->user());

        return response()->json(['message' => 'Folder created.', 'folder' => $this->presentFolder($folder)], 201);
    }

    public function update(UpdateBiddingFolderRequest $request, ProjectInformation $bidding, BiddingDocumentFolder $folder): JsonResponse
    {
        $folder = $this->documents->updateFolder($bidding, $folder, $request->validated());

        return response()->json(['message' => 'Folder updated.', 'folder' => $this->presentFolder($folder)]);
    }

    public function destroy(ProjectInformation $bidding, BiddingDocumentFolder $folder): JsonResponse
    {
        Gate::authorize('update', $bidding);
        abort_unless((int) $folder->project_information_id === (int) $bidding->id, 404);
        $this->documents->deleteFolder($bidding, $folder);

        return response()->json(['message' => 'Folder deleted.']);
    }

    /** @return array{id: int, parent_id: ?int, name: string, document_count: int} */
    private function presentFolder(BiddingDocumentFolder $folder): array
    {
        return ['id' => $folder->id, 'parent_id' => $folder->parent_id, 'name' => $folder->name, 'document_count' => $folder->documents()->count()];
    }
}
