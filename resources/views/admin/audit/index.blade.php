<x-app-layout>
<div class="mx-auto flex max-w-[1500px] flex-col gap-5">
    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div><p class="text-[10px] font-extrabold uppercase tracking-[.15em] text-blue-700">Monitoring &amp; security</p><h2 class="mt-1 text-xl font-extrabold tracking-tight text-[#172a45]">Activity and access log</h2><p class="mt-1.5 max-w-2xl text-xs leading-relaxed text-slate-500">Records successful sign-ins and administrator access changes. Sensitive keys and request payloads are not stored in this log.</p></div>
            <span class="w-fit rounded-lg bg-slate-100 px-3 py-2 text-[10px] font-semibold text-slate-600">{{ $activities->total() }} recorded events</span>
        </div>
        <form method="GET" class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-[minmax(220px,1fr)_220px_auto]">
            <label class="sr-only" for="audit-search">Search audit activity</label><input id="audit-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search action, person, or event" class="rounded-xl border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500/20">
            <label class="sr-only" for="audit-event">Filter by event</label><select id="audit-event" name="event" class="rounded-xl border-slate-200 bg-slate-50 text-xs"><option value="">All events</option><option value="user.login" @selected(($filters['event'] ?? '') === 'user.login')>Successful sign-ins</option><option value="user.access_updated" @selected(($filters['event'] ?? '') === 'user.access_updated')>User access changes</option><option value="role.permissions_updated" @selected(($filters['event'] ?? '') === 'role.permissions_updated')>Permission changes</option><option value="ai.configuration_updated" @selected(($filters['event'] ?? '') === 'ai.configuration_updated')>AI configuration</option><option value="company.updated" @selected(($filters['event'] ?? '') === 'company.updated')>Company changes</option></select>
            <button class="rounded-xl bg-[#174b8a] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#123b72]">Apply filters</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="overflow-x-auto"><table class="min-w-full text-left text-xs"><thead class="bg-slate-50 text-[9px] font-extrabold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Event</th><th class="px-5 py-3">Actor</th><th class="px-5 py-3">Resource</th><th class="px-5 py-3">Company scope</th><th class="px-5 py-3">Timestamp</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($activities as $activity)
                <tr class="align-top hover:bg-slate-50/60"><td class="min-w-[240px] px-5 py-3.5"><p class="font-bold text-slate-800">{{ $activity->summary }}</p><p class="mt-1 font-mono text-[9px] text-slate-400">{{ $activity->event }}</p></td><td class="whitespace-nowrap px-5 py-3.5 text-slate-600">{{ $activity->actor_name ?? 'System' }}</td><td class="px-5 py-3.5 text-slate-600">{{ ucfirst(str_replace('_', ' ', $activity->subject_type)) }}{{ $activity->subject_id ? ' · '.$activity->subject_id : '' }}</td><td class="px-5 py-3.5 text-slate-600">{{ $activity->company_id ? 'Company #'.$activity->company_id : 'Global' }}</td><td class="whitespace-nowrap px-5 py-3.5 text-slate-500">{{ \Illuminate\Support\Carbon::parse($activity->created_at)->format('M j, Y g:i A') }}</td></tr>
            @empty
                <tr><td colspan="5" class="px-5 py-14 text-center"><p class="text-xs font-semibold text-slate-700">No activity matches this filter</p><p class="mt-1 text-[10px] text-slate-400">Sign-ins and administrator changes will appear as they happen.</p></td></tr>
            @endforelse
        </tbody></table></div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $activities->links() }}</div>
    </section>
</div>
</x-app-layout>
