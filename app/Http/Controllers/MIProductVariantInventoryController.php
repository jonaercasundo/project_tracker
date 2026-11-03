<?php

namespace App\Http\Controllers;

use App\Models\MIProductVariant;
use App\Models\MIProductVariantInventory;
use App\Models\MIProductVariantInventoryMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MIProductVariantInventoryController extends Controller
{
    public function store(Request $request, MIProductVariant $variant): RedirectResponse
    {
        Gate::authorize('update', $variant->product);
        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouse', 'warehouse_id')],
            'movement_type' => ['required', Rule::in(['stock_in', 'stock_out'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000000'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($variant, $validated, $request): void {
            $lockedVariant = MIProductVariant::query()->whereKey($variant->getKey())->lockForUpdate()->firstOrFail();
            $inventory = MIProductVariantInventory::query()->firstOrCreate([
                'variant_id' => $lockedVariant->getKey(),
                'warehouse_id' => $validated['warehouse_id'],
            ], ['quantity' => 0]);
            $inventory = MIProductVariantInventory::query()->whereKey($inventory->getKey())->lockForUpdate()->firstOrFail();
            $change = $validated['movement_type'] === 'stock_in' ? (int) $validated['quantity'] : -(int) $validated['quantity'];
            $newQuantity = (int) $inventory->quantity + $change;
            if ($newQuantity < 0) {
                throw ValidationException::withMessages(['quantity' => 'The stock out quantity cannot exceed this variant’s available stock.']);
            }
            $inventory->update(['quantity' => $newQuantity]);
            MIProductVariantInventoryMovement::query()->create([
                'variant_id' => $lockedVariant->getKey(),
                'warehouse_id' => $validated['warehouse_id'],
                'movement_type' => $validated['movement_type'],
                'quantity_change' => $change,
                'quantity_after' => $newQuantity,
                'changed_by' => $request->user()->getAuthIdentifier(),
                'remarks' => $validated['remarks'] ?? null,
            ]);
        });

        return back()->with('success', 'Variant stock movement recorded.');
    }
}
