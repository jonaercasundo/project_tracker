<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Models\AiQuotation;
use App\Models\Company;
use App\Models\MI_Product;
use App\Models\User;
use App\Services\AiMiProductPresenter;
use App\Services\AiProviderConfiguration;
use App\Services\AiQuotationWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AiQuotationController extends Controller
{
    public function products(Request $request, AiMiProductPresenter $presenter): JsonResponse
    {
        $this->identity($request, requiredPermission: 'mi.product-prices.view');
        $validated = $request->validate(['query' => ['required', 'string', 'max:120']]);
        $query = trim($validated['query']);
        abort_if($query === '', 422, 'Enter a product name or SKU to search.');
        $products = MI_Product::query()->with(['images', 'category', 'collection'])->where(function ($builder) use ($query): void {
            $builder->where('item_name', 'like', '%'.$query.'%')->orWhere('sku', 'like', '%'.$query.'%')->orWhere('item_code', 'like', '%'.$query.'%');
        })->orderByRaw('CASE WHEN item_name = ? THEN 0 WHEN sku = ? OR item_code = ? THEN 0 ELSE 1 END', [$query, $query, $query])
            ->orderBy('item_name')->limit(8)->get(['product_id', 'item_name', 'sku', 'item_code', 'price']);

        return response()->json(['products' => $products->map(fn (MI_Product $product): array => $presenter->present($product, true))]);
    }

    public function start(Request $request, AiConversation $conversation, AiQuotationWorkflowService $workflow): JsonResponse
    {
        [$user, $company] = $this->identity($request, $conversation);
        $validated = $request->validate(['product_id' => ['nullable', 'integer', 'exists:mi_products,product_id']]);

        return response()->json($workflow->start($conversation, $user, $company, isset($validated['product_id']) ? (int) $validated['product_id'] : null), 201);
    }

    public function show(Request $request, AiConversation $conversation, AiQuotationWorkflowService $workflow): JsonResponse
    {
        [$user, $company] = $this->identity($request, $conversation);

        return response()->json($workflow->current($conversation, $user, $company));
    }

    public function preview(Request $request, AiConversation $conversation, AiQuotationWorkflowService $workflow): JsonResponse
    {
        [$user, $company] = $this->identity($request, $conversation);
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.id' => ['required', 'integer', 'distinct', 'exists:mi_products,product_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        return response()->json(['preview' => $workflow->preview($conversation, $user, $company, $validated)]);
    }

    public function confirm(Request $request, AiConversation $conversation, AiQuotationWorkflowService $workflow): JsonResponse
    {
        [$user, $company] = $this->identity($request, $conversation);
        $result = $workflow->confirm($conversation, $user, $company);
        if ($result['changed']) {
            return response()->json(['message' => 'Product prices changed since the preview. Review the updated totals and confirm again.', 'preview' => $result['preview']], 409);
        }

        return response()->json(['quotation' => ['id' => $result['quotation']->getKey(), 'quote_number' => $result['quotation']->quote_number, 'status' => $result['quotation']->status, 'print_url' => route('assistant.quotations.print', $result['quotation'])]], 201);
    }

    public function cancel(Request $request, AiConversation $conversation, AiQuotationWorkflowService $workflow): JsonResponse
    {
        [$user, $company] = $this->identity($request, $conversation);
        DB::table('ai_quotation_workflows')->where('conversation_id', $conversation->getKey())
            ->where('user_id', $user->getKey())->where('company_id', $company->getKey())->whereNull('quotation_id')->delete();
        $workflow->audit($conversation, $user, $company, 'workflow_cancelled');

        return response()->json(['cancelled' => true]);
    }

    public function print(Request $request, AiQuotation $quotation): View
    {
        [$user, $company] = $this->identity($request);
        abort_unless($quotation->user_id === $user->getKey() && $quotation->company_id === $company->getKey(), 404);
        $products = MI_Product::query()->with(['category', 'subCategory', 'productType', 'collection', 'images'])
            ->whereIn('product_id', collect($quotation->items)->pluck('id'))->get()->keyBy('product_id');
        $quotationItems = collect($quotation->items)->map(function (array $item) use ($products): object {
            $product = $products->get($item['id']);
            abort_unless($product !== null, 404, 'A quoted product is unavailable.');
            $product->item_name = $item['name'];
            $product->sku = $item['sku'];

            return (object) ['product' => $product, 'quantity' => $item['quantity'], 'unit_price' => $item['unit_price'], 'subtotal' => $item['subtotal']];
        });

        return view('mi_app.public.print', [
            'customer_name' => $quotation->customer_name, 'quotationItems' => $quotationItems,
            'total' => $quotation->total, 'quote_number' => $quotation->quote_number,
            'issued_at' => $quotation->confirmed_at, 'currency_symbol' => '₱',
        ]);
    }

    /** @return array{0: User, 1: Company} */
    private function identity(Request $request, ?AiConversation $conversation = null, string $requiredPermission = 'mi.quotation.create'): array
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);
        abort_unless($user->can('ai.assistant.use') && $user->can($requiredPermission), 403);
        if ($requiredPermission === 'mi.quotation.create') {
            abort_unless($user->can('mi.product-prices.view'), 403);
            abort_unless(Schema::hasTable('ai_quotation_workflows') && Schema::hasTable('ai_quotations'), 503, 'The quotation workflow is not available until its database migration is installed.');
        }
        $company = $user->currentCompany();
        abort_unless($company instanceof Company && $company->code === 'MI' && $user->belongsToCompany($company->getKey()), 403);
        abort_unless(app(AiProviderConfiguration::class)->isEnabled(), 503);
        if ($conversation !== null) {
            abort_unless($conversation->user_id === $user->getKey() && $conversation->company_id === $company->getKey(), 404);
        }

        return [$user, $company];
    }
}
