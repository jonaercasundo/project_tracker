<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiQuotation;
use App\Models\Company;
use App\Models\MI_Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiQuotationWorkflowService
{
    /** @return array<string, mixed> */
    public function start(AiConversation $conversation, User $user, Company $company, ?int $productId = null): array
    {
        $workflow = DB::table('ai_quotation_workflows')->where('conversation_id', $conversation->getKey())->first();
        $created = false;
        if ($workflow === null) {
            $product = $productId ? MI_Product::query()->find($productId) : null;
            $state = ['customer_name' => '', 'items' => $product ? [['id' => $product->product_id, 'quantity' => 1, 'name' => $product->item_name, 'sku' => $product->sku ?: $product->item_code]] : []];
            try {
                DB::table('ai_quotation_workflows')->insert([
                    'conversation_id' => $conversation->getKey(), 'user_id' => $user->getKey(),
                    'company_id' => $company->getKey(), 'idempotency_key' => (string) Str::uuid(),
                    'state' => json_encode($state, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $created = true;
            } catch (QueryException $exception) {
                $workflow = DB::table('ai_quotation_workflows')->where('conversation_id', $conversation->getKey())->first();
                if ($workflow === null) {
                    throw $exception;
                }
            }
            $workflow ??= DB::table('ai_quotation_workflows')->where('conversation_id', $conversation->getKey())->first();
        }

        abort_unless($workflow->user_id === $user->getKey() && $workflow->company_id === $company->getKey(), 404);
        if ($created) {
            $this->audit($conversation, $user, $company, 'workflow_started', ['has_product' => $productId !== null]);
        }

        return $this->workflowPayload($workflow);
    }

    /** @param array{customer_name: string, items: array<int, array{id: int, quantity: int}>} $input
     * @return array<string, mixed>
     */
    public function preview(AiConversation $conversation, User $user, Company $company, array $input): array
    {
        $workflow = $this->ownedWorkflow($conversation, $user, $company);
        $items = $this->priceItems($input['items']);
        abort_if(trim($input['customer_name']) === '', 422, 'Enter a customer or company name.');
        $state = ['customer_name' => trim($input['customer_name']), 'items' => $input['items'], 'preview_items' => $items];
        DB::transaction(function () use ($workflow, $state, $conversation, $user, $company, $items): void {
            DB::table('ai_quotation_workflows')->where('id', $workflow->id)->update(['state' => json_encode($state, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
            $this->audit($conversation, $user, $company, 'preview_generated', ['item_count' => count($items)]);
        });

        return ['customer_name' => $state['customer_name'], 'items' => $items, 'total' => $this->amount($items), 'currency' => 'PHP'];
    }

    /** @return array{quotation: AiQuotation|null, changed: bool, preview: array<string, mixed>|null} */
    public function confirm(AiConversation $conversation, User $user, Company $company): array
    {
        return DB::transaction(function () use ($conversation, $user, $company): array {
            $workflow = $this->ownedWorkflow($conversation, $user, $company, true);
            if ($workflow->quotation_id !== null) {
                return ['quotation' => AiQuotation::query()->findOrFail($workflow->quotation_id), 'changed' => false, 'preview' => null];
            }
            $state = json_decode((string) $workflow->state, true) ?: [];
            abort_unless(isset($state['customer_name'], $state['items'], $state['preview_items']) && $state['customer_name'] !== '', 422, 'Complete and preview the quotation before confirming it.');
            $latest = $this->priceItems($state['items']);
            if ($this->priceSignature($latest) !== $this->priceSignature($state['preview_items'])) {
                $state['preview_items'] = $latest;
                DB::table('ai_quotation_workflows')->where('id', $workflow->id)->update(['state' => json_encode($state, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
                $this->audit($conversation, $user, $company, 'price_changed_before_confirmation', ['item_count' => count($latest)]);

                return ['quotation' => null, 'changed' => true, 'preview' => ['customer_name' => $state['customer_name'], 'items' => $latest, 'total' => $this->amount($latest), 'currency' => 'PHP']];
            }
            $quote = AiQuotation::query()->create([
                'quote_number' => 'Q-'.now()->format('Ymd-His').'-'.strtoupper(bin2hex(random_bytes(5))),
                'idempotency_key' => $workflow->idempotency_key, 'conversation_id' => $conversation->getKey(),
                'user_id' => $user->getKey(), 'company_id' => $company->getKey(), 'customer_name' => $state['customer_name'],
                'currency' => 'PHP', 'items' => $latest, 'total' => $this->amount($latest), 'status' => 'draft', 'confirmed_at' => now(),
            ]);
            DB::table('ai_quotation_workflows')->where('id', $workflow->id)->update(['quotation_id' => $quote->getKey(), 'updated_at' => now()]);
            $this->audit($conversation, $user, $company, 'draft_confirmed', ['item_count' => count($latest)], $quote->getKey());

            return ['quotation' => $quote, 'changed' => false, 'preview' => null];
        });
    }

    /** @return array<string, mixed> */
    public function current(AiConversation $conversation, User $user, Company $company): array
    {
        $workflow = DB::table('ai_quotation_workflows')->where('conversation_id', $conversation->getKey())->first();
        if ($workflow === null) {
            return ['workflow' => null];
        }
        abort_unless($workflow->user_id === $user->getKey() && $workflow->company_id === $company->getKey(), 404);

        return ['workflow' => $this->workflowPayload($workflow)];
    }

    /** @param array<int, array{id: int, quantity: int}> $items
     * @return array<int, array<string, mixed>>
     */
    private function priceItems(array $items): array
    {
        $products = MI_Product::query()->whereIn('product_id', collect($items)->pluck('id'))->get()->keyBy('product_id');
        $priced = [];
        foreach ($items as $item) {
            $product = $products->get($item['id']);
            abort_unless($product !== null, 422, 'A selected product is no longer available.');
            abort_if($product->price === null || $product->price === '', 422, 'A selected product has no approved selling price.');
            $unitCents = $this->cents((string) $product->price);
            abort_if($unitCents < 0, 422, 'A selected product has an invalid selling price.');
            $lineCents = $unitCents * $item['quantity'];
            $priced[] = ['id' => (int) $product->product_id, 'name' => $product->item_name, 'sku' => $product->sku ?: $product->item_code, 'quantity' => $item['quantity'], 'unit_price' => $this->decimal($unitCents), 'subtotal' => $this->decimal($lineCents), 'currency' => 'PHP'];
        }

        return $priced;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function amount(array $items): string
    {
        return $this->decimal(array_sum(array_map(fn (array $item): int => $this->cents((string) $item['subtotal']), $items)));
    }

    /** @param array<int, array<string, mixed>> $items */
    private function priceSignature(array $items): string
    {
        return hash('sha256', json_encode(array_map(fn (array $item): array => [$item['id'], $item['quantity'], $item['unit_price']], $items), JSON_THROW_ON_ERROR));
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /** @param array<string, bool|int> $metadata */
    public function audit(AiConversation $conversation, User $user, Company $company, string $action, array $metadata = [], ?int $quotationId = null): void
    {
        DB::table('ai_quotation_audits')->insert([
            'conversation_id' => $conversation->getKey(), 'quotation_id' => $quotationId,
            'user_id' => $user->getKey(), 'company_id' => $company->getKey(), 'action' => $action,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function ownedWorkflow(AiConversation $conversation, User $user, Company $company, bool $lock = false): object
    {
        $query = DB::table('ai_quotation_workflows')->where('conversation_id', $conversation->getKey())
            ->where('user_id', $user->getKey())->where('company_id', $company->getKey());
        $workflow = $lock ? $query->lockForUpdate()->first() : $query->first();

        abort_unless($workflow !== null, 404, 'Start a quotation workflow first.');

        return $workflow;
    }

    /** @return array<string, mixed> */
    private function workflowPayload(object $workflow): array
    {
        $quotation = $workflow->quotation_id ? AiQuotation::query()->find($workflow->quotation_id) : null;

        return [
            'id' => $workflow->id,
            'state' => json_decode((string) $workflow->state, true) ?: [],
            'quotation_id' => $workflow->quotation_id,
            'quotation' => $quotation ? ['id' => $quotation->getKey(), 'quote_number' => $quotation->quote_number, 'status' => $quotation->status, 'print_url' => route('assistant.quotations.print', $quotation)] : null,
        ];
    }
}
