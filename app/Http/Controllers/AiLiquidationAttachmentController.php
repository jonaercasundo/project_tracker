<?php

namespace App\Http\Controllers;

use App\Models\AiAssistantAttachment;
use App\Models\AiConversation;
use App\Models\Company;
use App\Models\MI_Liquidation;
use App\Models\User;
use App\Services\AiCapabilityRegistry;
use App\Services\AiLiquidationExcelDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AiLiquidationAttachmentController extends Controller
{
    public function upload(Request $request, AiConversation $conversation, AiLiquidationExcelDraftService $drafts, AiCapabilityRegistry $capabilities): JsonResponse
    {
        [$user, $company] = $this->authorizeConversationContext($request, $conversation);
        $this->authorizeCapability($capabilities, 'ai.attachments.upload', $user, $company);
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'extensions:xlsx,xls,csv,pdf,docx,txt'],
        ]);
        $extension = strtolower($validated['file']->getClientOriginalExtension());
        if (in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            $this->authorizeLiquidationDraft($capabilities, $user, $company);
        } else {
            $this->authorizeCapability($capabilities, 'ai.documents.process', $user, $company);
        }
        $drafts->purgeExpired();
        $parsed = $drafts->inspectAttachment($validated['file'], $user->name);
        $path = $validated['file']->store('private/ai-assistant/liquidation-workbooks', 'local');
        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages(['file' => 'The workbook could not be stored securely. Try again.']);
        }

        try {
            $attachment = DB::transaction(function () use ($conversation, $user, $company, $validated, $parsed, $path): AiAssistantAttachment {
                $attachment = AiAssistantAttachment::query()->create([
                    'conversation_id' => $conversation->getKey(),
                    'user_id' => $user->getKey(),
                    'company_id' => $company->getKey(),
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $parsed['filename'],
                    'size_bytes' => $validated['file']->getSize(),
                    'sha256' => hash_file('sha256', $validated['file']->getRealPath()),
                    'parsed_data' => $parsed,
                    'expires_at' => now()->addHours(24),
                ]);
                $this->audit($attachment, 'uploaded');

                return $attachment;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return response()->json(['attachment' => $this->attachmentPayload($attachment)], 201);
    }

    public function fill(Request $request, AiAssistantAttachment $attachment, AiCapabilityRegistry $capabilities): JsonResponse
    {
        [$user, $company] = $this->authorizeConversationContext($request, $attachment->conversation);
        $this->authorizeAttachment($attachment, $user, $company);
        abort_unless(($attachment->parsed_data['kind'] ?? 'workbook') === 'workbook', 422, 'Only an analyzed Excel workbook can fill the MI liquidation form.');
        $this->authorizeLiquidationDraft($capabilities, $user, $company);
        $this->authorizeCapability($capabilities, 'ai.mi.liquidation.form.fill', $user, $company);

        $request->session()->put('ai_liquidation_draft_id', $attachment->getKey());
        $this->audit($attachment, 'form_prefill_requested');

        return response()->json(['url' => route('liquidation.create')]);
    }

    public function destroy(Request $request, AiAssistantAttachment $attachment, AiLiquidationExcelDraftService $drafts): JsonResponse
    {
        [$user, $company] = $this->authorizeConversationContext($request, $attachment->conversation);
        $this->authorizeAttachment($attachment, $user, $company);
        $this->audit($attachment, 'cancelled');
        $drafts->forget($attachment);

        return response()->json(['deleted' => true]);
    }

    /** @return array{0: User, 1: Company} */
    private function authorizeConversationContext(Request $request, AiConversation $conversation): array
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->can('ai.assistant.use'), 403);
        $company = $user->currentCompany();
        abort_unless($company instanceof Company && $company->is_active
            && $user->belongsToCompany($company->getKey()), 403, 'Select an active company you belong to before uploading attachments.');
        abort_unless((int) $conversation->user_id === (int) $user->getKey() && (int) $conversation->company_id === (int) $company->getKey(), 404);

        return [$user, $company];
    }

    private function authorizeLiquidationDraft(AiCapabilityRegistry $capabilities, User $user, Company $company): void
    {
        abort_unless($company->code === 'MI', 403, 'Excel liquidation drafts are available only in the MI company.');
        Gate::authorize('create', MI_Liquidation::class);
        $diagnostic = $capabilities->diagnostic('ai.excel.analyze', $user, $company, true, true);
        abort_unless($diagnostic['allowed'], 403, $diagnostic['reason']);
    }

    private function authorizeCapability(AiCapabilityRegistry $capabilities, string $capabilityId, User $user, Company $company): void
    {
        $diagnostic = $capabilities->diagnostic($capabilityId, $user, $company, true, true);
        abort_unless($diagnostic['allowed'], 403, $diagnostic['reason']);
    }

    private function authorizeAttachment(AiAssistantAttachment $attachment, User $user, Company $company): void
    {
        abort_unless((int) $attachment->user_id === (int) $user->getKey()
            && (int) $attachment->company_id === (int) $company->getKey()
            && $attachment->expires_at?->isFuture(), 404);
    }

    /** @return array<string, mixed> */
    private function attachmentPayload(AiAssistantAttachment $attachment): array
    {
        $preview = $attachment->parsed_data;
        if (($preview['kind'] ?? 'workbook') === 'workbook') {
            $preview['rows'] = array_slice($preview['rows'] ?? [], 0, 20);
            $preview['warnings'] = array_slice($preview['warnings'] ?? [], 0, 20);
        } else {
            unset($preview['extracted_text']);
        }

        return [
            'id' => $attachment->getKey(),
            'filename' => $attachment->original_name,
            'size_bytes' => $attachment->size_bytes,
            'expires_at' => $attachment->expires_at?->toIso8601String(),
            'preview' => $preview,
        ];
    }

    private function audit(AiAssistantAttachment $attachment, string $event): void
    {
        DB::table('ai_assistant_attachment_audits')->insert([
            'attachment_id' => $attachment->getKey(),
            'actor_user_id' => $attachment->user_id,
            'company_id' => $attachment->company_id,
            'conversation_id' => $attachment->conversation_id,
            'event' => $event,
            'item_count' => (int) ($attachment->parsed_data['item_count'] ?? 0),
            'file_sha256' => $attachment->sha256,
            'created_at' => now(),
        ]);
    }
}
