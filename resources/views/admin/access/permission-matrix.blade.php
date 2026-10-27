<x-app-layout>
<div class="mx-auto flex w-full min-w-0 max-w-[1500px] flex-col gap-5 {{ $scope === 'ai' ? 'pb-32' : '' }}" x-data="{ dirty: false, bulkRole: '', capabilitySearch: '', capabilityCategory: 'all', capabilityCompany: 'all', capabilityStatus: 'all', visibleCapabilities: 0, matchesCapability(card) { const query = this.capabilitySearch.trim().toLowerCase(); return (this.capabilityCategory === 'all' || card.dataset.category === this.capabilityCategory) && (this.capabilityCompany === 'all' || card.dataset.company === this.capabilityCompany) && (this.capabilityStatus === 'all' || card.dataset.status === this.capabilityStatus) && (query === '' || card.dataset.search.includes(query)); }, filterCapabilities() { this.visibleCapabilities = [...this.$root.querySelectorAll('[data-capability-card]')].filter((card) => this.matchesCapability(card)).length; }, filterRows() { const module = document.getElementById('permission-module-filter').value; const operation = document.getElementById('permission-type-filter').value; const implementation = document.getElementById('permission-status-filter').value; this.$root.querySelectorAll('[data-permission-row]').forEach((row) => { const matches = (module === 'all' || row.dataset.module === module) && (operation === 'all' || row.dataset.operation === operation) && (implementation === 'all' || row.dataset.implementation === implementation); const group = this.$root.querySelector(`[data-group-header='${row.dataset.group}']`); row.hidden = !matches || group?.dataset.open === 'false'; }); }, toggleGroup(group) { const header = this.$root.querySelector(`[data-group-header='${group}']`); header.dataset.open = header.dataset.open === 'false' ? 'true' : 'false'; this.filterRows(); }, setRolePermissions(roleId, checked) { this.$root.querySelectorAll(`input[type=checkbox][name^='permissions[${roleId}]']`).forEach((input) => { if (!input.disabled && !input.closest('[data-permission-row]')?.hidden) { input.checked = checked; } }); this.dirty = true; }, resetChanges() { this.$root.querySelectorAll('[data-original-checked]').forEach((input) => { input.checked = input.dataset.originalChecked === 'true'; }); this.dirty = false; } }" @change="if ($event.target.matches('[data-permission-checkbox]')) dirty = true" @input.debounce.150ms="if ($event.target.matches('[data-capability-search]')) filterCapabilities()" x-init="$nextTick(() => filterCapabilities())">
    @if ($scope === 'ai')
        @php
            $allCapabilities = collect($capabilityGroups)->flatMap(fn (array $category): array => $category['capabilities'])->values();
            $availableCapabilityCount = $allCapabilities->whereIn('status', ['Implemented', 'Partially Implemented'])->count();
            $permissionRestrictedCount = $allCapabilities->where('status', 'Permission Restricted')->count();
            $notImplementedCount = $allCapabilities->where('status', 'Not Implemented')->count();
            $companyScopes = $allCapabilities->pluck('company')->unique()->sort()->values();
        @endphp
        <section class="overflow-hidden rounded-2xl border border-[#dbe3ed] bg-white shadow-[0_14px_40px_rgba(23,42,69,0.06)]">
            <div class="relative overflow-hidden bg-[#132944] px-5 py-6 text-white sm:px-7 sm:py-7">
                <div class="absolute inset-y-0 left-0 w-1 bg-[#5f9ff7]"></div>
                <div class="relative flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-3xl">
                        <p class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-[.16em] text-blue-200"><x-ai-icon name="shield" size="15" /> Access control / AI</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight sm:text-[28px]">AI capability permissions</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">A readable map of what the assistant can do, which company each capability serves, and why access is available or restricted.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2"><span class="h-2 w-2 rounded-full {{ $aiEnabled && $aiConfigured ? 'bg-emerald-400' : 'bg-amber-300' }}"></span>{{ $aiEnabled ? 'Assistant enabled' : 'Assistant disabled' }}</span>
                        <span class="rounded-full border border-white/15 px-3 py-2 text-slate-200">{{ $aiConfigured ? 'Provider configured' : 'Configuration required' }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-4">
                <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4"><span class="absolute inset-y-0 left-0 w-1 bg-[#315d91]"></span><p class="text-xs font-semibold text-slate-500">Total AI capabilities</p><p class="mt-2 text-3xl font-bold tracking-tight text-[#172a45]">{{ $allCapabilities->count() }}</p><p class="mt-1 text-xs text-slate-500">Across {{ count($capabilityGroups) }} categories</p></div>
                <div class="relative overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/60 p-4"><span class="absolute inset-y-0 left-0 w-1 bg-emerald-500"></span><p class="text-xs font-semibold text-emerald-800">Available</p><p class="mt-2 text-3xl font-bold tracking-tight text-emerald-950">{{ $availableCapabilityCount }}</p><p class="mt-1 text-xs text-emerald-800">Implemented and partially implemented</p></div>
                <div class="relative overflow-hidden rounded-xl border border-rose-200 bg-rose-50/60 p-4"><span class="absolute inset-y-0 left-0 w-1 bg-rose-500"></span><p class="text-xs font-semibold text-rose-800">Permission restricted</p><p class="mt-2 text-3xl font-bold tracking-tight text-rose-950">{{ $permissionRestrictedCount }}</p><p class="mt-1 text-xs text-rose-800">Missing AI permissions</p></div>
                <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-4"><span class="absolute inset-y-0 left-0 w-1 bg-slate-400"></span><p class="text-xs font-semibold text-slate-600">Not implemented</p><p class="mt-2 text-3xl font-bold tracking-tight text-slate-800">{{ $notImplementedCount }}</p><p class="mt-1 text-xs text-slate-500">No executable capability available</p></div>
            </div>
        </section>

        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-labelledby="capability-directory-title">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-bold uppercase tracking-[.12em] text-[#315d91]">Capability directory</p><h3 id="capability-directory-title" class="mt-1 text-lg font-bold text-[#172a45]">Browse capabilities</h3></div>
                <p class="text-xs text-slate-500"><span x-text="visibleCapabilities"></span> of {{ $allCapabilities->count() }} shown</p>
            </div>
            <div class="mt-4 grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                <label class="relative min-w-0 sm:col-span-2"><span class="sr-only">Search capabilities</span><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"><x-ai-icon name="search" size="17" /></span><input type="search" data-capability-search x-model="capabilitySearch" placeholder="Search capabilities, permissions, or tools" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500/20"></label>
                <label class="min-w-0"><span class="sr-only">Filter category</span><select x-model="capabilityCategory" @change="filterCapabilities()" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500/20"><option value="all">All categories</option>@foreach ($capabilityGroups as $category)<option value="{{ $category['id'] }}">{{ $category['label'] }}</option>@endforeach</select></label>
                <label class="min-w-0"><span class="sr-only">Filter company</span><select x-model="capabilityCompany" @change="filterCapabilities()" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500/20"><option value="all">All companies</option>@foreach ($companyScopes as $companyOption)<option value="{{ $companyOption === '*' ? 'global' : $companyOption }}">{{ $companyOption === '*' ? 'Global' : $companyOption }}</option>@endforeach</select></label>
                <label class="min-w-0"><span class="sr-only">Filter status</span><select x-model="capabilityStatus" @change="filterCapabilities()" class="w-full rounded-xl border-slate-200 bg-slate-50 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500/20"><option value="all">All statuses</option><option value="implemented">Implemented</option><option value="partially-implemented">Partially implemented</option><option value="permission-restricted">Permission restricted</option><option value="business-access-restricted">Business access restricted</option><option value="company-restricted">Company restricted</option><option value="configuration-required">Configuration required</option><option value="disabled">Disabled</option><option value="not-implemented">Not implemented</option></select></label>
            </div>

            <div class="mt-4 grid min-w-0 grid-cols-1 items-start gap-3 xl:grid-cols-2">
                @foreach ($capabilityGroups as $category)
                    @foreach ($category['capabilities'] as $capability)
                        @php
                            $diagnostic = $capability['diagnostic_details'];
                            $categoryIcon = match ($category['icon']) {
                                'help' => 'help',
                                'search' => 'search',
                                'create' => 'plus',
                                'edit' => 'shield',
                                'analytics' => 'chart',
                                default => 'sparkles',
                            };
                            $statusStyle = match ($capability['status']) {
                                'Implemented' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                'Partially Implemented', 'Configuration Required', 'Disabled' => 'border-amber-200 bg-amber-50 text-amber-900',
                                'Permission Restricted', 'Business Access Restricted', 'Company Restricted' => 'border-rose-200 bg-rose-50 text-rose-800',
                                default => 'border-slate-200 bg-slate-100 text-slate-700',
                            };
                            $implementationStyle = match ($diagnostic['implementation_status']) {
                                'Implemented' => 'bg-emerald-50 text-emerald-800',
                                'Partially Implemented' => 'bg-amber-50 text-amber-900',
                                default => 'bg-slate-100 text-slate-600',
                            };
                            $cardSearch = mb_strtolower(implode(' ', array_filter([
                                $capability['label'], $capability['description'], $capability['id'], $capability['handler'] ?? '',
                                implode(' ', $capability['tool_names']), implode(' ', $diagnostic['required_ai_permissions']),
                                implode(' ', $diagnostic['business_policy_requirements']),
                            ])));
                            $capabilityCardId = 'capability-'.\Illuminate\Support\Str::slug($capability['id']);
                        @endphp
                        <article data-capability-card data-category="{{ $category['id'] }}" data-company="{{ $capability['company'] === '*' ? 'global' : $capability['company'] }}" data-status="{{ \Illuminate\Support\Str::slug($capability['status']) }}" data-search="{{ $cardSearch }}" x-show="matchesCapability($el)" x-transition.opacity.duration.150ms :aria-hidden="(!matchesCapability($el)).toString()" x-data="{ expanded: false }" class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white transition duration-150 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-[0_10px_26px_rgba(23,42,69,0.08)] motion-reduce:transform-none motion-reduce:transition-none">
                            <button type="button" @click="expanded = !expanded" :aria-expanded="expanded.toString()" aria-controls="{{ $capabilityCardId }}" class="flex w-full min-w-0 items-start gap-3 p-4 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 sm:p-5">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#edf3fa] text-[#315d91]"><x-ai-icon :name="$categoryIcon" size="20" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex min-w-0 flex-wrap items-center gap-2"><span class="break-words text-[15px] font-bold leading-5 text-[#172a45]">{{ $capability['label'] }}</span><span class="max-w-full break-all rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[11px] font-medium text-slate-600">{{ $capability['id'] }}</span></span>
                                    <span class="mt-1.5 block text-sm leading-5 text-slate-600">{{ $capability['description'] }}</span>
                                    <span class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">{{ $capability['status'] }}</span>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $implementationStyle }}">{{ $diagnostic['implementation_status'] }}</span>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $capability['company'] === '*' ? 'Global' : $capability['company'] }}</span>
                                    </span>
                                </span>
                                <svg aria-hidden="true" class="mt-2 h-4 w-4 shrink-0 text-slate-400 transition-transform duration-150 motion-reduce:transition-none" :class="expanded ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                            </button>
                            <div id="{{ $capabilityCardId }}" x-cloak x-show="expanded" x-transition.opacity.duration.150ms class="border-t border-slate-100 bg-slate-50/70 px-4 py-4 sm:px-5">
                                <p class="text-sm leading-6 text-slate-700"><span class="font-semibold text-slate-900">Why this status:</span> {{ $capability['diagnostic_reason'] }}</p>
                                <div class="mt-4 grid min-w-0 grid-cols-1 gap-3 lg:grid-cols-2">
                                    @foreach ([['Required AI permissions', $diagnostic['required_ai_permissions'], 'slate'], ['Granted permissions', $diagnostic['granted_ai_permissions'], 'emerald'], ['Missing permissions', $diagnostic['missing_ai_permissions'], 'rose'], ['Business-policy requirements', $diagnostic['business_policy_requirements'], 'blue']] as [$listTitle, $listItems, $listTone])
                                        @php
                                            $listToneClass = match ($listTone) {
                                                'emerald' => 'bg-emerald-50 text-emerald-800',
                                                'rose' => 'bg-rose-50 text-rose-800',
                                                'blue' => 'bg-blue-50 text-blue-800',
                                                default => 'bg-slate-100 text-slate-700',
                                            };
                                        @endphp
                                        <section class="min-w-0 rounded-lg border border-slate-200 bg-white p-3">
                                            <h4 class="text-xs font-bold text-slate-700">{{ $listTitle }}</h4>
                                            @if ($listItems !== [])
                                                <ul class="mt-2 flex min-w-0 flex-wrap gap-1.5">
                                                    @foreach ($listItems as $listItem)<li class="max-w-full break-all rounded-md px-2 py-1 text-xs leading-4 {{ $listToneClass }}">{{ $listItem }}</li>@endforeach
                                                </ul>
                                            @else
                                                <p class="mt-2 text-sm text-slate-500">{{ $listTitle === 'Business-policy requirements' ? 'No additional business policy' : 'None' }}</p>
                                            @endif
                                        </section>
                                    @endforeach
                                    @if ($diagnostic['missing_ai_permissions'] !== [])
                                        <div class="lg:col-span-2"><a class="inline-flex rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-800 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500" href="{{ route('admin.access.permissions.ai', array_filter(['catalog_subject_type' => $diagnosticUser ? 'user' : 'none', 'catalog_user_id' => $diagnosticUser?->getKey(), 'catalog_capability_id' => $capability['id'], 'catalog_status' => 'needs-attention', 'diagnostic_user_id' => $diagnosticUser?->getKey(), 'company_id' => $diagnosticCompany?->getKey(), 'diagnostic_capability_id' => $capability['id']])) }}#ai-permission-catalog">Manage Missing Permissions</a></div>
                                    @endif
                                    <section class="min-w-0 rounded-lg border border-slate-200 bg-white p-3">
                                        <h4 class="text-xs font-bold text-slate-700">Failed checks</h4>
                                        @php
                                            $failedChecks = collect($diagnostic['checks'])->filter(fn (array $check): bool => ! $check['granted'])->values();
                                        @endphp
                                        @if ($failedChecks->isNotEmpty())
                                            <ul class="mt-2 grid gap-1.5">@foreach ($failedChecks as $check)<li class="break-words rounded-md bg-rose-50 px-2.5 py-2 text-xs leading-5 text-rose-800"><span class="font-semibold">{{ ucfirst(str_replace('_', ' ', $check['type'])) }}:</span> {{ $check['name'] }}</li>@endforeach</ul>
                                        @else
                                            <p class="mt-2 text-sm text-emerald-700">All required checks passed.</p>
                                        @endif
                                    </section>
                                    <section class="min-w-0 rounded-lg border border-slate-200 bg-white p-3">
                                        <h4 class="text-xs font-bold text-slate-700">Implementation and risk</h4>
                                        <dl class="mt-2 grid min-w-0 gap-2 text-sm sm:grid-cols-2">
                                            <div class="min-w-0"><dt class="text-xs font-medium text-slate-500">Handler</dt><dd class="mt-0.5 break-words text-slate-800">{{ $capability['handler'] ?? 'Not connected' }}</dd></div>
                                            <div><dt class="text-xs font-medium text-slate-500">Operation / risk</dt><dd class="mt-0.5 text-slate-800">{{ $capability['operation'] }} / {{ ucfirst(str_replace('_', ' ', $capability['risk'])) }}</dd></div>
                                            <div class="min-w-0"><dt class="text-xs font-medium text-slate-500">Company scope</dt><dd class="mt-0.5 text-slate-800">{{ $capability['company'] === '*' ? 'Global' : $capability['company'] }}</dd></div>
                                            <div class="min-w-0"><dt class="text-xs font-medium text-slate-500">Confirmation / audit</dt><dd class="mt-0.5 break-words text-slate-800">{{ str_replace('_', ' ', $capability['confirmation']) }} / {{ $capability['audit'] }}</dd></div>
                                            @if ($capability['roles'] !== [])<div class="sm:col-span-2"><dt class="text-xs font-medium text-slate-500">Role restrictions</dt><dd class="mt-0.5 break-words text-slate-800">{{ implode(', ', $capability['roles']) }}</dd></div>@endif
                                            @if ($capability['tool_names'] !== [])<div class="sm:col-span-2"><dt class="text-xs font-medium text-slate-500">Registered tools</dt><dd class="mt-0.5 flex flex-wrap gap-1.5">@foreach ($capability['tool_names'] as $toolName)<span class="break-all rounded bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700">{{ $toolName }}</span>@endforeach</dd></div>@endif
                                        </dl>
                                    </section>
                                    <details class="min-w-0 rounded-lg border border-slate-200 bg-white p-3 lg:col-span-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500">Input and output contract</summary>
                                        <div class="mt-3 grid min-w-0 grid-cols-1 gap-3 lg:grid-cols-2">
                                            <div class="min-w-0"><h4 class="text-xs font-bold text-slate-600">Input schema</h4><pre class="mt-2 max-h-64 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-[#132944] p-3 text-xs leading-5 text-slate-100">{{ json_encode($capability['input_schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                            <div class="min-w-0"><h4 class="text-xs font-bold text-slate-600">Output schema</h4><pre class="mt-2 max-h-64 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-[#132944] p-3 text-xs leading-5 text-slate-100">{{ json_encode($capability['output_schema'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </article>
                    @endforeach
                @endforeach
                <div x-cloak x-show="visibleCapabilities === 0" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center sm:col-span-2"><p class="text-sm font-semibold text-slate-700">No capabilities match these filters.</p><p class="mt-1 text-sm text-slate-500">Try another search term or clear a filter.</p></div>
            </div>
        </section>

        @if (session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>@endif
        @if ($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
    @endif

    @if ($scope === 'ai')
        <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-blue-500 sm:px-6">
                <span><span class="block text-sm font-bold text-[#172a45]">Role assignments and access diagnostics</span><span class="mt-1 block text-sm text-slate-500">Manage permission grants, inspect a user’s effective access, and review the full matrix.</span></span>
                <svg aria-hidden="true" class="h-4 w-4 shrink-0 text-slate-500 transition-transform group-open:rotate-180 motion-reduce:transition-none" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </summary>
            <div class="flex min-w-0 flex-col gap-5 border-t border-slate-100 bg-slate-50/50 p-3 sm:p-5">
    @endif
    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-[10px] font-extrabold uppercase tracking-[.15em] text-blue-700">Access control</p>
                <h2 class="mt-1 text-xl font-extrabold tracking-tight text-[#172a45]">{{ $scope === 'ai' ? 'AI capability permissions' : 'Role permission matrix' }}</h2>
                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">Roles are global Spatie roles under the web guard. Company membership and module authorization still scope business data; a role alone does not grant access to every company.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-[10px] font-semibold text-slate-600"><span class="h-1.5 w-1.5 rounded-full bg-blue-600"></span>{{ $permissions->count() }} permissions · {{ $roles->count() }} roles</span>
        </div>
        <form method="GET" action="{{ $scope === 'ai' ? route('admin.access.permissions.ai') : route('admin.access.permissions.index') }}" class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-[minmax(220px,1fr)_160px_auto_auto]">
            @if ($scope === 'ai')<input type="hidden" name="scope" value="ai">@endif
            <input type="hidden" name="module" id="permission-module-submitted" value="{{ $moduleFilter }}">
            <label class="sr-only" for="permission-search">Search permissions</label>
            <input id="permission-search" name="search" value="{{ $search }}" placeholder="Search permissions by name" class="min-w-0 rounded-xl border-slate-200 bg-slate-50 text-xs placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500/20">
            <label class="sr-only" for="permission-company">Company scope</label>
            <select id="permission-company" name="company" class="rounded-xl border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500/20">
                @foreach ($companyLabels as $value => $label)<option value="{{ $value }}" @selected($companyScope === $value)>{{ $label }} scope</option>@endforeach
            </select>
            <button class="rounded-xl bg-[#174b8a] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#123b72] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Apply filters</button>
            @if ($search || $companyScope !== 'all' || $moduleFilter !== 'all')<a href="{{ $scope === 'ai' ? route('admin.access.permissions.ai') : route('admin.access.permissions.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-xs font-bold text-slate-600 hover:bg-slate-50">Reset</a>@endif
        </form>
        <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3">
            <label class="sr-only" for="permission-module-filter">Filter by module</label>
            <select id="permission-module-filter" @change="document.getElementById('permission-module-submitted').value = $event.target.value; filterRows()" class="rounded-xl border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500/20"><option value="all" @selected($moduleFilter === 'all')>All modules</option><option value="AI" @selected($moduleFilter === 'AI')>Core AI</option><option value="MI" @selected($moduleFilter === 'MI')>MI</option><option value="MMC" @selected($moduleFilter === 'MMC')>MMC</option></select>
            <label class="sr-only" for="permission-type-filter">Filter by capability type</label>
            <select id="permission-type-filter" @change="filterRows()" class="rounded-xl border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500/20"><option value="all">All capability types</option><option value="CORE">Core</option><option value="READ">Read / search</option><option value="CREATE">Create</option><option value="UPDATE">Update</option><option value="DELETE / ARCHIVE">Delete / archive</option><option value="PERMISSION">Module permission</option></select>
            <label class="sr-only" for="permission-status-filter">Filter by implementation status</label>
            <select id="permission-status-filter" @change="filterRows()" class="rounded-xl border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500/20"><option value="all">All implementation statuses</option><option value="implemented">Implemented</option><option value="partial">Partially implemented</option><option value="unavailable">Not implemented</option><option value="permission_only">Permission only</option></select>
        </div>
        @if ($scope === 'ai')
            <form method="GET" action="{{ route('admin.access.permissions.ai') }}" class="mt-4 grid grid-cols-1 gap-2 rounded-xl border border-blue-100 bg-blue-50/50 p-3 sm:grid-cols-2 xl:grid-cols-5 xl:items-end">
                <input type="hidden" name="scope" value="ai"><input type="hidden" name="company" value="{{ $companyScope }}"><input type="hidden" name="search" value="{{ $search }}"><input type="hidden" name="module" value="{{ $moduleFilter }}"><input type="hidden" name="catalog_capability_id" value="{{ $catalogCapabilityId }}"><input type="hidden" name="catalog_status" value="{{ $catalogStatusFilter }}">
                <label class="grid gap-1 text-[10px] font-semibold text-slate-600">Diagnostic company
                    <select name="company_id" class="rounded-lg border-slate-200 bg-white text-xs">
                        <option value="">Select a company</option>
                        @foreach ($companies as $companyOption)<option value="{{ $companyOption->getKey() }}" @selected($diagnosticCompany?->getKey() === $companyOption->getKey())>{{ $companyOption->name }} ({{ $companyOption->code }})</option>@endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-[10px] font-semibold text-slate-600">Effective access for user
                    <select name="diagnostic_user_id" @change="$el.form.catalog_subject_type.value = 'user'; $el.form.catalog_user_id.value = $event.target.value; $el.form.requestSubmit()" class="rounded-lg border-slate-200 bg-white text-xs">
                        @foreach ($diagnosticUsers as $diagnosticUserOption)<option value="{{ $diagnosticUserOption->getKey() }}" @selected($diagnosticUser?->getKey() === $diagnosticUserOption->getKey())>{{ $diagnosticUserOption->name }} · {{ $diagnosticUserOption->email }}</option>@endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-[10px] font-semibold text-slate-600">AI capability
                    <select name="diagnostic_capability_id" class="rounded-lg border-slate-200 bg-white text-xs">
                        <option value="">All capabilities</option>
                        @foreach ($diagnosticCapabilities as $capabilityOption)<option value="{{ $capabilityOption['id'] }}" @selected($diagnosticCapabilityId === $capabilityOption['id'])>{{ $capabilityOption['label'] }} · {{ $capabilityOption['company'] === '*' ? 'Global' : $capabilityOption['company'] }}</option>@endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-[10px] font-semibold text-slate-600">Permission catalog subject
                    <select name="catalog_subject_type" class="rounded-lg border-slate-200 bg-white text-xs"><option value="none" @selected($catalogSubjectType === 'none')>Select a user or role</option><option value="user" @selected($catalogSubjectType === 'user')>User and inherited roles</option><option value="role" @selected($catalogSubjectType === 'role')>Role permissions only</option></select>
                </label>
                <label class="grid gap-1 text-[10px] font-semibold text-slate-600">Catalog user
                    <select name="catalog_user_id" @change="$el.form.requestSubmit()" class="rounded-lg border-slate-200 bg-white text-xs"><option value="">Select a user</option>@foreach ($diagnosticUsers as $userOption)<option value="{{ $userOption->getKey() }}" @selected($catalogUser?->getKey() === $userOption->getKey())>{{ $userOption->name }} · {{ $userOption->email }}</option>@endforeach</select>
                </label>
                <label class="grid gap-1 text-[10px] font-semibold text-slate-600">Catalog role
                    <select name="catalog_role_id" @change="$el.form.requestSubmit()" class="rounded-lg border-slate-200 bg-white text-xs"><option value="">Select a role</option>@foreach ($roles as $roleOption)<option value="{{ $roleOption->getKey() }}" @selected($catalogRole?->getKey() === $roleOption->getKey())>{{ $roleOption->name }} · {{ $roleOption->users_count }} users</option>@endforeach</select>
                </label>
                <button class="rounded-lg bg-blue-700 px-4 py-2 text-xs font-bold text-white hover:bg-blue-800">Diagnose Access</button>
            </form>
            @if ($selectedCapability && $selectedCapabilityDiagnostic)
                <div class="mt-3 rounded-xl border {{ $selectedCapabilityDiagnostic['allowed'] ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-3 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2"><strong>{{ $selectedCapability['label'] }} · {{ $selectedCapabilityDiagnostic['status'] }}</strong><span>{{ $selectedCapabilityDiagnostic['reason'] }}</span></div>
                    <p class="mt-2 text-slate-600">Missing AI permissions: <span class="font-semibold text-rose-700">{{ implode(', ', $selectedCapabilityDiagnostic['missing_ai_permissions']) ?: 'None' }}</span></p>
                    <p class="mt-1 text-slate-600">Failed business checks: <span class="font-semibold text-rose-700">{{ implode('; ', $selectedCapabilityDiagnostic['failed_business_checks']) ?: 'None' }}</span></p>
                    <ul class="mt-2 grid gap-1 sm:grid-cols-2">@foreach ($selectedCapabilityDiagnostic['checks'] as $check)<li class="flex items-center justify-between gap-2 rounded-lg bg-white/70 px-2 py-1"><span class="text-slate-600">{{ $check['type'] }} · {{ $check['name'] }}</span><strong class="{{ $check['granted'] ? 'text-emerald-700' : 'text-rose-700' }}">{{ $check['granted'] ? 'Passed' : 'Failed' }}</strong></li>@endforeach</ul>
                </div>
            @endif
            <section id="ai-permission-catalog" class="mt-4 rounded-xl border border-slate-200 bg-white p-4" x-data="{ permissionSearch: '', permissionStatus: @js($catalogStatusFilter), permissionCompany: 'all', permissionCategory: 'all', permissionCapability: @js($catalogCapabilityId), matches(item) { const search = this.permissionSearch.trim().toLowerCase(); const statusMatches = this.permissionStatus === 'all' || (this.permissionStatus === 'needs-attention' && ['missing', 'not-registered'].includes(item.dataset.status)) || item.dataset.status === this.permissionStatus; return (search === '' || item.dataset.name.includes(search) || item.dataset.capabilities.includes(search)) && statusMatches && (this.permissionCompany === 'all' || item.dataset.company.split('|').includes(this.permissionCompany)) && (this.permissionCategory === 'all' || item.dataset.group === this.permissionCategory) && (this.permissionCapability === '' || item.dataset.capabilityIds.split('|').includes(this.permissionCapability)); } }">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="text-sm font-bold text-[#172a45]">AI permission catalog</h3><p class="mt-1 text-xs leading-5 text-slate-500">Discovered from capability definitions and AI permission seeders. Missing entries can be registered without granting them.</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ count($permissionCatalog) }} predefined permissions</span></div>
                @if ($catalogSubjectType === 'none' || ($catalogSubjectType === 'user' && ! $catalogUser) || ($catalogSubjectType === 'role' && ! $catalogRole))
                    <p role="status" class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5 text-sm text-blue-900">Select a user to evaluate missing permissions, or select a role to inspect permissions assigned directly to that role.</p>
                @endif
                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-5">
                    <label class="sm:col-span-2"><span class="sr-only">Search AI permissions</span><input type="search" x-model="permissionSearch" placeholder="Search permission or capability" class="w-full rounded-lg border-slate-200 bg-slate-50 text-sm"></label>
                    <label><span class="sr-only">Filter permissions by status</span><select x-model="permissionStatus" class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs"><option value="all">All permissions</option><option value="missing">Missing only</option><option value="granted">Granted only</option><option value="not-registered">Not registered</option><option value="needs-attention">Missing and not registered</option></select></label>
                    <label><span class="sr-only">Filter by company</span><select x-model="permissionCompany" class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs"><option value="all">All companies</option><option value="MMC">MMC</option><option value="MI">MI</option><option value="Global">Global</option></select></label>
                    <label><span class="sr-only">Filter by capability category</span><select x-model="permissionCategory" class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs"><option value="all">All categories</option><option value="core-ai">Core AI</option><option value="mmc-intelligence">MMC Intelligence</option><option value="mi-intelligence">MI Intelligence</option><option value="ai-business-actions">AI Business Actions</option><option value="documents-analytics">Documents &amp; Analytics</option></select></label>
                </div>
                <div class="mt-3 max-h-[34rem] space-y-3 overflow-y-auto pr-1">
                    @foreach (collect($permissionCatalog)->groupBy('group') as $groupLabel => $catalogEntries)
                        <details open class="rounded-lg border border-slate-200"><summary class="sticky top-0 cursor-pointer bg-slate-50 px-3 py-2.5 text-xs font-bold text-slate-700">{{ $groupLabel }} <span class="ml-1 font-medium text-slate-500">{{ count($catalogEntries) }}</span></summary><div class="divide-y divide-slate-100">
                            @foreach ($catalogEntries as $catalogEntry)
                                @php
                                    $catalogStatus = ! $catalogEntry['registered']
                                        ? 'not-registered'
                                        : (($catalogSubjectType === 'none' || ($catalogSubjectType === 'user' && ! $catalogUser) || ($catalogSubjectType === 'role' && ! $catalogRole))
                                            ? 'not-applicable'
                                            : ($catalogEntry['granted'] ? 'granted' : 'missing'));
                                @endphp
                                <div data-permission-item data-name="{{ strtolower($catalogEntry['name']) }}" data-capabilities="{{ strtolower(implode(' ', $catalogEntry['capabilities'])) }}" data-status="{{ $catalogStatus }}" data-company="{{ implode('|', array_map(fn (string $scope): string => $scope === '*' ? 'Global' : $scope, $catalogEntry['scopes'])) }}" data-group="{{ \Illuminate\Support\Str::slug($groupLabel) }}" data-capability-ids="{{ implode('|', $catalogEntry['capability_ids']) }}" x-show="matches($el)" class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] gap-3 px-3 py-3 sm:grid-cols-[auto_minmax(0,1fr)_auto]">
                                    <input type="checkbox" form="ai-permission-mutation" name="permission_ids[]" value="{{ $catalogEntry['permission_id'] }}" @disabled(! $catalogEntry['registered'] || ($catalogSubjectType === 'user' && $catalogEntry['inherited'] && ! $catalogEntry['direct']) || ($catalogSubjectType === 'none') || ($catalogSubjectType === 'user' && ! $catalogUser) || ($catalogSubjectType === 'role' && ! $catalogRole)) class="mt-1 rounded border-slate-300 text-blue-700 focus:ring-blue-500" aria-label="Select {{ $catalogEntry['name'] }}">
                                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><code class="break-all text-xs font-bold text-slate-800">{{ $catalogEntry['name'] }}</code>
                                        @if (! $catalogEntry['registered'])<span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-900">Not Registered</span>@elseif ($catalogSubjectType === 'none' || ($catalogSubjectType === 'user' && ! $catalogUser) || ($catalogSubjectType === 'role' && ! $catalogRole))<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Not Applicable</span>@elseif ($catalogEntry['granted'])<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-900">Granted</span>@else<span class="rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-900">Missing</span>@endif
                                        @if ($catalogEntry['registered'])<span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">Registered · web</span>@endif
                                    </div><p class="mt-1 text-xs leading-5 text-slate-600">{{ $catalogEntry['description'] }}</p><p class="mt-1 break-words text-[11px] leading-5 text-slate-500">Capabilities: {{ implode(', ', $catalogEntry['capabilities']) ?: 'Shared AI permission' }} · Scope: {{ implode(', ', array_map(fn (string $scope): string => $scope === '*' ? 'Global' : $scope, $catalogEntry['scopes'])) }} · {{ $catalogEntry['implementation_available'] ? 'Implementation available' : 'Implementation unavailable' }}</p>
                                        @if ($catalogEntry['inherited'])<p class="mt-1 text-[11px] font-medium text-blue-800">Inherited through role: {{ implode(', ', $catalogEntry['inherited_roles']) }}. <a class="underline" href="{{ route('admin.access.permissions.ai', array_filter(['catalog_subject_type' => 'role', 'catalog_role_id' => $catalogEntry['inherited_role_ids'][0] ?? null, 'diagnostic_user_id' => $diagnosticUser?->getKey(), 'company_id' => $diagnosticCompany?->getKey(), 'diagnostic_capability_id' => $diagnosticCapabilityId, 'catalog_capability_id' => $catalogCapabilityId])) }}#ai-permission-catalog">Manage role permission</a></p>@elseif ($catalogEntry['registered'] && $catalogSubjectType !== 'none' && (($catalogSubjectType === 'user' && $catalogUser) || ($catalogSubjectType === 'role' && $catalogRole)))<p class="mt-1 text-[11px] text-slate-500">Grant source: {{ $catalogEntry['source'] }}</p>@endif
                                    </div>
                                    @if (! $catalogEntry['registered'])<form method="POST" action="{{ route('admin.access.permissions.ai.register') }}" class="col-start-2 sm:col-start-auto">@csrf<input type="hidden" name="permission_name" value="{{ $catalogEntry['name'] }}"><input type="hidden" name="company_id" value="{{ $diagnosticCompany?->getKey() }}"><input type="hidden" name="diagnostic_user_id" value="{{ $diagnosticUser?->getKey() }}"><input type="hidden" name="diagnostic_capability_id" value="{{ $diagnosticCapabilityId }}"><input type="hidden" name="catalog_subject_type" value="{{ $catalogSubjectType }}"><input type="hidden" name="catalog_user_id" value="{{ $catalogUser?->getKey() }}"><input type="hidden" name="catalog_role_id" value="{{ $catalogRole?->getKey() }}"><input type="hidden" name="catalog_capability_id" value="{{ $catalogCapabilityId }}"><input type="hidden" name="catalog_status" value="{{ $catalogStatusFilter }}"><button class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-900 hover:bg-amber-50">Register Permission</button></form>@endif
                                </div>
                            @endforeach
                        </div></details>
                    @endforeach
                </div>
                <form id="ai-permission-mutation" method="POST" action="{{ route('admin.access.permissions.ai.configure') }}" class="mt-4 grid grid-cols-1 gap-3 rounded-xl border border-blue-100 bg-blue-50/40 p-3 sm:grid-cols-2 lg:grid-cols-4" @submit="if (!window.confirm('Review the selected AI permission change and its impact. Continue?')) $event.preventDefault()">
                    @csrf @method('PATCH')<input type="hidden" name="subject_type" value="{{ $catalogSubjectType === 'role' ? 'role' : 'user' }}"><input type="hidden" name="user_subject_id" value="{{ $catalogUser?->getKey() }}"><input type="hidden" name="role_subject_id" value="{{ $catalogRole?->getKey() }}"><input type="hidden" name="company_id" value="{{ $diagnosticCompany?->getKey() }}"><input type="hidden" name="diagnostic_user_id" value="{{ $diagnosticUser?->getKey() }}"><input type="hidden" name="diagnostic_capability_id" value="{{ $diagnosticCapabilityId }}"><input type="hidden" name="catalog_capability_id" value="{{ $catalogCapabilityId }}"><input type="hidden" name="catalog_status" value="{{ $catalogStatusFilter }}">
                    <label class="grid gap-1 text-xs font-semibold text-slate-700">Action<select name="action" required class="rounded-lg border-slate-200 bg-white text-xs"><option value="grant">Grant selected</option><option value="revoke">Revoke selected direct grants</option></select></label>
                    <label class="flex items-start gap-2 text-xs leading-5 text-amber-900 sm:col-span-2"><input type="checkbox" name="confirm_changes" value="1" class="mt-1 rounded border-amber-300"><span>I reviewed this permission change. Shared role changes affect every assigned user. Sensitive and bulk changes require confirmation.</span></label>
                    @if ($catalogSubjectType === 'role')<label class="flex items-start gap-2 text-xs leading-5 text-amber-900 sm:col-span-2"><input type="checkbox" name="confirm_shared_role" value="1" required class="mt-1 rounded border-amber-300"><span>Confirm this shared role change and its impact on every role member.</span></label>@endif
                    <button type="submit" @disabled($catalogSubjectType === 'none' || ($catalogSubjectType === 'user' && ! $catalogUser) || ($catalogSubjectType === 'role' && ! $catalogRole)) class="rounded-lg bg-[#174b8a] px-4 py-2 text-xs font-bold text-white hover:bg-[#123b72] disabled:cursor-not-allowed disabled:opacity-50">Review and save selected</button>
                </form>
            </section>
            <div class="mt-3 flex flex-wrap items-center gap-3 text-[10px]">
                <a href="{{ route('admin.access.review', array_filter(['company' => $diagnosticCompany?->getKey()])) }}" class="font-semibold text-blue-700 hover:underline">Review user access</a>
                <a href="{{ route('admin.audit.index', ['event' => 'role.permissions_updated']) }}" class="font-semibold text-blue-700 hover:underline">Permission change audit history</a>
                <span class="text-slate-500">Role permissions are global. Company selection scopes this diagnostic; membership and business policy are checked independently.</span>
            </div>
        @endif
    </section>

    @if ($scope !== 'ai' && session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">{{ session('success') }}</div>@endif
    @if ($scope !== 'ai' && $errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('admin.access.permissions.update') }}" @submit="if (dirty && !window.confirm('Save these role permission changes? Administrator role changes require the confirmation below.')) $event.preventDefault()">
        @csrf @method('PATCH')
        <input type="hidden" name="scope" value="{{ $scope }}">
        <input type="hidden" name="company" value="{{ $companyScope }}">
        <input type="hidden" name="search" value="{{ $search }}">
        <input type="hidden" name="module" value="{{ $moduleFilter }}">
        @if ($diagnosticCompany)<input type="hidden" name="company_id" value="{{ $diagnosticCompany->getKey() }}">@endif
        @if ($diagnosticUser)<input type="hidden" name="diagnostic_user_id" value="{{ $diagnosticUser->getKey() }}">@endif
        @foreach ($roles as $role)<input type="hidden" name="role_ids[]" value="{{ $role->id }}">@endforeach

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div><h3 class="text-sm font-bold text-[#172a45]">{{ $scope === 'ai' ? 'AI and module permissions' : 'Permissions by role' }}</h3><p class="mt-1 text-[10px] text-slate-400">The table scrolls horizontally on small screens. Checkboxes save only the filtered permissions.</p></div>
                <span x-show="dirty" x-cloak class="w-fit rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-800">Unsaved changes</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                <label for="bulk-role" class="text-[10px] font-semibold text-slate-600">Bulk assignment</label>
                <select id="bulk-role" x-model="bulkRole" class="rounded-lg border-slate-200 bg-white py-1.5 text-[10px]"><option value="">Choose role</option>@foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select>
                <button type="button" @click="if (bulkRole) setRolePermissions(bulkRole, true)" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-slate-600 hover:bg-slate-50">Select visible</button>
                <button type="button" @click="if (bulkRole) setRolePermissions(bulkRole, false)" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-slate-600 hover:bg-slate-50">Clear visible</button>
                <span class="text-[9px] text-slate-400">Bulk actions respect the selected filters.</span>
            </div>
            <div class="max-h-[68vh] overflow-auto">
                <table class="min-w-full border-separate border-spacing-0 text-left text-xs">
                    <thead class="sticky top-0 z-20 bg-slate-50">
                        <tr>
                            <th class="sticky left-0 z-30 min-w-[260px] border-b border-r border-slate-200 bg-slate-50 px-4 py-3 text-[9px] font-extrabold uppercase tracking-wider text-slate-500 sm:min-w-[320px]">Permission</th>
                            @foreach ($roles as $role)
                                <th class="min-w-[126px] border-b border-slate-200 px-3 py-3 align-bottom">
                                    <span class="block truncate text-[10px] font-extrabold text-slate-700">{{ $role->name }}</span>
                                    <span class="mt-1 block text-[9px] font-medium text-slate-400">{{ $role->users_count }} users</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permissionGroups as $group => $groupPermissions)
                            @php
                                $groupKey = \Illuminate\Support\Str::slug($group);
                            @endphp
                            <tr data-group-header="{{ $groupKey }}" data-open="true"><th colspan="{{ $roles->count() + 1 }}" class="sticky top-[46px] z-10 border-b border-slate-100 bg-[#f5f8fc] px-4 py-2 text-left text-[9px] font-extrabold uppercase tracking-[.14em] text-[#31557e]"><button type="button" @click="toggleGroup('{{ $groupKey }}')" class="flex w-full items-center justify-between text-left"><span>{{ $group }} <span class="ml-1 font-medium normal-case tracking-normal text-slate-400">{{ $groupPermissions->count() }} permissions</span></span><span aria-hidden="true">⌄</span></button></th></tr>
                            @foreach ($groupPermissions as $permission)
                                @php
                                    $detail = $permissionDetails[$permission->id];
                                @endphp
                                <tr class="group hover:bg-blue-50/40" data-permission-row data-group="{{ $groupKey }}" data-module="{{ $detail['module'] }}" data-operation="{{ $detail['operation'] }}" data-implementation="{{ $detail['implementation'] }}">
                                    <th scope="row" class="sticky left-0 z-[5] border-b border-r border-slate-100 bg-white px-4 py-3 font-semibold text-slate-700 group-hover:bg-blue-50/60">
                                        <span class="block break-all font-mono text-[10px]">{{ $permission->name }}</span>
                                        <details class="mt-1.5 text-[9px] font-normal text-slate-500"><summary class="cursor-pointer">Capability details & access diagnostic</summary><div class="mt-1 space-y-0.5"><p>Module: {{ $detail['module'] }} · Operation: {{ $detail['operation'] }}</p>@if ($detail['capability'])<p>Capability: {{ $detail['capability'] }}</p>@endif<p>Company scope: {{ $detail['company_scope'] === '*' ? 'Global' : $detail['company_scope'] }}</p><p>Implementation: {{ $detail['implementation_status'] }}</p><p class="break-words">Handler: {{ $detail['handler'] }}</p><p class="break-words">Required AI permissions: {{ implode(', ', $detail['required_ai_permissions']) ?: 'None' }}</p><p class="break-words text-emerald-700">Granted AI permissions: {{ implode(', ', $detail['granted_ai_permissions']) ?: 'None' }}</p><p class="break-words text-rose-700">Missing AI permissions: {{ implode(', ', $detail['missing_ai_permissions']) ?: 'None' }}</p><p class="break-words">Business-policy requirements: {{ implode(', ', $detail['business_policy_requirements']) ?: 'None' }}</p><p class="break-words">Business permissions: {{ implode(', ', $detail['business_permissions']) ?: 'None' }}</p><p>Risk: {{ $detail['risk'] }}</p><p>Effective access: {{ $detail['status']['status'] }}</p><p class="break-words">Failed business checks: {{ implode('; ', $detail['status']['failed_business_checks'] ?? []) ?: 'None' }}</p><p class="break-words">Why: {{ $detail['status']['reason'] }}</p></div></details>
                                        <span class="mt-1 block text-[9px] font-medium text-slate-400">{{ $permission->guard_name }} guard · {{ str_starts_with($permission->name, 'mi.') ? 'MI' : (str_starts_with($permission->name, 'mmc.') ? 'MMC' : 'Shared') }}</span>
                                    </th>
                                    @foreach ($roles as $role)
                                        @php $isAssigned = $role->permissions->contains('id', $permission->id); $lockedAiSettings = $permission->name === 'ai.settings.manage' && $role->name !== 'Administrator'; @endphp
                                        <td class="border-b border-slate-100 px-3 py-2.5 text-center">
                                            @if ($lockedAiSettings)
                                                <input type="hidden" name="permissions[{{ $role->id }}][{{ $permission->id }}]" value="{{ $isAssigned ? 1 : 0 }}">
                                                <input type="checkbox" @checked($isAssigned) data-original-checked="{{ $isAssigned ? 'true' : 'false' }}" disabled aria-label="{{ $permission->name }} for {{ $role->name }} is restricted to Administrator" class="h-4 w-4 rounded border-slate-300 text-slate-400 opacity-60">
                                            @else
                                                <input type="hidden" name="permissions[{{ $role->id }}][{{ $permission->id }}]" value="0">
                                                <input type="checkbox" data-permission-checkbox data-original-checked="{{ $isAssigned ? 'true' : 'false' }}" name="permissions[{{ $role->id }}][{{ $permission->id }}]" value="1" @checked($isAssigned) aria-label="{{ $permission->name }} for {{ $role->name }}" class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="{{ $roles->count() + 1 }}" class="px-4 py-12 text-center text-xs text-slate-500">No permissions match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="sticky bottom-0 z-30 flex flex-col gap-4 border-t border-slate-100 bg-white/95 px-4 py-4 {{ $scope === 'ai' ? 'pb-24 pr-5' : 'pr-20' }} shadow-[0_-8px_18px_-18px_rgba(15,23,42,.5)] backdrop-blur sm:flex-row sm:items-center sm:justify-between sm:px-5 {{ $scope === 'ai' ? 'sm:pb-24 sm:pr-5' : 'sm:pr-5' }}">
                <label class="flex max-w-2xl items-start gap-2.5 text-[10px] leading-relaxed text-slate-500"><input type="checkbox" name="confirm_critical" value="1" class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-500"><span>Confirm critical Administrator role changes. <span class="font-semibold text-slate-700">ai.settings.manage</span> must remain with Administrator; the last active Administrator cannot be removed from an account.</span></label>
                <div class="flex shrink-0 flex-wrap items-center gap-2"><a href="{{ $scope === 'ai' ? route('admin.access.permissions.ai') : route('admin.access.permissions.index') }}" class="rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</a><button type="button" @click="resetChanges()" class="rounded-xl border border-slate-200 px-3 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Reset changes</button><button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[#174b8a] px-5 py-2.5 text-xs font-bold text-white transition hover:bg-[#123b72] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Save Permissions</button></div>
            </div>
        </section>
    </form>
    @if ($scope === 'ai')
            </div>
        </details>
    @endif
</div>
</x-app-layout>
