<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryReturnRequest;
use App\Models\Delivery;
use App\Models\DeliveryReturn;
use App\Models\Inventory;
use App\Models\InventoryHistory;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DeliveryReturnController extends Controller
{
    public function page(Request $request): View
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'min:1', Rule::exists('projects', 'project_id')],
            'return_type' => ['nullable', Rule::in(['Item Return', 'Document Return'])],
            'return_status' => ['nullable', Rule::in(['Reported', 'In Transit', 'Received', 'Rejected'])],
            'return_date_from' => ['nullable', 'date_format:Y-m-d'],
            'return_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:return_date_from'],
            'received_date_from' => ['nullable', 'date_format:Y-m-d'],
            'received_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:received_date_from'],
            'quantity_from' => ['nullable', 'integer', 'min:1'],
            'quantity_to' => ['nullable', 'integer', 'min:1', 'gte:quantity_from'],
        ]);
        $projects = DB::table('projects')->orderBy('project_name')->get(['project_id', 'project_name']);
        $project = isset($validated['project_id']) ? (int) $validated['project_id'] : null;
        $panel = $project === null ? [] : $this->panelData($project, $validated);

        return view('operation.delivery.returns', [
            'projects' => $projects,
            'selectedProject' => $project,
            'filters' => $validated,
            ...$panel,
        ]);
    }

    public function index(Request $request, int $project): JsonResponse
    {
        abort_unless(DB::table('projects')->where('project_id', $project)->exists(), 404);
        $panel = $this->panelData($project, $request->validate($this->filterRules()));

        return response()->json([
            'html' => view('operation.delivery.partials.monitoring-returns', $panel)->render(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(StoreDeliveryReturnRequest $request, int $project): JsonResponse|RedirectResponse
    {
        $fields = $request->validated();
        $delivery = Delivery::query()->where('project_id', $project)->where('school_id', $fields['school_id'])
            ->orderByDesc('delivery_date')->orderByDesc('delivery_id')->first();
        abort_if($delivery === null, 422, 'No delivery is recorded for this School ID in the selected project.');
        DeliveryReturn::query()->create([
            ...$fields,
            'project_id' => $project,
            'lot_id' => $delivery->lot_id,
            'school_id' => $delivery->school_id,
            'delivery_id' => $delivery->delivery_id,
            'dr_no' => $delivery->dr_no,
            'status' => 'Reported',
            'monitoring_date' => now()->toDateString(),
            'reported_by_user_id' => Auth::id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Return recorded. Stock changes only when an Item Return is marked Received.']);
        }

        return redirect()->route('deliveries.return-monitoring', ['project_id' => $project])
            ->with('success', 'Return recorded. Stock changes only when an Item Return is marked Received.');
    }

    public function update(Request $request, int $project, int $return): JsonResponse|RedirectResponse
    {
        $existingReturn = DeliveryReturn::query()->where('project_id', $project)->findOrFail($return);
        $requiresItemReceipt = $existingReturn->return_type === 'Item Return' && $request->input('status') === 'Received';
        $fields = $request->validate([
            'status' => ['required', Rule::in(['Reported', 'In Transit', 'Received', 'Rejected'])],
            'monitoring_date' => ['required', 'date_format:Y-m-d'],
            'received_date' => ['required_if:status,Received', 'nullable', 'date_format:Y-m-d'],
            'received_by' => ['required_if:status,Received', 'nullable', 'string', 'max:255'],
            'received_warehouse_id' => ['nullable', 'integer', 'exists:warehouse,warehouse_id'],
            'item_id' => [Rule::requiredIf($requiresItemReceipt), 'nullable', 'integer', Rule::exists('item', 'item_id')],
            'quantity_returned' => [Rule::requiredIf($requiresItemReceipt), 'nullable', 'integer', 'min:1'],
        ]);
        DB::transaction(function () use ($project, $return, $fields): void {
            $record = DeliveryReturn::query()->where('project_id', $project)->lockForUpdate()->findOrFail($return);
            abort_if($record->status === 'Received', 409, 'A received return is final because its receipt has been applied to inventory.');
            if ($record->return_type === 'Document Return') {
                $fields['received_warehouse_id'] = null;
                $fields['item_id'] = null;
                $fields['quantity_returned'] = null;
            }
            if ($fields['status'] !== 'Received') {
                $fields['received_date'] = null;
                $fields['received_by'] = null;
                $fields['received_warehouse_id'] = null;
            }
            if ($fields['status'] === 'Received' && $record->return_type === 'Item Return') {
                abort_unless(Item::query()->where('project_id', $project)->whereKey($fields['item_id'])->exists(), 422, 'Choose an item from this project.');
                $record->item_id = $fields['item_id'];
                $record->quantity_returned = $fields['quantity_returned'];
                abort_if(empty($fields['received_warehouse_id']), 422, 'Choose the warehouse that physically received the returned item.');
                abort_if(InventoryHistory::query()->where('delivery_return_id', $record->return_id)->exists(), 409, 'This return has already been credited to inventory.');
                $inventory = Inventory::query()->where('item_id', $record->item_id)
                    ->where('warehouse_id', $fields['received_warehouse_id'])->lockForUpdate()->first();
                if ($inventory === null) {
                    $inventory = new Inventory([
                        'item_id' => $fields['item_id'],
                        'warehouse_id' => $fields['received_warehouse_id'],
                        'qty' => 0,
                        'inventory_status' => 'Approved',
                    ]);
                    $inventory->save();
                }
                $oldQuantity = (int) ($inventory->qty ?? 0);
                $newQuantity = $oldQuantity + (int) $record->quantity_returned;
                $inventory->qty = $newQuantity;
                $inventory->inventory_status = 'Approved';
                $inventory->save();
                InventoryHistory::query()->create([
                    'inventory_id' => $inventory->inventory_id,
                    'item_id' => $fields['item_id'],
                    'warehouse_id' => $fields['received_warehouse_id'],
                    'old_qty' => $oldQuantity,
                    'new_qty' => $newQuantity,
                    'change_type' => 'stock_in',
                    'batch_no' => 'RETURN-'.$record->return_id,
                    'changed_at' => now(),
                    'changed_by' => $fields['received_by'],
                    'changed_by_user_id' => Auth::id(),
                    'remarks' => 'Item return received: '.$record->reason.($record->remarks ? ' — '.$record->remarks : ''),
                    'project_id' => $record->project_id,
                    'delivery_id' => $record->delivery_id,
                    'package_id' => $record->package_id,
                    'dr_no' => $record->dr_no,
                    'quantity' => $record->quantity_returned,
                    'delivery_return_id' => $record->return_id,
                ]);
            }
            $record->fill($fields)->save();
        }, 3);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Return tracking updated.']);
        }

        return redirect()->route('deliveries.return-monitoring', ['project_id' => $project])
            ->with('success', 'Return tracking updated.');
    }

    /** @return array<string, mixed> */
    private function panelData(int $project, array $filters): array
    {
        $records = DeliveryReturn::query()->with(['item', 'warehouse'])->where('project_id', $project)
            ->when($filters['return_type'] ?? null, fn ($query, string $value) => $query->where('return_type', $value))
            ->when($filters['return_status'] ?? null, fn ($query, string $value) => $query->where('status', $value))
            ->when($filters['return_date_from'] ?? null, fn ($query, string $value) => $query->whereDate('monitoring_date', '>=', $value))
            ->when($filters['return_date_to'] ?? null, fn ($query, string $value) => $query->whereDate('monitoring_date', '<=', $value))
            ->when($filters['received_date_from'] ?? null, fn ($query, string $value) => $query->whereDate('received_date', '>=', $value))
            ->when($filters['received_date_to'] ?? null, fn ($query, string $value) => $query->whereDate('received_date', '<=', $value))
            ->when($filters['quantity_from'] ?? null, fn ($query, int $value) => $query->where('quantity_returned', '>=', $value))
            ->when($filters['quantity_to'] ?? null, fn ($query, int $value) => $query->where('quantity_returned', '<=', $value))
            ->orderByDesc('monitoring_date')->orderByDesc('return_id')->paginate(25)->withQueryString();

        return [
            'records' => $records,
            'deliveries' => Delivery::query()->leftJoin('school', 'school.school_id', '=', 'deliveries.school_id')
                ->where('deliveries.project_id', $project)->orderByDesc('deliveries.delivery_date')
                ->get(['deliveries.delivery_id', 'deliveries.dr_no', 'deliveries.lot_id', 'deliveries.school_id', 'school.school_name']),
            'items' => Item::query()->where('project_id', $project)->orderBy('item_name')->get(['item_id', 'item_name', 'unit']),
            'warehouses' => Warehouse::query()->orderBy('warehouse_name')->get(['warehouse_id', 'warehouse_name']),
            'project' => $project,
            'filters' => $filters,
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function filterRules(): array
    {
        return [
            'return_type' => ['nullable', Rule::in(['Item Return', 'Document Return'])],
            'return_status' => ['nullable', Rule::in(['Reported', 'In Transit', 'Received', 'Rejected'])],
            'return_date_from' => ['nullable', 'date_format:Y-m-d'],
            'return_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:return_date_from'],
            'received_date_from' => ['nullable', 'date_format:Y-m-d'],
            'received_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:received_date_from'],
            'quantity_from' => ['nullable', 'integer', 'min:1'],
            'quantity_to' => ['nullable', 'integer', 'min:1', 'gte:quantity_from'],
        ];
    }
}
