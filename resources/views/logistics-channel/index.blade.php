<x-project_app-layout>
    @php
        $tabs = [
            'overview' => 'Overview',
            'integrations' => 'Integrations',
            'api' => 'API & Webhooks',
            'tracking' => 'DR Tracking',
            'events' => 'Event Logs',
            'reconciliation' => 'Reconciliation',
            'audit' => 'Audit Logs',
        ];
        $cards = [
            ['Total integrations', $metrics['total_integrations'], 'text-slate-900'],
            ['Active integrations', $metrics['active_integrations'], 'text-emerald-700'],
            ['Authorized projects', $metrics['authorized_projects'], 'text-blue-700'],
            ['Webhook events received', $metrics['received_events'], 'text-slate-900'],
            ['Synchronized DR scopes', $metrics['synchronized_drs'], 'text-blue-700'],
            ['Pending updates', $metrics['pending_updates'], 'text-amber-700'],
            ['Failed events', $metrics['failed_events'], 'text-rose-700'],
            ['Reconciliation required', $metrics['reconciliation_required'], 'text-orange-700'],
        ];
    @endphp
    <main class="space-y-6" id="logistics-channel">
        <header class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-lg">
            <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-cyan-300">MMC · External delivery partner</p>
                    <h1 class="mt-3 text-3xl font-black tracking-tight">Logistics Channel</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Manage partner credentials and project access, then follow each verified DR from Logistics custody through delivery.</p>
                </div>
                <div class="flex flex-wrap gap-3 text-xs">
                    <span class="rounded-full border border-white/15 bg-white/5 px-3 py-2">Queue {{ $metrics['queue_depth'] === null ? 'unavailable' : number_format($metrics['queue_depth']).' waiting' }}</span>
                    <span class="rounded-full px-3 py-2 {{ $metrics['worker_activity'] === 'backlog' ? 'bg-rose-400/15 text-rose-200' : ($metrics['worker_activity'] === 'recent' ? 'bg-emerald-400/15 text-emerald-200' : 'bg-amber-300/15 text-amber-100') }}">
                        Worker {{ ['recent' => 'recent activity', 'backlog' => 'backlog needs attention', 'idle' => 'idle / no recent activity', 'unavailable' => 'health unavailable'][$metrics['worker_activity']] }}
                    </span>
                </div>
            </div>
            <nav class="flex gap-1 overflow-x-auto border-t border-white/10 px-4 py-3" aria-label="Logistics Channel sections">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('logistics-channel.index', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif class="whitespace-nowrap rounded-xl px-3 py-2 text-xs font-bold transition {{ $tab === $key ? 'bg-cyan-300 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </header>

        @if(session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
        @endif

        @if($tab === 'overview')
            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Logistics Channel summary">
                @foreach($cards as [$label, $value, $tone])
                    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-3 text-3xl font-black tabular-nums {{ $tone }}">{{ number_format($value) }}</p>
                    </article>
                @endforeach
            </section>
            <section class="grid gap-4 lg:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-extrabold text-slate-900">Synchronization</h2>
                    <dl class="mt-4 grid grid-cols-[1fr_auto] gap-y-3 text-sm">
                        <dt class="text-slate-500">Last successful DR update</dt>
                        <dd class="font-semibold text-slate-800">{{ $metrics['last_successful_sync'] ? \Illuminate\Support\Carbon::parse($metrics['last_successful_sync'])->timezone(config('app.timezone'))->format('M j, Y g:i A') : 'No successful sync yet' }}</dd>
                        <dt class="text-slate-500">Waiting webhook jobs</dt>
                        <dd class="font-semibold text-slate-800">{{ $metrics['queue_depth'] === null ? 'Queue table unavailable' : number_format($metrics['queue_depth']).' waiting · '.number_format($metrics['reserved_queue_jobs']).' reserved' }}</dd>
                        <dt class="text-slate-500">Worker signal</dt>
                        <dd class="font-semibold text-slate-800">{{ $metrics['worker_activity'] === 'recent' ? 'Recent successful work' : ($metrics['worker_activity'] === 'backlog' ? 'Backlog with no recent success' : 'No direct process heartbeat is configured') }}</dd>
                    </dl>
                    <p class="mt-4 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-500">Worker activity is inferred from successful webhook processing and queue depth. This page does not claim a live process heartbeat.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div><h2 class="text-sm font-extrabold text-slate-900">Current delivery milestones</h2><p class="mt-1 text-xs text-slate-500">Logistics status remains separate from MMC receiving and acceptance.</p></div>
                        <a href="{{ route('logistics-channel.index', ['tab' => 'tracking']) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">Open tracking</a>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <span class="rounded-xl bg-blue-50 px-4 py-3 text-sm font-bold text-blue-800">Logistics Received: {{ number_format($trackingRows['summary']['logistics_received'] ?? 0) }}</span>
                        <span class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">Logistics Delivered: {{ number_format($trackingRows['summary']['logistics_delivered'] ?? 0) }}</span>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-slate-500">Pending counts represent distinct MMC DR scopes with no current Logistics milestone. No status is created when a webhook has not arrived.</p>
                </article>
            </section>
        @endif

        @if($tab === 'integrations')
            <section class="grid gap-5 xl:grid-cols-[minmax(300px,0.85fr)_minmax(0,1.4fr)]">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-900">{{ $editing ? 'Edit integration' : 'Add integration' }}</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Each partner receives a separate HMAC key and an explicit MMC project scope.</p>
                    <form method="POST" action="{{ $editing ? route('logistics-channel.integrations.update', $editing) : route('logistics-channel.integrations.store') }}" class="mt-5 space-y-4" id="integration-form" data-schools-url="{{ route('logistics-channel.schools') }}" data-selected-schools="{{ json_encode($editingSchoolTokens) }}">
                        @csrf
                        @if($editing) @method('PATCH') @endif
                        <div>
                            <label for="integration-name" class="mb-1 block text-xs font-bold text-slate-600">Integration name</label>
                            <input id="integration-name" name="name" required maxlength="120" value="{{ old('name', $editing->name ?? '') }}" class="w-full rounded-xl border-slate-200 text-sm focus:border-cyan-600 focus:ring-cyan-600">
                        </div>
                        @unless($editing)
                            <div>
                                <label for="integration-id" class="mb-1 block text-xs font-bold text-slate-600">Integration ID</label>
                                <input id="integration-id" name="integration_id" required maxlength="64" pattern="[A-Za-z0-9._-]+" value="{{ old('integration_id') }}" placeholder="logistics-partner-east" class="w-full rounded-xl border-slate-200 font-mono text-sm focus:border-cyan-600 focus:ring-cyan-600">
                            </div>
                        @endunless
                        <div>
                            <label for="webhook-url" class="mb-1 block text-xs font-bold text-slate-600">Webhook endpoint URL</label>
                            <input id="webhook-url" name="webhook_url" type="url" value="{{ old('webhook_url', $editing->webhook_url ?? $webhookUrl) }}" class="w-full rounded-xl border-slate-200 text-sm focus:border-cyan-600 focus:ring-cyan-600">
                        </div>
                        <div>
                            <label for="integration-status" class="mb-1 block text-xs font-bold text-slate-600">Integration status</label>
                            <select id="integration-status" name="status" class="w-full rounded-xl border-slate-200 text-sm focus:border-cyan-600 focus:ring-cyan-600">
                                <option value="active" @selected(old('status', $editing->status ?? 'active') === 'active')>Active</option>
                                <option value="disabled" @selected(old('status', $editing->status ?? 'active') === 'disabled')>Disabled</option>
                            </select>
                        </div>
                        <fieldset>
                            <legend class="mb-2 text-xs font-bold text-slate-600">Enabled event types</legend>
                            @foreach(['delivery.logistics_received' => 'Logistics Received', 'delivery.delivered' => 'Logistics Delivered'] as $eventType => $eventLabel)
                                <label class="flex items-center gap-2 py-1.5 text-sm text-slate-700"><input type="checkbox" name="enabled_event_types[]" value="{{ $eventType }}" @checked(in_array($eventType, old('enabled_event_types', $editing->enabled_event_types ?? array_keys(['delivery.logistics_received' => 1, 'delivery.delivered' => 1])), true)) class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-600">{{ $eventLabel }}</label>
                            @endforeach
                        </fieldset>
                        <div>
                            <label for="authorized-projects" class="mb-1 block text-xs font-bold text-slate-600">Authorized projects</label>
                            <select id="authorized-projects" name="project_ids[]" multiple required size="6" class="w-full rounded-xl border-slate-200 text-sm focus:border-cyan-600 focus:ring-cyan-600">
                                @foreach($projects as $project)
                                    <option value="{{ $project->project_id }}" @selected(in_array((int) $project->project_id, array_map('intval', old('project_ids', $selectedProjects)), true))>{{ $project->project_name ?: 'Project '.$project->project_id }} · {{ $project->project_id }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500">Use Ctrl or Command to select multiple projects. A project is never added implicitly.</p>
                        </div>
                        <div>
                            <label for="authorized-schools" class="mb-1 block text-xs font-bold text-slate-600">Authorized schools <span class="font-normal text-slate-400">(optional)</span></label>
                            <select id="authorized-schools" name="school_scopes[]" multiple size="5" class="w-full rounded-xl border-slate-200 text-sm focus:border-cyan-600 focus:ring-cyan-600">
                                @foreach($schools as $school)
                                    @php($scopeToken = (int) $school->project_id.':'.rtrim(strtr(base64_encode((string) $school->school_id), '+/', '-_'), '='))
                                    <option value="{{ $scopeToken }}" @selected(in_array($scopeToken, old('school_scopes', $editingSchoolTokens), true))>{{ $school->school_name ?: $school->school_id }} · {{ $school->school_id }} · Project {{ $school->project_id }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[11px] text-slate-500">A selected school is tied to its project. If no schools are selected, explicitly allow all schools below.</p>
                            <label class="mt-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-950"><input id="all-schools" type="checkbox" name="all_schools" value="1" @checked((string) old('all_schools', $allSchools ? '1' : '') === '1') class="mt-0.5 rounded border-amber-400 text-amber-800 focus:ring-amber-700"><span><strong>Allow every school</strong> in the selected projects. Choose this when school-level restriction is not required.</span></label>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <button class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-600">{{ $editing ? 'Save integration' : 'Create integration and key' }}</button>
                            @if($editing)<a href="{{ route('logistics-channel.index', ['tab' => 'integrations']) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700">Cancel</a>@endif
                        </div>
                    </form>
                    @if($editing)
                        <form method="POST" action="{{ route('logistics-channel.integrations.rotate', $editing) }}" class="mt-5 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-5" onsubmit="return confirm('Create a new signing credential? Current active credentials remain valid for the overlap period.')">
                            @csrf
                            <label class="text-xs font-bold text-slate-600">Key overlap days<select name="overlap_days" class="mt-1 block rounded-lg border-slate-200 text-sm"><option>7</option><option>3</option><option>14</option><option>30</option></select></label>
                            <button class="rounded-xl border border-amber-300 px-4 py-2.5 text-xs font-bold text-amber-800 hover:bg-amber-50">Rotate HMAC key</button>
                        </form>
                    @endif
                </article>

                <article class="space-y-4">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4"><h2 class="text-sm font-extrabold text-slate-900">Managed integrations</h2><p class="mt-1 text-xs text-slate-500">Secrets are encrypted at rest. Only key IDs and credential state are listed.</p></div>
                        <div class="divide-y divide-slate-100">
                            @forelse($integrations as $integration)
                                <div class="space-y-3 p-5">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div><h3 class="font-bold text-slate-900">{{ $integration->name }}</h3><p class="mt-1 font-mono text-xs text-slate-500">{{ $integration->integration_id }}</p></div>
                                        <div class="flex gap-2"><span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $integration->status === 'active' ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($integration->status) }}</span><a href="{{ route('logistics-channel.index', ['tab' => 'integrations', 'edit' => $integration->id]) }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700">Edit</a></div>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        @forelse($integration->credentials as $credential)
                                            <div class="flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                                <code class="font-mono text-slate-700">{{ $credential->key_id }}</code>
                                                <span class="rounded-full px-2 py-0.5 font-bold {{ $credential->status === 'active' && (!$credential->expires_at || $credential->expires_at->isFuture()) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ $credential->status }}{{ $credential->expires_at ? ' · expires '.$credential->expires_at->format('M j') : '' }}</span>
                                                <span class="text-slate-500">{{ $credential->last_used_at ? 'Last used '.$credential->last_used_at->timezone(config('app.timezone'))->format('M j, g:i A') : 'Not used yet' }}</span>
                                                @if($credential->status === 'active')
                                                    <form method="POST" action="{{ route('logistics-channel.integrations.credentials.revoke', [$integration, $credential]) }}" onsubmit="return confirm('Revoke this credential immediately? Signed webhook requests using this key will fail.')">@csrf<button class="font-bold text-rose-700 hover:underline">Revoke</button></form>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-xs text-slate-500">No credentials registered</span>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <p class="p-6 text-sm text-slate-500">No database-managed integrations yet. Environment integrations remain active until explicitly adopted.</p>
                            @endforelse
                        </div>
                        <div class="border-t border-slate-100 p-4">{{ $integrations->links() }}</div>
                    </div>

                    @if($legacyIntegrations->isNotEmpty())
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                            <h2 class="text-sm font-extrabold text-amber-950">Environment-managed integrations</h2>
                            <p class="mt-1 text-xs leading-5 text-amber-900">These continue using their current HMAC secrets and configured scopes. Adoption encrypts the existing secret in MMC without showing or rotating it.</p>
                            @foreach($legacyIntegrations as $legacy)
                                <form method="POST" action="{{ route('logistics-channel.integrations.adopt') }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-amber-200 bg-white p-4" onsubmit="return confirm('Adopt this environment integration into encrypted database storage without changing its secret or project scope?')">
                                    @csrf<input type="hidden" name="integration_id" value="{{ $legacy['integration_id'] }}">
                                    <div class="min-w-48 flex-1"><label class="mb-1 block text-[11px] font-bold text-slate-600">Integration ID</label><p class="font-mono text-sm text-slate-900">{{ $legacy['integration_id'] }}</p></div>
                                    <label class="min-w-40 flex-1 text-[11px] font-bold text-slate-600">Display name<input name="name" required maxlength="120" value="{{ $legacy['name'] }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm font-normal"></label>
                                    <div class="w-full text-[11px] text-slate-500">Authorized project IDs: {{ $legacy['project_ids'] ? implode(', ', $legacy['project_ids']) : 'No project-wide scope (approved DR mappings only)' }}</div>
                                    <button class="rounded-lg bg-amber-900 px-3 py-2 text-xs font-bold text-white hover:bg-amber-800">Adopt without rotation</button>
                                </form>
                            @endforeach
                        </div>
                    @endif
                </article>
            </section>
        @endif

        @if($tab === 'api')
            <section class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-cyan-800">Existing receiver contract</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950">HMAC-SHA256 delivery events</h2>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div><dt class="text-xs font-bold text-slate-500">Endpoint</dt><dd class="mt-1 break-all rounded-lg bg-slate-950 p-3 font-mono text-xs text-cyan-100">POST {{ $webhookUrl }}</dd></div>
                        <div><dt class="text-xs font-bold text-slate-500">Headers</dt><dd class="mt-1 font-mono text-xs leading-6 text-slate-700">X-Integration-Key-Id<br>X-Webhook-Timestamp<br>X-Webhook-Signature: v1=&lt;hex&gt;</dd></div>
                        <div><dt class="text-xs font-bold text-slate-500">Signed bytes</dt><dd class="mt-1 rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-700">key_id + LF + timestamp + LF + raw_body</dd></div>
                        <div><dt class="text-xs font-bold text-slate-500">Timestamp window</dt><dd class="mt-1 text-slate-700">300 seconds. Retry with a fresh timestamp and the same event ID/body.</dd></div>
                    </dl>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-extrabold text-slate-900">Events and scope</h2>
                    <div class="mt-4 flex flex-wrap gap-2"><code class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-800">delivery.logistics_received</code><code class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800">delivery.delivered</code></div>
                    <pre class="mt-5 overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs leading-6 text-slate-100"><code>{
  "event_id": "unique-event-id",
  "event_type": "delivery.logistics_received",
  "dr_reference": "0035470",
  "occurred_at": "2026-10-13T14:30:00+08:00",
  "sequence": 1
}</code></pre>
                    <p class="mt-4 text-xs leading-5 text-slate-500">DR numbers stay strings. MMC resolves one project/school receipt scope; a scope may include multiple delivery rows and keystages. Logistics milestones do not change MMC delivery, package, warehouse, acceptance, or billing states.</p>
                    <a href="{{ route('logistics-channel.index', ['tab' => 'integrations']) }}" class="mt-4 inline-flex rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700">Manage signing keys</a>
                </article>
            </section>
        @endif

        @if($tab === 'tracking')
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-end justify-between gap-4"><div><h2 class="text-lg font-black text-slate-900">Verified DR tracking</h2><p class="mt-1 text-xs text-slate-500">One row per integration, project, school, and DR scope.</p></div><span class="text-xs text-slate-500">{{ number_format($trackingRows['summary']['pending_updates']) }} scopes have no Logistics milestone</span></div>
                <form method="GET" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6"><input type="hidden" name="tab" value="tracking">
                    <select name="project_id" class="rounded-xl border-slate-200 text-sm"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->project_id }}" @selected(($filters['project_id'] ?? '') == $project->project_id)>{{ $project->project_name ?: 'Project '.$project->project_id }}</option>@endforeach</select>
                    <input name="dr_reference" value="{{ $filters['dr_reference'] ?? '' }}" placeholder="DR#" class="rounded-xl border-slate-200 font-mono text-sm">
                    <input name="school" value="{{ $filters['school'] ?? '' }}" placeholder="School" class="rounded-xl border-slate-200 text-sm">
                    <select name="status" class="rounded-xl border-slate-200 text-sm"><option value="">All Logistics statuses</option><option value="logistics_received" @selected(($filters['status'] ?? '') === 'logistics_received')>Logistics Received</option><option value="delivered" @selected(($filters['status'] ?? '') === 'delivered')>Logistics Delivered</option></select>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="From date" class="rounded-xl border-slate-200 text-sm"><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="To date" class="rounded-xl border-slate-200 text-sm">
                    <div class="flex gap-2"><button class="rounded-xl bg-slate-950 px-4 py-2 text-xs font-bold text-white">Filter</button><a href="{{ route('logistics-channel.index', ['tab' => 'tracking']) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600">Clear</a></div>
                </form>
                <div class="mt-5 overflow-x-auto rounded-xl border border-slate-100"><table class="min-w-full text-left text-xs"><thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">DR#</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">School</th><th class="px-4 py-3">Logistics status</th><th class="px-4 py-3">Updated</th><th class="px-4 py-3">MMC receiving</th><th class="px-4 py-3">Reconciliation</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @forelse($trackingRows['rows'] as $row)<tr><td class="px-4 py-3 font-mono font-bold">{{ $row['dr_reference'] }}</td><td class="px-4 py-3">{{ $row['project_name'] ?? 'Project '.$row['project_id'] }}</td><td class="px-4 py-3">{{ $row['school_name'] ?: $row['school_id'] }}</td><td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 font-bold {{ $row['logistics_status'] === 'delivered' ? 'bg-emerald-50 text-emerald-800' : 'bg-blue-50 text-blue-800' }}">{{ $row['logistics_status'] === 'delivered' ? 'Logistics Delivered' : 'Logistics Received' }}</span></td><td class="whitespace-nowrap px-4 py-3">{{ \Illuminate\Support\Carbon::parse($row['last_logistics_update'])->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</td><td class="px-4 py-3">{{ str_replace('_', ' ', ucfirst($row['mmc_receiving_status'])) }} · {{ $row['mmc_received_package_count'] }}/{{ $row['mmc_expected_package_count'] }}</td><td class="px-4 py-3">{{ str_replace('_', ' ', ucfirst($row['reconciliation_status'])) }}</td></tr>
                    @empty<tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No Logistics tracking rows match these filters. Scopes without webhook events do not receive an invented status.</td></tr>@endforelse
                </tbody></table></div><div class="mt-4">{{ $trackingRows['rows']->links() }}</div>
            </section>
        @endif

        @if(in_array($tab, ['events', 'reconciliation'], true))
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div><h2 class="text-lg font-black text-slate-900">{{ $tab === 'events' ? 'Webhook event history' : 'Reconciliation queue' }}</h2><p class="mt-1 text-xs text-slate-500">Payloads and signatures are intentionally excluded from this view.</p></div>
                <form method="GET" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6"><input type="hidden" name="tab" value="{{ $tab }}"><input name="integration_id" value="{{ $filters['integration_id'] ?? '' }}" placeholder="Integration ID" class="rounded-xl border-slate-200 font-mono text-xs"><input name="event_id" value="{{ $filters['event_id'] ?? '' }}" placeholder="Event ID" class="rounded-xl border-slate-200 font-mono text-xs"><select name="project_id" class="rounded-xl border-slate-200 text-sm"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->project_id }}" @selected(($filters['project_id'] ?? '') == $project->project_id)>{{ $project->project_name ?: 'Project '.$project->project_id }}</option>@endforeach</select><input name="dr_reference" value="{{ $filters['dr_reference'] ?? '' }}" placeholder="DR#" class="rounded-xl border-slate-200 font-mono text-sm"><input name="status" value="{{ $filters['status'] ?? '' }}" placeholder="Processing / resolution / reconciliation" class="rounded-xl border-slate-200 text-sm"><input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="From date" class="rounded-xl border-slate-200 text-sm"><input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="To date" class="rounded-xl border-slate-200 text-sm"><button class="rounded-xl bg-slate-950 px-4 py-2 text-xs font-bold text-white">Filter events</button></form>
                <div class="mt-5 overflow-x-auto rounded-xl border border-slate-100"><table class="min-w-full text-left text-xs"><thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-3">Event / Integration</th><th class="px-3 py-3">DR / Project / School</th><th class="px-3 py-3">Event</th><th class="px-3 py-3">Sequence / occurred</th><th class="px-3 py-3">Processing</th><th class="px-3 py-3">Resolution</th><th class="px-3 py-3">Reconciliation</th><th class="px-3 py-3">Attempts / error category</th><th class="px-3 py-3">Action</th></tr></thead><tbody class="divide-y divide-slate-100">
                    @forelse($eventRows as $row)<tr><td class="max-w-56 px-3 py-3"><span class="block break-all font-mono font-bold text-slate-800">{{ $row->external_event_id }}</span><span class="mt-1 block text-slate-500">{{ $row->integration_id }}</span></td><td class="px-3 py-3"><span class="font-mono font-bold">{{ $row->dr_reference }}</span><span class="mt-1 block text-slate-600">{{ $row->project_name ?? ($row->resolved_project_id ? 'Project '.$row->resolved_project_id : 'Unresolved') }}</span><span class="block text-slate-500">{{ $row->school_name ?? $row->resolved_school_id ?? '—' }}</span></td><td class="whitespace-nowrap px-3 py-3">{{ $row->event_type }}</td><td class="whitespace-nowrap px-3 py-3"><span>Seq {{ $row->event_sequence ?? '—' }}</span><span class="mt-1 block text-slate-500">{{ $row->event_occurred_at }}</span></td><td class="px-3 py-3">{{ $row->processing_status }}</td><td class="px-3 py-3">{{ $row->resolution_status }}</td><td class="px-3 py-3">{{ $row->reconciliation_status }}</td><td class="px-3 py-3"><span>{{ $row->processing_attempts }} tries</span><span class="mt-1 block text-slate-500">{{ $row->error_details ? class_basename($row->error_details) : '—' }}</span></td><td class="px-3 py-3">@if($row->processing_status === 'failed')<form method="POST" action="{{ route('logistics-channel.events.retry', $row->id) }}" onsubmit="return confirm('Requeue this stored event for validation? Its original payload will be used unchanged.')">@csrf<button class="whitespace-nowrap rounded-lg border border-amber-300 px-3 py-1.5 font-bold text-amber-800">Retry stored event</button></form>@else<span class="text-slate-400">—</span>@endif</td></tr>
                    @empty<tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">No events match these filters.</td></tr>@endforelse
                </tbody></table></div><div class="mt-4">{{ $eventRows->links() }}</div>
            </section>
        @endif

        @if($tab === 'audit')
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-lg font-black text-slate-900">Administrative audit history</h2><p class="mt-1 text-xs text-slate-500">Credential values, signatures, and payloads are never recorded.</p><div class="mt-5 overflow-x-auto"><table class="min-w-full text-left text-xs"><thead class="bg-slate-50 uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">When</th><th class="px-4 py-3">Integration</th><th class="px-4 py-3">Action</th><th class="px-4 py-3">Actor</th><th class="px-4 py-3">Target</th><th class="px-4 py-3">Details</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($auditRows as $audit)<tr><td class="whitespace-nowrap px-4 py-3">{{ $audit->created_at }}</td><td class="px-4 py-3 font-mono">{{ $audit->integration_reference ?? '—' }}</td><td class="px-4 py-3 font-bold">{{ str_replace('_', ' ', $audit->action) }}</td><td class="px-4 py-3">{{ $audit->actor_user_id ?? 'System' }}</td><td class="px-4 py-3">{{ $audit->target_type }} {{ $audit->target_reference }}</td><td class="max-w-72 break-words px-4 py-3 text-slate-500">{{ $audit->details ? json_encode(json_decode($audit->details, true), JSON_UNESCAPED_SLASHES) : '—' }}</td></tr>@empty<tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No administrative actions recorded.</td></tr>@endforelse</tbody></table></div><div class="mt-4">{{ $auditRows->links() }}</div></section>
        @endif
    </main>

    <script>
        (() => {
            const form = document.getElementById('integration-form');
            const projects = document.getElementById('authorized-projects');
            const schools = document.getElementById('authorized-schools');
            const allSchools = document.getElementById('all-schools');
            if (!form || !projects || !schools) return;
            const endpoint = form.dataset.schoolsUrl;
            const initial = new Set(JSON.parse(form.dataset.selectedSchools || '[]'));
            let requestController;
            let schoolOptionsLoading = false;
            let schoolOptionsFailed = false;
            schools.addEventListener('change', () => {
                if (Array.from(schools.selectedOptions).length) allSchools.checked = false;
            });
            allSchools.addEventListener('change', () => {
                if (allSchools.checked) Array.from(schools.options).forEach(option => { option.selected = false; });
            });
            form.addEventListener('submit', event => {
                if (schoolOptionsLoading || schoolOptionsFailed) {
                    event.preventDefault();
                    window.alert('Wait for school access options to load successfully before saving this scope.');
                }
            });
            projects.addEventListener('change', async () => {
                const projectIds = Array.from(projects.selectedOptions, option => option.value);
                requestController?.abort();
                requestController = undefined;
                schoolOptionsLoading = false;
                schoolOptionsFailed = false;
                schools.replaceChildren();
                if (!projectIds.length) return;
                const controller = new AbortController();
                requestController = controller;
                schoolOptionsLoading = true;
                const url = new URL(endpoint, window.location.origin);
                projectIds.forEach(id => url.searchParams.append('project_ids[]', id));
                try {
                    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal });
                    if (!response.ok) throw new Error('School options could not be loaded.');
                    const rows = await response.json();
                    if (requestController !== controller) return;
                    for (const school of rows) {
                        const encoded = btoa(String(school.school_id)).replaceAll('+', '-').replaceAll('/', '_').replace(/=+$/, '');
                        const token = `${school.project_id}:${encoded}`;
                        const option = new Option(`${school.school_name || school.school_id} · ${school.school_id} · Project ${school.project_id}`, token, false, initial.has(token));
                        schools.add(option);
                    }
                    schoolOptionsLoading = false;
                } catch (error) {
                    if (requestController !== controller) return;
                    schoolOptionsLoading = false;
                    if (error.name !== 'AbortError') {
                        schoolOptionsFailed = true;
                        schools.add(new Option(error.message, '', true, true));
                    }
                }
            });
        })();
    </script>
</x-project_app-layout>
