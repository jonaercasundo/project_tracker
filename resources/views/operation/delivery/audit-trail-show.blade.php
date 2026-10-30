<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <a href="{{ route('delivery-audit-trail.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">← Back to audit trail</a>
        <section class="rounded-2xl border border-slate-200 bg-white p-6">
            <p class="text-xs font-bold uppercase tracking-wider text-blue-600">{{ $event->source_module }} · {{ $event->event_uuid }}</p>
            <h1 class="mt-2 text-2xl font-extrabold text-slate-900">{{ str($event->event_type)->replace('_', ' ')->title() }}</h1>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                @foreach(['Event date and time' => $event->event_at?->format('Y-m-d H:i:s T'), 'Project ID' => $event->project_id, 'Lot ID' => $event->lot_id, 'School ID' => $event->school_id, 'School name' => $event->school_name, 'Core package code' => $event->package_code, 'DR number' => $event->dr_no, 'Previous status' => $event->previous_status, 'New status' => $event->new_status, 'Quantity affected' => $event->quantity, 'Warehouse' => $event->warehouse_name ?? $event->warehouse_id, 'Performed by' => $event->actor_name ?? $event->performer?->name ?? 'System', 'Related transaction' => trim(($event->related_type ?? '').' '.($event->related_id ?? '')), 'Notes / correction reason' => $event->notes]
                    as $label => $value)
                    <div class="rounded-lg bg-slate-50 p-3"><dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words text-sm text-slate-900">{{ $value ?: '—' }}</dd></div>
                @endforeach
            </dl>
            @if($event->metadata)
                <div class="mt-6"><h2 class="text-sm font-bold text-slate-800">Source metadata</h2><pre class="mt-2 max-h-96 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($event->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
            @endif
        </section>
        @can('delivery.audit.correct')
            <form method="POST" action="{{ route('delivery-audit-trail.corrections.store') }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-5">@csrf<input type="hidden" name="event_uuid" value="{{ $event->event_uuid }}"><label class="block text-sm font-bold text-amber-950">Record a correction or reversal reason<textarea name="reason" required maxlength="2000" class="mt-2 w-full rounded-lg border-amber-300 text-sm" rows="3"></textarea></label><p class="mt-1 text-xs text-amber-900">This adds a new audit entry and keeps this event unchanged.</p><button class="mt-3 rounded-lg bg-amber-800 px-4 py-2 text-sm font-semibold text-white">Append correction event</button></form>
        @endcan
    </div>
</x-project-app-layout>
