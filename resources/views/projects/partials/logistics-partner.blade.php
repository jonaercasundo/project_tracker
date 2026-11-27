<div>
    <!-- Nothing worth having comes easy. - Theodore Roosevelt -->
</div>
<section id="logistics-partner" class="tab-content hidden space-y-5" aria-labelledby="logistics-partner-heading">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 id="logistics-partner-heading" class="text-xl font-extrabold text-slate-950">Logistics Partner</h2>
            <p class="mt-1 text-sm text-slate-500">Manage the logistics partners assigned to {{ $project->project_name }}.</p>
        </div>
        @if ($canManageLogisticsPartners)
            <a href="{{ route('logistics-channel.index', ['tab' => 'integrations', 'project_ids' => [$project->project_id], 'return_project_id' => $project->project_id]) }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14" /></svg>
                Add logistics partner
            </a>
        @endif
    </header>

    @if ($logisticsPartners->isNotEmpty())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($logisticsPartners as $partner)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-extrabold text-slate-950">{{ $partner->name }}</h3>
                            <p class="mt-1 break-all font-mono text-xs text-slate-500">{{ $partner->integration_id }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $partner->status === 'active' ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($partner->status) }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 text-xs">
                        <div><dt class="text-slate-500">Connection</dt><dd class="mt-1 font-semibold text-slate-800">{{ $partner->authentication_method === 'manual' ? 'Manual tracking' : 'API connected' }}</dd></div>
                        <div><dt class="text-slate-500">Credentials</dt><dd class="mt-1 font-semibold text-slate-800">{{ number_format($partner->credentials_count) }}</dd></div>
                    </dl>
                    @can('update', $partner)
                        <a href="{{ route('logistics-channel.index', ['tab' => 'integrations', 'edit' => $partner->id, 'return_project_id' => $project->project_id]) }}" class="mt-4 inline-flex rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">View / edit partner</a>
                    @endcan
                </article>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
            <p class="text-sm font-semibold text-slate-700">No logistics partners are assigned to this project.</p>
            @if ($canManageLogisticsPartners)
                <p class="mt-1 text-xs text-slate-500">Add a partner to manage its project and delivery scope.</p>
            @endif
        </div>
    @endif
</section>
