<div class="flex items-center gap-1">
    <button type="button" data-expand-project="{{ $project['project_id'] }}" aria-expanded="false" aria-controls="{{ $detailsId }}" aria-label="Stage details for {{ $project['project_name'] }}" title="Stage details" class="rounded p-1.5 text-slate-500 hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-blue-600"><span data-chevron aria-hidden="true">&#9656;</span></button>
    <a href="{{ route('projects.show', $project['project_id']) }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-50" aria-label="View project {{ $project['project_name'] }}">View</a>
    <details class="relative" data-actions-menu>
        <summary class="list-none cursor-pointer rounded p-1.5 text-slate-600 hover:bg-slate-100" aria-label="Actions for {{ $project['project_name'] }}" title="Project actions">&#8942;</summary>
        <div data-actions-popover class="absolute right-0 z-20 mt-1 w-40 rounded-lg border border-slate-200 bg-white p-1 text-xs shadow-lg">
            <a href="{{ route('projects.show', $project['project_id']) }}" class="block rounded px-3 py-2 hover:bg-slate-50">View Project</a>
            <a href="{{ route('deliveries.index', $project['project_id']) }}?project={{ $project['project_id'] }}" class="block rounded px-3 py-2 hover:bg-slate-50">View Deliveries</a>
            <button type="button" data-expand-project="{{ $project['project_id'] }}" aria-expanded="false" aria-controls="{{ $detailsId }}" class="block w-full rounded px-3 py-2 text-left hover:bg-slate-50">Stage Details</button>
        </div>
    </details>
</div>
