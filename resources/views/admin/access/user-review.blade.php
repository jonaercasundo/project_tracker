<div class="mx-auto flex max-w-[1500px] flex-col gap-5">
    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6">
        <div><p class="text-[10px] font-extrabold uppercase tracking-[.15em] text-blue-700">Access governance</p><h2 class="mt-1 text-xl font-extrabold tracking-tight text-[#172a45]">User access review</h2><p class="mt-1.5 max-w-3xl text-xs leading-relaxed text-slate-500">Roles are global to the web guard. Effective permissions combine role and direct grants; company membership is listed separately and does not replace module-level authorization.</p></div>
        <form method="GET" class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_180px_180px_150px_auto]">
            <label class="sr-only" for="access-search">Search users</label><input id="access-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name, email, employee ID" class="rounded-xl border-slate-200 bg-slate-50 text-xs focus:border-blue-500 focus:ring-blue-500/20">
            <label class="sr-only" for="access-company">Company</label><select id="access-company" name="company" class="rounded-xl border-slate-200 bg-slate-50 text-xs"><option value="">All companies</option>@foreach($companies as $company)<option value="{{ $company->company_id }}" @selected(($filters['company'] ?? null) == $company->company_id)>{{ $company->name }} ({{ $company->code }})</option>@endforeach</select>
            <label class="sr-only" for="access-role">Role</label><select id="access-role" name="role" class="rounded-xl border-slate-200 bg-slate-50 text-xs"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->name }}" @selected(($filters['role'] ?? null) === $role->name)>{{ $role->name }}</option>@endforeach</select>
            <label class="sr-only" for="access-status">Account status</label><select id="access-status" name="status" class="rounded-xl border-slate-200 bg-slate-50 text-xs"><option value="all">All statuses</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option></select>
            <button class="rounded-xl bg-[#174b8a] px-4 py-2.5 text-xs font-bold text-white hover:bg-[#123b72]">Apply filters</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h3 class="text-sm font-bold text-[#172a45]">People and effective access</h3><p class="mt-1 text-[10px] text-slate-400">{{ $users->total() }} matching accounts · page {{ $users->currentPage() }} of {{ $users->lastPage() }}</p></div><a href="{{ route('roleaccess.index', ['add' => 1]) }}" class="rounded-xl bg-[#174b8a] px-3.5 py-2 text-[10px] font-bold text-white hover:bg-[#123b72]">Add user</a></div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs">
                <thead class="bg-slate-50 text-[9px] font-extrabold uppercase tracking-wider text-slate-500"><tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Account</th><th class="px-4 py-3">Global roles</th><th class="px-4 py-3">Company membership</th><th class="px-4 py-3">Effective permissions</th><th class="px-4 py-3 text-right">Review</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        @php $effectivePermissions = $user->getAllPermissions()->sortBy('name'); @endphp
                        <tr class="align-top hover:bg-slate-50/60">
                            <td class="min-w-[210px] px-4 py-3.5"><p class="font-bold text-slate-800">{{ $user->name }}</p><p class="mt-1 text-[10px] text-slate-500">{{ $user->email }}</p><p class="mt-1 text-[9px] text-slate-400">{{ $user->employee_id ?: 'No employee ID' }} · {{ $user->department ?: 'No department' }}</p></td>
                            <td class="px-4 py-3.5"><span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-bold {{ $user->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="min-w-[150px] px-4 py-3.5"><div class="flex flex-wrap gap-1">@forelse($user->roles as $role)<span class="rounded-md bg-indigo-50 px-2 py-1 text-[9px] font-semibold text-indigo-800">{{ $role->name }}</span>@empty<span class="text-[10px] text-slate-400">No role</span>@endforelse</div><p class="mt-1.5 text-[9px] text-slate-400">Global role scope</p></td>
                            <td class="min-w-[170px] px-4 py-3.5"><div class="flex flex-wrap gap-1">@forelse($user->companies as $company)<span class="rounded-md bg-blue-50 px-2 py-1 text-[9px] font-semibold text-blue-800">{{ $company->code }}</span>@empty<span class="text-[10px] text-rose-600">No company access</span>@endforelse</div></td>
                            <td class="min-w-[180px] px-4 py-3.5"><p class="font-bold text-slate-700">{{ $effectivePermissions->count() }} permissions</p><p class="mt-1 max-w-[260px] break-words text-[9px] leading-relaxed text-slate-400">{{ $effectivePermissions->take(5)->pluck('name')->implode(' · ') ?: 'No effective permissions' }}{{ $effectivePermissions->count() > 5 ? ' · +' . ($effectivePermissions->count() - 5) . ' more' : '' }}</p></td>
                            <td class="whitespace-nowrap px-4 py-3.5 text-right"><a href="{{ route('roleaccess.index', ['search' => $user->email]) }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10px] font-bold text-slate-600 hover:border-blue-200 hover:text-blue-700">Edit access</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center"><p class="text-xs font-semibold text-slate-700">No accounts match these filters</p><p class="mt-1 text-[10px] text-slate-400">Try a different company, role, or search term.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">{{ $users->links() }}</div>
    </section>
</div>
