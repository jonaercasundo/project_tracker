<div class="mx-auto flex max-w-[1500px] flex-col gap-5" x-data="{ dirty: false }" @change="dirty = true">
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
            <label class="sr-only" for="permission-search">Search permissions</label>
            <input id="permission-search" name="search" value="{{ $search }}" placeholder="Search permissions by name" class="min-w-0 rounded-xl border-slate-200 bg-slate-50 text-xs placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500/20">
            <label class="sr-only" for="permission-company">Company scope</label>
            <select id="permission-company" name="company" class="rounded-xl border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500/20">
                @foreach ($companyLabels as $value => $label)<option value="{{ $value }}" @selected($companyScope === $value)>{{ $label }} scope</option>@endforeach
            </select>
            <button class="rounded-xl bg-[#174b8a] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#123b72] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Apply filters</button>
            @if ($search || $companyScope !== 'all')<a href="{{ $scope === 'ai' ? route('admin.access.permissions.ai') : route('admin.access.permissions.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-xs font-bold text-slate-600 hover:bg-slate-50">Reset</a>@endif
        </form>
    </section>

    @if (session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800">{{ session('success') }}</div>@endif
    @if ($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs text-rose-800">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('admin.access.permissions.update') }}" @submit="if (dirty && !window.confirm('Save these role permission changes? Administrator role changes require the confirmation below.')) $event.preventDefault()">
        @csrf @method('PATCH')
        <input type="hidden" name="scope" value="{{ $scope }}">
        <input type="hidden" name="company" value="{{ $companyScope }}">
        <input type="hidden" name="search" value="{{ $search }}">
        @foreach ($roles as $role)<input type="hidden" name="role_ids[]" value="{{ $role->id }}">@endforeach

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div><h3 class="text-sm font-bold text-[#172a45]">{{ $scope === 'ai' ? 'AI and module permissions' : 'Permissions by role' }}</h3><p class="mt-1 text-[10px] text-slate-400">The table scrolls horizontally on small screens. Checkboxes save only the filtered permissions.</p></div>
                <span x-show="dirty" x-cloak class="w-fit rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-800">Unsaved changes</span>
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
                            <tr><th colspan="{{ $roles->count() + 1 }}" class="sticky top-[46px] z-10 border-b border-slate-100 bg-[#f5f8fc] px-4 py-2 text-left text-[9px] font-extrabold uppercase tracking-[.14em] text-[#31557e]">{{ $group }}</th></tr>
                            @foreach ($groupPermissions as $permission)
                                <tr class="group hover:bg-blue-50/40">
                                    <th scope="row" class="sticky left-0 z-[5] border-b border-r border-slate-100 bg-white px-4 py-3 font-semibold text-slate-700 group-hover:bg-blue-50/60">
                                        <span class="block break-all font-mono text-[10px]">{{ $permission->name }}</span>
                                        <span class="mt-1 block text-[9px] font-medium text-slate-400">{{ $permission->guard_name }} guard · {{ str_starts_with($permission->name, 'mi.') ? 'MI' : (str_starts_with($permission->name, 'mmc.') ? 'MMC' : 'Shared') }}</span>
                                    </th>
                                    @foreach ($roles as $role)
                                        @php $isAssigned = $role->permissions->contains('id', $permission->id); $lockedAiSettings = $permission->name === 'ai.settings.manage' && $role->name !== 'Administrator'; @endphp
                                        <td class="border-b border-slate-100 px-3 py-2.5 text-center">
                                            @if ($lockedAiSettings)
                                                <input type="hidden" name="permissions[{{ $role->id }}][{{ $permission->id }}]" value="{{ $isAssigned ? 1 : 0 }}">
                                                <input type="checkbox" @checked($isAssigned) disabled aria-label="{{ $permission->name }} for {{ $role->name }} is restricted to Administrator" class="h-4 w-4 rounded border-slate-300 text-slate-400 opacity-60">
                                            @else
                                                <input type="hidden" name="permissions[{{ $role->id }}][{{ $permission->id }}]" value="0">
                                                <input type="checkbox" name="permissions[{{ $role->id }}][{{ $permission->id }}]" value="1" @checked($isAssigned) @change="dirty = true" aria-label="{{ $permission->name }} for {{ $role->name }}" class="h-4 w-4 rounded border-slate-300 text-blue-700 focus:ring-blue-500">
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
            <div class="flex flex-col gap-4 border-t border-slate-100 bg-white px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <label class="flex max-w-2xl items-start gap-2.5 text-[10px] leading-relaxed text-slate-500"><input type="checkbox" name="confirm_critical" value="1" class="mt-0.5 rounded border-slate-300 text-blue-700 focus:ring-blue-500"><span>Confirm critical Administrator role changes. <span class="font-semibold text-slate-700">ai.settings.manage</span> must remain with Administrator; the last active Administrator cannot be removed from an account.</span></label>
                <button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[#174b8a] px-5 py-2.5 text-xs font-bold text-white transition hover:bg-[#123b72] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Save permission changes</button>
            </div>
        </section>
    </form>
</div>
