<x-project-app-layout>
    <div class="mx-auto max-w-[1500px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.16em] text-blue-600">Delivery operations</p>
            <h1 class="mt-1 text-2xl font-extrabold text-slate-900">Delivery Audit Trail</h1>
            <p class="mt-1 text-sm text-slate-500">Recorded inventory, delivery, logistics and billing activity, in chronological order.</p>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-950">
            @if($coverage['earliest'])
                Available recorded history begins {{ \Illuminate\Support\Carbon::parse($coverage['earliest'])->format('M j, Y g:i A') }}. Older delivery status changes may not have been recorded; this trail does not reconstruct them from current status.
            @else
                No historical events are available yet. New activity will appear here as it is recorded.
            @endif
        </div>

        <form method="GET" class="grid grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
            <label class="text-xs font-semibold text-slate-600">Project<select name="project_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->project_id }}" @selected(($filters['project_id'] ?? '') == $project->project_id)>{{ $project->project_name }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-slate-600">Lot<select name="lot_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">All lots</option>@foreach($lots as $lot)<option value="{{ $lot->lot_id }}" @selected(($filters['lot_id'] ?? '') == $lot->lot_id)>{{ $lot->lot_name }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-slate-600">School ID or name<input name="school" value="{{ $filters['school'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">DR number<input name="dr_no" value="{{ $filters['dr_no'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">Search records<input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Project, package, event or notes" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">Event type<select name="event_type" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">All event types</option>@foreach($eventTypes as $type)<option value="{{ $type }}" @selected(($filters['event_type'] ?? '') === $type)>{{ str($type)->replace('_', ' ')->title() }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-slate-600">From<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">To<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">Performed by<select name="performer" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Anyone or system</option>@foreach($performers as $performer)<option value="{{ $performer['value'] }}" @selected(($filters['performer'] ?? '') === $performer['value'])>{{ $performer['label'] }}</option>@endforeach</select></label>
            <div class="flex items-end gap-2 lg:col-span-4"><button class="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white">Apply filters</button><a href="{{ route('delivery-audit-trail.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Clear</a></div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-[1500px] divide-y divide-slate-200 text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">Date &amp; time</th><th class="px-4 py-3">Project / Lot</th><th class="px-4 py-3">School</th><th class="px-4 py-3">Package</th><th class="px-4 py-3">DR</th><th class="px-4 py-3">Event / status</th><th class="px-4 py-3">Qty</th><th class="px-4 py-3">Warehouse</th><th class="px-4 py-3">Performed by</th><th class="px-4 py-3">Source / related ID</th><th class="px-4 py-3">Details</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($events as $event)
                        <tr class="align-top hover:bg-slate-50"><td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $event->event_at?->format('Y-m-d H:i:s') }}</td><td class="px-4 py-3"><div>{{ $projects->firstWhere('project_id', $event->project_id)?->project_name ?? '—' }}</div><div class="mt-1 text-slate-500">{{ $lots->firstWhere('lot_id', $event->lot_id)?->lot_name ?? '—' }}</div></td><td class="px-4 py-3"><div>{{ $event->school_id ?? '—' }}</div><div class="mt-1 text-slate-500">{{ $event->school_name ?? '—' }}</div></td><td class="px-4 py-3">{{ $event->package_code ?? '—' }}</td><td class="px-4 py-3">{{ $event->dr_no ?? '—' }}</td><td class="px-4 py-3"><div class="font-semibold">{{ str($event->event_type)->replace('_', ' ')->title() }}</div><div class="mt-1 text-slate-500">{{ $event->previous_status ?? '—' }} → {{ $event->new_status ?? '—' }}</div>@if(data_get($event->metadata, 'mapping_review'))<span class="mt-1 inline-block rounded bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-900">Mapping review needed</span>@endif</td><td class="px-4 py-3">{{ $event->quantity ?? '—' }}</td><td class="px-4 py-3">{{ $event->warehouse_name ?? ($event->warehouse_id ? 'Warehouse '.$event->warehouse_id : '—') }}</td><td class="px-4 py-3">{{ $event->actor_name ?? $event->performer?->name ?? 'System' }}</td><td class="px-4 py-3"><div>{{ $event->source_module }}</div><div class="mt-1 text-slate-500">{{ $event->related_type ? $event->related_type.': ' : '' }}{{ $event->related_id ?? '—' }}</div></td><td class="px-4 py-3"><a class="font-semibold text-blue-700 hover:underline" href="{{ route('delivery-audit-trail.show', $event) }}">Inspect event</a><p class="mt-1 max-w-48 truncate text-slate-500">{{ $event->notes }}</p></td></tr>
                    @empty
                        <tr><td colspan="11" class="px-6 py-14 text-center"><div class="mx-auto max-w-md"><p class="text-sm font-bold text-slate-800">No audit events match these filters</p><p class="mt-1 text-sm text-slate-500">Try a wider date range or clear one or more filters. New recorded delivery activity will appear here.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($events->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $events->links() }}</div>@endif
        </div>
    </div>
</x-project-app-layout>
