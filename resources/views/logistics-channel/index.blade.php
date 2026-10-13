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

        // Small attention counters shown on the tabs that need action.
        $tabAlerts = [
            'events' => (int) ($metrics['failed_events'] ?? 0),
            'reconciliation' => (int) ($metrics['reconciliation_required'] ?? 0),
        ];

        $cards = [
            ['Total integrations', $metrics['total_integrations'], 'text-slate-900', null],
            ['Active integrations', $metrics['active_integrations'], 'text-emerald-700', null],
            ['Authorized projects', $metrics['authorized_projects'], 'text-blue-700', null],
            ['Webhook events received', $metrics['received_events'], 'text-slate-900', 'events'],
            ['Synchronized DR scopes', $metrics['synchronized_drs'], 'text-blue-700', 'tracking'],
            ['Pending updates', $metrics['pending_updates'], 'text-amber-700', 'tracking'],
            ['Failed events', $metrics['failed_events'], 'text-rose-700', 'events'],
            ['Reconciliation required', $metrics['reconciliation_required'], 'text-orange-700', 'reconciliation'],
        ];

        $fmt = function ($value, $withTime = true) {
            if (! $value) {
                return '—';
            }
            try {
                return \Illuminate\Support\Carbon::parse($value)->timezone(config('app.timezone'))->format($withTime ? 'M j, Y g:i A' : 'M j, Y');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        };

        $humanize = fn ($value) => $value === null || $value === '' ? '—' : ucfirst(str_replace('_', ' ', (string) $value));

        // One place that decides how any processing/resolution/reconciliation status is coloured.
        $pill = function ($value) {
            $v = strtolower((string) $value);
            return match (true) {
                $v === '' => 'bg-slate-100 text-slate-500',
                str_contains($v, 'fail'), str_contains($v, 'reject'), str_contains($v, 'error') => 'bg-rose-50 text-rose-800',
                str_contains($v, 'required'), str_contains($v, 'review'), str_contains($v, 'unresolved'), str_contains($v, 'conflict') => 'bg-orange-50 text-orange-800',
                str_contains($v, 'pending'), str_contains($v, 'queue'), str_contains($v, 'process') && ! str_contains($v, 'processed') => 'bg-amber-50 text-amber-800',
                str_contains($v, 'success'), str_contains($v, 'processed'), str_contains($v, 'resolved'), str_contains($v, 'complete'), str_contains($v, 'matched'), str_contains($v, 'clear') => 'bg-emerald-50 text-emerald-800',
                default => 'bg-slate-100 text-slate-700',
            };
        };

        $workerLabels = [
            'recent' => 'recent activity',
            'backlog' => 'backlog needs attention',
            'idle' => 'idle / no recent activity',
            'unavailable' => 'health unavailable',
        ];
        $workerTone = [
            'recent' => 'bg-emerald-400/15 text-emerald-200',
            'backlog' => 'bg-rose-400/15 text-rose-200',
        ];
        $workerKey = $metrics['worker_activity'] ?? 'unavailable';

        $field = 'w-full rounded-xl border-slate-200 text-sm focus:border-cyan-600 focus:ring-cyan-600';
        $btnPrimary = 'rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-600';
        $btnGhost = 'rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-600';
        $th = 'px-3 py-3 font-bold';
    @endphp

    <main class="space-y-6" id="logistics-channel">
        <header class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-lg">
            <div class="grid gap-6 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-cyan-300">MMC · External delivery partner</p>
                    <h1 class="mt-3 text-3xl font-black tracking-tight">Logistics Channel</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Manage partner credentials and project access, then follow each verified DR from Logistics custody through delivery.</p>
                </div>
                <div class="flex flex-wrap gap-3 text-xs" role="group" aria-label="System health">
                    <span class="rounded-full border border-white/15 bg-white/5 px-3 py-2">Queue {{ ($metrics['queue_depth'] ?? null) === null ? 'unavailable' : number_format($metrics['queue_depth']).' waiting' }}</span>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-2 {{ $workerTone[$workerKey] ?? 'bg-amber-300/15 text-amber-100' }}">
                        <span aria-hidden="true" class="h-2 w-2 rounded-full {{ $workerKey === 'recent' ? 'bg-emerald-300' : ($workerKey === 'backlog' ? 'bg-rose-300' : 'bg-amber-200') }}"></span>
                        Worker {{ $workerLabels[$workerKey] ?? $workerLabels['unavailable'] }}
                    </span>
                </div>
            </div>
            <nav class="flex gap-1 overflow-x-auto border-t border-white/10 px-4 py-3" aria-label="Logistics Channel sections">
                @foreach($tabs as $key => $label)
                    <a href="{{ route('logistics-channel.index', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif
                       class="inline-flex items-center gap-2 whitespace-nowrap rounded-xl px-3 py-2 text-xs font-bold transition motion-reduce:transition-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300 {{ $tab === $key ? 'bg-cyan-300 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                        {{ $label }}
                        @if(($tabAlerts[$key] ?? 0) > 0)
                            <span class="rounded-full bg-rose-500 px-1.5 py-0.5 text-[10px] font-black leading-none text-white"><span class="sr-only">{{ $tabAlerts[$key] }} need attention</span><span aria-hidden="true">{{ $tabAlerts[$key] > 99 ? '99+' : $tabAlerts[$key] }}</span></span>
                        @endif
                    </a>
                @endforeach
            </nav>
        </header>

        @if(session('status'))
            <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-bold">{{ $errors->count() > 1 ? 'Fix these '.$errors->count().' issues to continue:' : 'Fix this issue to continue:' }}</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- OVERVIEW --}}
        @if($tab === 'overview')
            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Logistics Channel summary">
                @foreach($cards as [$label, $value, $tone, $target])
                    <article class="relative rounded-2xl border border-slate-200 bg-white p-5 shadow-sm {{ $target ? 'transition hover:border-slate-300 hover:shadow motion-reduce:transition-none' : '' }}">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="mt-3 text-3xl font-black tabular-nums {{ $tone }}">{{ number_format($value ?? 0) }}</p>
                        @if($target)
                            <a href="{{ route('logistics-channel.index', ['tab' => $target]) }}" class="absolute inset-0 rounded-2xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-cyan-600"><span class="sr-only">View {{ strtolower($label) }}</span></a>
                        @endif
                    </article>
                @endforeach
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-extrabold text-slate-900">Synchronization</h2>
                    <dl class="mt-4 grid grid-cols-[1fr_auto] gap-x-4 gap-y-3 text-sm">
                        <dt class="text-slate-500">Last successful DR update</dt>
                        <dd class="text-right font-semibold text-slate-800">{{ ($metrics['last_successful_sync'] ?? null) ? $fmt($metrics['last_successful_sync']) : 'No successful sync yet' }}</dd>
                        <dt class="text-slate-500">Waiting webhook jobs</dt>
                        <dd class="text-right font-semibold text-slate-800">{{ ($metrics['queue_depth'] ?? null) === null ? 'Queue table unavailable' : number_format($metrics['queue_depth']).' waiting · '.number_format($metrics['reserved_queue_jobs'] ?? 0).' reserved' }}</dd>
                        <dt class="text-slate-500">Worker signal</dt>
                        <dd class="text-right font-semibold text-slate-800">{{ $workerKey === 'recent' ? 'Recent successful work' : ($workerKey === 'backlog' ? 'Backlog with no recent success' : 'No direct process heartbeat is configured') }}</dd>
                    </dl>
                    <p class="mt-4 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-500">Worker activity is inferred from successful webhook processing and queue depth. This page does not claim a live process heartbeat.</p>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-extrabold text-slate-900">Current delivery milestones</h2>
                            <p class="mt-1 text-xs text-slate-500">Logistics status remains separate from MMC receiving and acceptance.</p>
                        </div>
                        <a href="{{ route('logistics-channel.index', ['tab' => 'tracking']) }}" class="shrink-0 {{ str_replace('px-4 py-2.5 text-sm', 'px-3 py-2 text-xs', $btnGhost) }}">Open tracking</a>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a href="{{ route('logistics-channel.index', ['tab' => 'tracking', 'status' => 'logistics_received']) }}" class="rounded-xl bg-blue-50 px-4 py-3 text-sm font-bold text-blue-800 hover:bg-blue-100">Logistics Received: {{ number_format($trackingRows['summary']['logistics_received'] ?? 0) }}</a>
                        <a href="{{ route('logistics-channel.index', ['tab' => 'tracking', 'status' => 'delivered']) }}" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800 hover:bg-emerald-100">Logistics Delivered: {{ number_format($trackingRows['summary']['logistics_delivered'] ?? 0) }}</a>
                    </div>
                    <p class="mt-4 text-xs leading-5 text-slate-500">Pending counts represent distinct MMC DR scopes with no current Logistics milestone. No status is created when a webhook has not arrived.</p>
                </article>
            </section>
        @endif

        {{-- INTEGRATIONS --}}
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
                            <input id="integration-name" name="name" required maxlength="120" value="{{ old('name', $editing->name ?? '') }}" class="{{ $field }}" @error('name') aria-invalid="true" @enderror>
                            @error('name')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        @unless($editing)
                            <div>
                                <label for="integration-id" class="mb-1 block text-xs font-bold text-slate-600">Integration ID</label>
                                <input id="integration-id" name="integration_id" required maxlength="64" pattern="[A-Za-z0-9._-]+" aria-describedby="integration-id-help" value="{{ old('integration_id') }}" placeholder="logistics-partner-east" class="{{ $field }} font-mono" @error('integration_id') aria-invalid="true" @enderror>
                                <p id="integration-id-help" class="mt-1 text-[11px] text-slate-500">Letters, numbers, dots, dashes, and underscores. This cannot be changed after you create the integration.</p>
                                @error('integration_id')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                            </div>
                        @endunless

                        <div>
                            <label for="webhook-url" class="mb-1 block text-xs font-bold text-slate-600">Webhook endpoint URL</label>
                            <input id="webhook-url" name="webhook_url" type="url" value="{{ old('webhook_url', $editing->webhook_url ?? $webhookUrl) }}" class="{{ $field }}" @error('webhook_url') aria-invalid="true" @enderror>
                            @error('webhook_url')<p class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="integration-status" class="mb-1 block text-xs font-bold text-slate-600">Integration status</label>
                            <select id="integration-status" name="status" class="{{ $field }}">
                                <option value="active" @selected(old('status', $editing->status ?? 'active') === 'active')>Active</option>
                                <option value="disabled" @selected(old('status', $editing->status ?? 'active') === 'disabled')>Disabled</option>
                            </select>
                        </div>

                        <fieldset>
                            <legend class="mb-2 text-xs font-bold text-slate-600">Enabled event types</legend>
                            @foreach(['delivery.logistics_received' => 'Logistics Received', 'delivery.delivered' => 'Logistics Delivered'] as $eventType => $eventLabel)
                                <label class="flex items-center gap-2 py-1.5 text-sm text-slate-700">
                                    <input type="checkbox" name="enabled_event_types[]" value="{{ $eventType }}" @checked(in_array($eventType, old('enabled_event_types', $editing->enabled_event_types ?? ['delivery.logistics_received', 'delivery.delivered']), true)) class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-600">
                                    <span>{{ $eventLabel }} <code class="ml-1 text-[11px] text-slate-400">{{ $eventType }}</code></span>
                                </label>
                            @endforeach
                        </fieldset>

                        <div>
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <label for="authorized-projects" class="block text-xs font-bold text-slate-600">Authorized projects</label>
                                <span id="project-count" class="text-[11px] text-slate-500" aria-live="polite"></span>
                            </div>
                            <select id="authorized-projects" name="project_ids[]" multiple required size="6" aria-describedby="projects-help" class="{{ $field }}">
                                @foreach($projects as $project)
                                    <option value="{{ $project->project_id }}" @selected(in_array((int) $project->project_id, array_map('intval', old('project_ids', $selectedProjects)), true))>{{ $project->project_name ?: 'Project '.$project->project_id }} · {{ $project->project_id }}</option>
                                @endforeach
                            </select>
                            <p id="projects-help" class="mt-1 text-[11px] text-slate-500">Use Ctrl or Command to select multiple projects. A project is never added implicitly.</p>
                        </div>

                        <div>
                            <div class="mb-1 flex items-center justify-between gap-2">
                                <label for="authorized-schools" class="block text-xs font-bold text-slate-600">Authorized schools <span class="font-normal text-slate-400">(optional)</span></label>
                                <span id="school-status" class="text-[11px] text-slate-500" role="status" aria-live="polite"></span>
                            </div>
                            <select id="authorized-schools" name="school_scopes[]" multiple size="5" aria-describedby="schools-help" class="{{ $field }}">
                                @foreach($schools as $school)
                                    @php($scopeToken = (int) $school->project_id.':'.rtrim(strtr(base64_encode((string) $school->school_id), '+/', '-_'), '='))
                                    <option value="{{ $scopeToken }}" @selected(in_array($scopeToken, old('school_scopes', $editingSchoolTokens), true))>{{ $school->school_name ?: $school->school_id }} · {{ $school->school_id }} · Project {{ $school->project_id }}</option>
                                @endforeach
                            </select>
                            <p id="schools-help" class="mt-1 text-[11px] text-slate-500">A selected school is tied to its project. If no schools are selected, explicitly allow all schools below.</p>
                            <label class="mt-3 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs leading-5 text-amber-950">
                                <input id="all-schools" type="checkbox" name="all_schools" value="1" @checked((string) old('all_schools', $allSchools ? '1' : '') === '1') class="mt-0.5 rounded border-amber-400 text-amber-800 focus:ring-amber-700">
                                <span><strong>Allow every school</strong> in the selected projects. Choose this when school-level restriction is not required.</span>
                            </label>
                        </div>

                        <div class="flex flex-wrap gap-2 pt-2">
                            <button type="submit" id="integration-submit" class="{{ $btnPrimary }} disabled:cursor-not-allowed disabled:opacity-60">{{ $editing ? 'Save integration' : 'Create integration and key' }}</button>
                            @if($editing)<a href="{{ route('logistics-channel.index', ['tab' => 'integrations']) }}" class="{{ $btnGhost }}">Cancel</a>@endif
                        </div>
                    </form>

                    @if($editing)
                        <form method="POST" action="{{ route('logistics-channel.integrations.rotate', $editing) }}" class="mt-5 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-5" data-confirm="Create a new signing credential? Current active credentials remain valid for the overlap period.">
                            @csrf
                            <label class="text-xs font-bold text-slate-600">Key overlap period
                                <select name="overlap_days" class="mt-1 block rounded-lg border-slate-200 text-sm">
                                    @foreach([3 => '3 days', 7 => '7 days', 14 => '14 days', 30 => '30 days'] as $days => $daysLabel)
                                        <option value="{{ $days }}" @selected($days === 7)>{{ $daysLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <button class="rounded-xl border border-amber-300 px-4 py-2.5 text-xs font-bold text-amber-800 hover:bg-amber-50">Rotate HMAC key</button>
                        </form>
                    @endif
                </article>

                <div class="space-y-4">
                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <h2 class="text-sm font-extrabold text-slate-900">Managed integrations</h2>
                            <p class="mt-1 text-xs text-slate-500">Secrets are encrypted at rest. Only key IDs and credential state are listed.</p>
                        </div>
                        <ul class="divide-y divide-slate-100">
                            @forelse($integrations as $integration)
                                <li class="space-y-3 p-5 {{ $editing && $editing->id === $integration->id ? 'bg-cyan-50/50' : '' }}">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h3 class="font-bold text-slate-900">{{ $integration->name }}</h3>
                                            <p class="mt-1 font-mono text-xs text-slate-500">{{ $integration->integration_id }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $integration->status === 'active' ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($integration->status) }}</span>
                                            <a href="{{ route('logistics-channel.index', ['tab' => 'integrations', 'edit' => $integration->id]) }}" aria-label="Edit {{ $integration->name }}" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Edit</a>
                                        </div>
                                    </div>
                                    <ul class="flex flex-wrap gap-2" aria-label="Credentials for {{ $integration->name }}">
                                        @forelse($integration->credentials as $credential)
                                            @php($live = $credential->status === 'active' && (! $credential->expires_at || $credential->expires_at->isFuture()))
                                            <li class="flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 px-3 py-2 text-xs">
                                                <code class="font-mono text-slate-700">{{ $credential->key_id }}</code>
                                                <span class="rounded-full px-2 py-0.5 font-bold {{ $live ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ $credential->status }}{{ $credential->expires_at ? ' · expires '.$credential->expires_at->format('M j') : '' }}</span>
                                                <span class="text-slate-500">{{ $credential->last_used_at ? 'Last used '.$credential->last_used_at->timezone(config('app.timezone'))->format('M j, g:i A') : 'Not used yet' }}</span>
                                                @if($credential->status === 'active')
                                                    <form method="POST" action="{{ route('logistics-channel.integrations.credentials.revoke', [$integration, $credential]) }}" data-confirm="Revoke this credential immediately? Signed webhook requests using this key will fail.">
                                                        @csrf<button class="font-bold text-rose-700 hover:underline"><span aria-hidden="true">Revoke</span><span class="sr-only">Revoke key {{ $credential->key_id }}</span></button>
                                                    </form>
                                                @endif
                                            </li>
                                        @empty
                                            <li class="text-xs text-slate-500">No credentials registered. Rotate the key to create one.</li>
                                        @endforelse
                                    </ul>
                                </li>
                            @empty
                                <li class="p-6 text-sm text-slate-500">No database-managed integrations yet. Add one using the form, or adopt an environment integration if one is listed below.</li>
                            @endforelse
                        </ul>
                        @if($integrations->hasPages())<div class="border-t border-slate-100 p-4">{{ $integrations->links() }}</div>@endif
                    </article>

                    @if($legacyIntegrations->isNotEmpty())
                        <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                            <h2 class="text-sm font-extrabold text-amber-950">Environment-managed integrations</h2>
                            <p class="mt-1 text-xs leading-5 text-amber-900">These continue using their current HMAC secrets and configured scopes. Adoption encrypts the existing secret in MMC without showing or rotating it.</p>
                            @foreach($legacyIntegrations as $legacy)
                                <form method="POST" action="{{ route('logistics-channel.integrations.adopt') }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-xl border border-amber-200 bg-white p-4" data-confirm="Adopt this environment integration into encrypted database storage without changing its secret or project scope?">
                                    @csrf<input type="hidden" name="integration_id" value="{{ $legacy['integration_id'] }}">
                                    <div class="min-w-48 flex-1"><p class="mb-1 text-[11px] font-bold text-slate-600">Integration ID</p><p class="font-mono text-sm text-slate-900">{{ $legacy['integration_id'] }}</p></div>
                                    <label class="min-w-40 flex-1 text-[11px] font-bold text-slate-600">Display name<input name="name" required maxlength="120" value="{{ $legacy['name'] }}" class="mt-1 w-full rounded-lg border-slate-200 text-sm font-normal"></label>
                                    <p class="w-full text-[11px] text-slate-500">Authorized project IDs: {{ $legacy['project_ids'] ? implode(', ', $legacy['project_ids']) : 'No project-wide scope (approved DR mappings only)' }}</p>
                                    <button class="rounded-lg bg-amber-900 px-3 py-2 text-xs font-bold text-white hover:bg-amber-800">Adopt without rotation</button>
                                </form>
                            @endforeach
                        </article>
                    @endif
                </div>
            </section>
        @endif

        {{-- API & WEBHOOKS --}}
        @if($tab === 'api')
            <section class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-cyan-800">Existing receiver contract</p>
                    <h2 class="mt-2 text-xl font-black text-slate-950">HMAC-SHA256 delivery events</h2>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div>
                            <dt class="text-xs font-bold text-slate-500">Endpoint</dt>
                            <dd class="mt-1 flex items-stretch gap-2">
                                <code id="webhook-endpoint" class="min-w-0 flex-1 break-all rounded-lg bg-slate-950 p-3 font-mono text-xs text-cyan-100">POST {{ $webhookUrl }}</code>
                                <button type="button" data-copy="{{ $webhookUrl }}" class="shrink-0 rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-700 hover:bg-slate-50">Copy URL</button>
                            </dd>
                        </div>
                        <div><dt class="text-xs font-bold text-slate-500">Headers</dt><dd class="mt-1 font-mono text-xs leading-6 text-slate-700">X-Integration-Key-Id<br>X-Webhook-Timestamp<br>X-Webhook-Signature: v1=&lt;hex&gt;</dd></div>
                        <div><dt class="text-xs font-bold text-slate-500">Signed bytes</dt><dd class="mt-1 rounded-lg bg-slate-50 p-3 font-mono text-xs text-slate-700">key_id + LF + timestamp + LF + raw_body</dd></div>
                        <div><dt class="text-xs font-bold text-slate-500">Timestamp window</dt><dd class="mt-1 text-slate-700">300 seconds. Retry with a fresh timestamp and the same event ID/body.</dd></div>
                    </dl>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-sm font-extrabold text-slate-900">Events and scope</h2>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        <li><code class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-800">delivery.logistics_received</code></li>
                        <li><code class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800">delivery.delivered</code></li>
                    </ul>
                    <div class="mt-5 flex items-center justify-between">
                        <p class="text-xs font-bold text-slate-500">Example payload</p>
                        <button type="button" data-copy-target="payload-example" class="rounded-lg border border-slate-200 px-3 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">Copy payload</button>
                    </div>
                    <pre class="mt-2 overflow-x-auto rounded-xl bg-slate-950 p-4 text-xs leading-6 text-slate-100" tabindex="0"><code id="payload-example">{
  "event_id": "unique-event-id",
  "event_type": "delivery.logistics_received",
  "dr_reference": "0035470",
  "occurred_at": "{{ now()->timezone(config('app.timezone'))->toIso8601String() }}",
  "sequence": 1
}</code></pre>
                    <p class="mt-4 text-xs leading-5 text-slate-500">DR numbers stay strings. MMC resolves one project/school receipt scope; a scope may include multiple delivery rows and keystages. Logistics milestones do not change MMC delivery, package, warehouse, acceptance, or billing states.</p>
                    <a href="{{ route('logistics-channel.index', ['tab' => 'integrations']) }}" class="mt-4 inline-flex {{ str_replace('px-4 py-2.5 text-sm', 'px-3 py-2 text-xs', $btnGhost) }}">Manage signing keys</a>
                </article>
            </section>
        @endif

        {{-- TRACKING --}}
        @if($tab === 'tracking')
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Verified DR tracking</h2>
                        <p class="mt-1 text-xs text-slate-500">One row per integration, project, school, and DR scope.</p>
                    </div>
                    <span class="text-xs text-slate-500">{{ number_format($trackingRows['summary']['pending_updates'] ?? 0) }} scopes have no Logistics milestone</span>
                </div>

                <form method="GET" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6" role="search" aria-label="Filter DR tracking">
                    <input type="hidden" name="tab" value="tracking">
                    <div><label for="t-project" class="sr-only">Project</label><select id="t-project" name="project_id" class="{{ $field }}"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->project_id }}" @selected(($filters['project_id'] ?? '') == $project->project_id)>{{ $project->project_name ?: 'Project '.$project->project_id }}</option>@endforeach</select></div>
                    <div><label for="t-dr" class="sr-only">DR number</label><input id="t-dr" name="dr_reference" value="{{ $filters['dr_reference'] ?? '' }}" placeholder="DR#" class="{{ $field }} font-mono"></div>
                    <div><label for="t-school" class="sr-only">School</label><input id="t-school" name="school" value="{{ $filters['school'] ?? '' }}" placeholder="School" class="{{ $field }}"></div>
                    <div><label for="t-status" class="sr-only">Logistics status</label><select id="t-status" name="status" class="{{ $field }}"><option value="">All Logistics statuses</option><option value="logistics_received" @selected(($filters['status'] ?? '') === 'logistics_received')>Logistics Received</option><option value="delivered" @selected(($filters['status'] ?? '') === 'delivered')>Logistics Delivered</option></select></div>
                    <div><label for="t-from" class="sr-only">From date</label><input id="t-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="{{ $field }}"></div>
                    <div><label for="t-to" class="sr-only">To date</label><input id="t-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="{{ $field }}"></div>
                    <div class="flex gap-2 sm:col-span-2 xl:col-span-6">
                        <button class="{{ $btnPrimary }} !py-2 text-xs">Apply filters</button>
                        <a href="{{ route('logistics-channel.index', ['tab' => 'tracking']) }}" class="{{ $btnGhost }} !py-2 text-xs">Clear filters</a>
                    </div>
                </form>

                <div class="mt-5 overflow-x-auto rounded-xl border border-slate-100">
                    <table class="min-w-full text-left text-xs">
                        <caption class="sr-only">Verified DR tracking, one row per integration, project, school, and DR scope</caption>
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                            <tr>
                                @foreach(['DR#', 'Project', 'School', 'Logistics status', 'Updated', 'MMC receiving', 'Reconciliation'] as $heading)<th scope="col" class="{{ $th }}">{{ $heading }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($trackingRows['rows'] as $row)
                                <tr class="hover:bg-slate-50/60">
                                    <th scope="row" class="px-3 py-3 font-mono font-bold">{{ $row['dr_reference'] }}</th>
                                    <td class="px-3 py-3">{{ $row['project_name'] ?? 'Project '.$row['project_id'] }}</td>
                                    <td class="px-3 py-3">{{ $row['school_name'] ?: $row['school_id'] }}</td>
                                    <td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 font-bold {{ $row['logistics_status'] === 'delivered' ? 'bg-emerald-50 text-emerald-800' : 'bg-blue-50 text-blue-800' }}">{{ $row['logistics_status'] === 'delivered' ? 'Logistics Delivered' : 'Logistics Received' }}</span></td>
                                    <td class="whitespace-nowrap px-3 py-3"><time datetime="{{ \Illuminate\Support\Carbon::parse($row['last_logistics_update'])->toIso8601String() }}">{{ $fmt($row['last_logistics_update']) }}</time></td>
                                    <td class="whitespace-nowrap px-3 py-3">{{ $humanize($row['mmc_receiving_status']) }} · <span class="tabular-nums">{{ $row['mmc_received_package_count'] }}/{{ $row['mmc_expected_package_count'] }}</span> packages</td>
                                    <td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 font-bold {{ $pill($row['reconciliation_status']) }}">{{ $humanize($row['reconciliation_status']) }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No Logistics tracking rows match these filters. Scopes without webhook events do not receive an invented status. Clear the filters to see every scope.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $trackingRows['rows']->withQueryString()->links() }}</div>
            </section>
        @endif

        {{-- EVENTS & RECONCILIATION --}}
        @if(in_array($tab, ['events', 'reconciliation'], true))
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h2 class="text-lg font-black text-slate-900">{{ $tab === 'events' ? 'Webhook event history' : 'Reconciliation queue' }}</h2>
                    <p class="mt-1 text-xs text-slate-500">Payloads and signatures are intentionally excluded from this view.</p>
                </div>

                <form method="GET" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6" role="search" aria-label="Filter events">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div><label for="e-integration" class="sr-only">Integration ID</label><input id="e-integration" name="integration_id" value="{{ $filters['integration_id'] ?? '' }}" placeholder="Integration ID" class="{{ $field }} font-mono"></div>
                    <div><label for="e-event" class="sr-only">Event ID</label><input id="e-event" name="event_id" value="{{ $filters['event_id'] ?? '' }}" placeholder="Event ID" class="{{ $field }} font-mono"></div>
                    <div><label for="e-project" class="sr-only">Project</label><select id="e-project" name="project_id" class="{{ $field }}"><option value="">All projects</option>@foreach($projects as $project)<option value="{{ $project->project_id }}" @selected(($filters['project_id'] ?? '') == $project->project_id)>{{ $project->project_name ?: 'Project '.$project->project_id }}</option>@endforeach</select></div>
                    <div><label for="e-dr" class="sr-only">DR number</label><input id="e-dr" name="dr_reference" value="{{ $filters['dr_reference'] ?? '' }}" placeholder="DR#" class="{{ $field }} font-mono"></div>
                    <div><label for="e-status" class="sr-only">Status</label><input id="e-status" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="Status, e.g. failed" class="{{ $field }}"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label for="e-from" class="sr-only">From date</label><input id="e-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="{{ $field }}"></div>
                        <div><label for="e-to" class="sr-only">To date</label><input id="e-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="{{ $field }}"></div>
                    </div>
                    <div class="flex gap-2 sm:col-span-2 xl:col-span-6">
                        <button class="{{ $btnPrimary }} !py-2 text-xs">Apply filters</button>
                        <a href="{{ route('logistics-channel.index', ['tab' => $tab]) }}" class="{{ $btnGhost }} !py-2 text-xs">Clear filters</a>
                    </div>
                </form>

                <div class="mt-5 overflow-x-auto rounded-xl border border-slate-100">
                    <table class="min-w-full text-left text-xs">
                        <caption class="sr-only">{{ $tab === 'events' ? 'Webhook events received from integrations' : 'Events that need reconciliation' }}</caption>
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                            <tr>
                                @foreach(['Event / Integration', 'DR / Project / School', 'Event', 'Sequence / occurred', 'Processing', 'Resolution', 'Reconciliation', 'Attempts / error category', 'Action'] as $heading)<th scope="col" class="{{ $th }}">{{ $heading }}</th>@endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($eventRows as $row)
                                <tr class="align-top hover:bg-slate-50/60">
                                    <th scope="row" class="max-w-56 px-3 py-3 text-left font-normal">
                                        <span class="block break-all font-mono font-bold text-slate-800">{{ $row->external_event_id }}</span>
                                        <span class="mt-1 block text-slate-500">{{ $row->integration_id }}</span>
                                    </th>
                                    <td class="px-3 py-3">
                                        <span class="font-mono font-bold">{{ $row->dr_reference }}</span>
                                        <span class="mt-1 block text-slate-600">{{ $row->project_name ?? ($row->resolved_project_id ? 'Project '.$row->resolved_project_id : 'Unresolved') }}</span>
                                        <span class="block text-slate-500">{{ $row->school_name ?? $row->resolved_school_id ?? '—' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 font-mono">{{ $row->event_type }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <span>Seq {{ $row->event_sequence ?? '—' }}</span>
                                        <span class="mt-1 block text-slate-500">{{ $fmt($row->event_occurred_at) }}</span>
                                    </td>
                                    <td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 font-bold {{ $pill($row->processing_status) }}">{{ $humanize($row->processing_status) }}</span></td>
                                    <td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 font-bold {{ $pill($row->resolution_status) }}">{{ $humanize($row->resolution_status) }}</span></td>
                                    <td class="px-3 py-3"><span class="rounded-full px-2.5 py-1 font-bold {{ $pill($row->reconciliation_status) }}">{{ $humanize($row->reconciliation_status) }}</span></td>
                                    <td class="px-3 py-3">
                                        <span class="tabular-nums">{{ $row->processing_attempts }} {{ \Illuminate\Support\Str::plural('try', $row->processing_attempts) }}</span>
                                        <span class="mt-1 block text-slate-500">{{ $row->error_details ? class_basename($row->error_details) : '—' }}</span>
                                    </td>
                                    <td class="px-3 py-3">
                                        @if($row->processing_status === 'failed')
                                            <form method="POST" action="{{ route('logistics-channel.events.retry', $row->id) }}" data-confirm="Requeue this stored event for validation? Its original payload will be used unchanged.">
                                                @csrf<button class="whitespace-nowrap rounded-lg border border-amber-300 px-3 py-1.5 font-bold text-amber-800 hover:bg-amber-50">Retry event<span class="sr-only"> {{ $row->external_event_id }}</span></button>
                                            </form>
                                        @else
                                            <span class="text-slate-400" aria-label="No action available">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="px-4 py-10 text-center text-slate-500">{{ $tab === 'events' ? 'No events match these filters. Clear the filters, or wait for the next partner webhook.' : 'Nothing needs reconciliation right now.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $eventRows->withQueryString()->links() }}</div>
            </section>
        @endif

        {{-- AUDIT --}}
        @if($tab === 'audit')
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-slate-900">Administrative audit history</h2>
                <p class="mt-1 text-xs text-slate-500">Credential values, signatures, and payloads are never recorded.</p>
                <div class="mt-5 overflow-x-auto rounded-xl border border-slate-100">
                    <table class="min-w-full text-left text-xs">
                        <caption class="sr-only">Administrative actions on Logistics Channel integrations</caption>
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                            <tr>@foreach(['When', 'Integration', 'Action', 'Actor', 'Target', 'Details'] as $heading)<th scope="col" class="px-4 py-3 font-bold">{{ $heading }}</th>@endforeach</tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($auditRows as $audit)
                                <tr class="align-top hover:bg-slate-50/60">
                                    <td class="whitespace-nowrap px-4 py-3"><time datetime="{{ \Illuminate\Support\Carbon::parse($audit->created_at)->toIso8601String() }}">{{ $fmt($audit->created_at) }}</time></td>
                                    <td class="px-4 py-3 font-mono">{{ $audit->integration_reference ?? '—' }}</td>
                                    <td class="px-4 py-3 font-bold">{{ $humanize($audit->action) }}</td>
                                    <td class="px-4 py-3">{{ $audit->actor_user_id ? 'User '.$audit->actor_user_id : 'System' }}</td>
                                    <td class="px-4 py-3">{{ $audit->target_type }} {{ $audit->target_reference }}</td>
                                    <td class="max-w-72 break-words px-4 py-3 font-mono text-[11px] text-slate-500">{{ $audit->details ? json_encode(json_decode($audit->details, true), JSON_UNESCAPED_SLASHES) : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No administrative actions recorded yet. Creating, editing, or rotating an integration will appear here.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $auditRows->withQueryString()->links() }}</div>
            </section>
        @endif
    </main>

    <script>
        (() => {
            // Confirm dialogs for destructive or sensitive forms (replaces inline onsubmit handlers).
            document.querySelectorAll('form[data-confirm]').forEach(f => {
                f.addEventListener('submit', e => { if (!window.confirm(f.dataset.confirm)) e.preventDefault(); });
            });

            // Prevent double submits on every POST form.
            document.querySelectorAll('#logistics-channel form[method="POST"]').forEach(f => {
                f.addEventListener('submit', e => {
                    if (e.defaultPrevented) return;
                    const btn = f.querySelector('button:not([type="button"])');
                    if (btn) setTimeout(() => { btn.disabled = true; }, 0);
                });
            });

            // Copy helpers.
            const flash = (btn, text) => {
                const original = btn.textContent;
                btn.textContent = text;
                setTimeout(() => { btn.textContent = original; }, 1600);
            };
            const copy = async (btn, value) => {
                try { await navigator.clipboard.writeText(value); flash(btn, 'Copied'); }
                catch { flash(btn, 'Copy failed'); }
            };
            document.querySelectorAll('[data-copy]').forEach(btn => btn.addEventListener('click', () => copy(btn, btn.dataset.copy)));
            document.querySelectorAll('[data-copy-target]').forEach(btn => btn.addEventListener('click', () => {
                const el = document.getElementById(btn.dataset.copyTarget);
                if (el) copy(btn, el.textContent);
            }));

            // Integration form: project/school scope picker.
            const form = document.getElementById('integration-form');
            const projects = document.getElementById('authorized-projects');
            const schools = document.getElementById('authorized-schools');
            const allSchools = document.getElementById('all-schools');
            if (!form || !projects || !schools) return;

            const submit = document.getElementById('integration-submit');
            const schoolStatus = document.getElementById('school-status');
            const projectCount = document.getElementById('project-count');
            const endpoint = form.dataset.schoolsUrl;
            const initial = new Set(JSON.parse(form.dataset.selectedSchools || '[]'));
            let requestController;
            let schoolOptionsLoading = false;
            let schoolOptionsFailed = false;

            const updateProjectCount = () => {
                const n = projects.selectedOptions.length;
                projectCount.textContent = n ? `${n} selected` : 'None selected';
            };
            const setSchoolState = (state, message = '') => {
                schoolOptionsLoading = state === 'loading';
                schoolOptionsFailed = state === 'failed';
                schoolStatus.textContent = message;
                schools.setAttribute('aria-busy', String(schoolOptionsLoading));
                if (submit) submit.disabled = schoolOptionsLoading || schoolOptionsFailed;
            };
            // base64url of the UTF-8 bytes, matching the server's base64_encode (btoa alone breaks on non-Latin1 IDs).
            const encodeId = value => {
                const bytes = new TextEncoder().encode(String(value));
                let binary = '';
                bytes.forEach(b => { binary += String.fromCharCode(b); });
                return btoa(binary).replaceAll('+', '-').replaceAll('/', '_').replace(/=+$/, '');
            };

            updateProjectCount();

            schools.addEventListener('change', () => {
                if (schools.selectedOptions.length) allSchools.checked = false;
            });
            allSchools.addEventListener('change', () => {
                if (allSchools.checked) Array.from(schools.options).forEach(o => { o.selected = false; });
            });
            form.addEventListener('submit', event => {
                if (schoolOptionsLoading || schoolOptionsFailed) {
                    event.preventDefault();
                    window.alert('Wait for school access options to load successfully before saving this scope.');
                }
            });

            projects.addEventListener('change', async () => {
                updateProjectCount();
                const projectIds = Array.from(projects.selectedOptions, o => o.value);
                // Keep whatever the person already picked when the list refreshes.
                const keep = new Set([...initial, ...Array.from(schools.selectedOptions, o => o.value)]);
                requestController?.abort();
                requestController = undefined;
                setSchoolState('idle');
                schools.replaceChildren();
                if (!projectIds.length) return;

                const controller = new AbortController();
                requestController = controller;
                setSchoolState('loading', 'Loading schools…');
                const url = new URL(endpoint, window.location.origin);
                projectIds.forEach(id => url.searchParams.append('project_ids[]', id));

                try {
                    const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal });
                    if (!response.ok) throw new Error('Schools could not be loaded. Reselect the projects to try again.');
                    const rows = await response.json();
                    if (requestController !== controller) return;
                    for (const school of rows) {
                        const token = `${school.project_id}:${encodeId(school.school_id)}`;
                        schools.add(new Option(`${school.school_name || school.school_id} · ${school.school_id} · Project ${school.project_id}`, token, false, keep.has(token)));
                    }
                    setSchoolState('idle', `${rows.length} ${rows.length === 1 ? 'school' : 'schools'}`);
                } catch (error) {
                    if (requestController !== controller || error.name === 'AbortError') return;
                    setSchoolState('failed', error.message);
                    schools.add(new Option(error.message, '', true, true));
                }
            });
        })();
    </script>
</x-project_app-layout>