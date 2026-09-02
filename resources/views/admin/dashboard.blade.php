<x-app-layout>
        @php
            $currentUser = auth()->user();

            // Current company
            $currentCompany = $currentUser->currentCompany();
            $companyId = $currentCompany?->company_id;
            $companyName = $currentCompany?->name ?? 'No Company Selected';

            // Current role
            $currentRole = $currentUser->getRoleNames()->first() ?? 'No Role';

            // Dashboard statistics
            $totalPeople = \App\Models\User::count();

            $managerAdminCount = \App\Models\User::whereIn(
                'role',
                [
                    'manager',
                    'admin',
                    'Manager',
                    'Admin',
                    'Administrator'
                ]
            )->count();
        @endphp

    <div class="space-y-8 p-6 bg-slate-50 min-h-screen">

        {{-- ================= WELCOME HEADER ================= --}}
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-bl from-blue-50 to-transparent -z-10 rounded-bl-full pointer-events-none"></div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                {{-- Greeting + company --}}
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-xl font-bold text-slate-900">Welcome back, {{ $currentUser->name }}</h3>
                        <span class="font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md text-xs uppercase tracking-wide border border-rose-100">
                            {{ $currentRole }}
                        </span>
                    </div>

                    <p class="text-sm text-slate-500 mt-2">Here's what's happening across your team today.</p>

                    <div class="flex items-center gap-2 mt-3">

                        {{-- Company Icon --}}
                        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"
                                />
                            </svg>
                        </div>

                        {{-- Company Selector --}}
                        <div>
                            <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400 block">
                                Current Company
                            </span>

                            @if($currentUser->companies->count() > 0)

                                <form
                                    method="POST"
                                    action="{{ route('company.switch') }}"
                                    class="mt-0.5"
                                >
                                    @csrf

                                    <select
                                        name="company_id"
                                        onchange="this.form.submit()"
                                        class="text-sm font-bold text-slate-800 bg-transparent border-0 p-0 pr-7 focus:ring-0 focus:outline-none cursor-pointer"
                                    >

                                        @foreach($currentUser->companies as $company)

                                            <option
                                                value="{{ $company->company_id }}"
                                                @selected($companyId == $company->company_id)
                                            >
                                                {{ $company->name }}
                                            </option>

                                        @endforeach

                                    </select>

                                </form>

                            @else

                                <span class="text-sm font-bold text-slate-500">
                                    No Company Assigned
                                </span>

                            @endif
                        </div>

                    </div>
                </div>

                {{-- User info --}}
                <div class="flex flex-wrap gap-4 text-xs font-medium text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200/60">
                    <div>
                        <span class="text-slate-400 block uppercase tracking-wider font-bold">Employee ID</span>
                        <span class="text-slate-900 font-mono text-sm">{{ $currentUser->employee_id ?? 'N/A' }}</span>
                    </div>
                    <div class="border-l border-slate-200 pl-4">
                        <span class="text-slate-400 block uppercase tracking-wider font-bold">Department</span>
                        <span class="text-slate-900 text-sm">{{ $currentUser->department ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= STATISTICS ================= --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <x-dashboard.stat-card
                label="Total People"
                :value="$totalPeople"
                :footer="$companyName"
                color="blue">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </x-dashboard.stat-card>

            <x-dashboard.stat-card
                label="Managers & Admins"
                :value="$managerAdminCount"
                footer="Access management"
                color="indigo">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                </svg>
            </x-dashboard.stat-card>

            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between group hover:border-emerald-300 transition-colors">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">System Status</span>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                        <span class="text-lg font-bold text-slate-800">All good</span>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 block">{{ $companyName }}</span>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- ================= QUICK ACTIONS ================= --}}
        <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-6">
            <h3 class="text-lg font-bold text-slate-900 tracking-tight">Quick actions</h3>
            <p class="text-xs text-slate-500 mb-6">Common tasks, one click away.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <a href="{{ route('roleaccess.index') }}"
                   class="inline-flex items-center gap-3 px-5 py-4 bg-slate-50 hover:bg-blue-50 border border-slate-200/70 hover:border-blue-200 text-slate-700 hover:text-blue-700 rounded-xl font-semibold text-sm transition-colors group">
                    <span class="p-2 bg-white rounded-lg border border-slate-200 group-hover:border-blue-100 shadow-sm text-slate-500 group-hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </span>
                    Add a new person
                </a>

                <a href="{{ route('roleaccess.index') }}"
                   class="inline-flex items-center gap-3 px-5 py-4 bg-slate-50 hover:bg-indigo-50 border border-slate-200/70 hover:border-indigo-200 text-slate-700 hover:text-indigo-700 rounded-xl font-semibold text-sm transition-colors group">
                    <span class="p-2 bg-white rounded-lg border border-slate-200 group-hover:border-indigo-100 shadow-sm text-slate-500 group-hover:text-indigo-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </span>
                    Manage access
                </a>

                <a href="#"
                   class="inline-flex items-center gap-3 px-5 py-4 bg-slate-50 hover:bg-slate-900 border border-slate-200/70 hover:border-slate-900 text-slate-700 hover:text-white rounded-xl font-semibold text-sm transition-colors group">
                    <span class="p-2 bg-white rounded-lg border border-slate-200 group-hover:border-slate-800 shadow-sm text-slate-500 group-hover:text-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14z" />
                        </svg>
                    </span>
                    View reports
                </a>
                <a href="{{ route('companies.index') }}"
                    class="inline-flex items-center gap-3 px-5 py-4 bg-slate-50 hover:bg-emerald-50 border border-slate-200/70 hover:border-emerald-200 text-slate-700 hover:text-emerald-700 rounded-xl font-semibold text-sm transition-colors group">

                        <span class="p-2 bg-white rounded-lg border border-slate-200 group-hover:border-emerald-100 shadow-sm text-slate-500 group-hover:text-emerald-600">
                            <svg class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4" />
                            </svg>
                        </span>

                        Add / Manage company
                    </a>
                        {{-- ROLE MANAGEMENT --}}
                    <a href="{{ route('roles.index') }}"
                    class="inline-flex items-center gap-3 px-5 py-4 bg-slate-50 hover:bg-purple-50 border border-slate-200/70 hover:border-purple-200 text-slate-700 hover:text-purple-700 rounded-xl font-semibold text-sm transition-colors group">

                        <span class="p-2 bg-white rounded-lg border border-slate-200 group-hover:border-purple-100 shadow-sm text-slate-500 group-hover:text-purple-600">
                            <svg class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z" />
                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06-1.5 1.5-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 .95 1.65 1.65 0 00-.12.64v.08h-2.12v-.08a1.65 1.65 0 00-1.12-1.59 1.65 1.65 0 00-1.82.33l-.06.06-1.5-1.5.06-.06A1.65 1.65 0 009.1 15a1.65 1.65 0 00-.95-1 1.65 1.65 0 00-.64-.12h-.08v-2.12h.08a1.65 1.65 0 001.59-1.12 1.65 1.65 0 00-.33-1.82l-.06-.06 1.5-1.5.06.06a1.65 1.65 0 001.82.33 1.65 1.65 0 001-1 1.65 1.65 0 00.12-.64v-.08h2.12v.08a1.65 1.65 0 001.12 1.59 1.65 1.65 0 001.82-.33l.06-.06 1.5 1.5-.06.06a1.65 1.65 0 00-.33 1.82 1.65 1.65 0 001 .95 1.65 1.65 0 00.64.12h.08v2.12h-.08a1.65 1.65 0 00-1.59 1.12z" />
                            </svg>
                        </span>

                        Manage Roles
                    </a>
            </div>
        </div>

        {{-- ================= PEOPLE + ACTIVITY ================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- PEOPLE --}}
            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden lg:col-span-2 flex flex-col">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">People</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            People with access to <span class="font-semibold text-slate-700">{{ $companyName }}</span>
                        </p>
                    </div>

                    <div class="flex items-center flex-wrap sm:flex-nowrap gap-3">
                        <form method="GET" action="{{ url()->current() }}" class="relative w-full sm:w-64">
                            <label for="people-search" class="sr-only">Search people</label>
                            <input
                                id="people-search"
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search by name, email, or department..."
                                class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 pl-8 pr-7 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-slate-400">

                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>

                            @if(request('search'))
                                <a href="{{ url()->current() }}" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600" title="Clear search" aria-label="Clear search">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            @endif
                        </form>

                        <a href="{{ route('roleaccess.index') }}"
                           class="flex items-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-sm whitespace-nowrap">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Manage people
                        </a>
                    </div>
                </div>

                <div class="p-6">
                    @if(isset($users) && $users instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                        <div class="space-y-3">
                            @forelse($users as $user)
                                <div class="flex items-center justify-between gap-4 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-600 shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $user->name }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $user->email }}</div>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap justify-end gap-1">
                                        @forelse($user->getRoleNames() as $role)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] uppercase font-bold tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                {{ $role }}
                                            </span>
                                        @empty
                                            <span class="text-[10px] text-slate-400 italic">No role</span>
                                        @endforelse
                                    </div>
                                </div>
                            @empty
                                <div class="py-10 text-center text-slate-400">
                                    <p class="text-xs">No people found.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-5">
                            {{ $users->withQueryString()->links() }}
                        </div>
                    @else
                        <div class="py-10 text-center text-slate-400">
                            <p class="text-xs">No people data available.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RECENT ACTIVITY --}}
            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 flex flex-col h-fit">
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900">Recent activity</h3>
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">What's happened lately.</p>
                </div>

                <div class="space-y-4 flex-grow overflow-y-auto max-h-[340px] pr-1">
                    @forelse($recentActivity ?? [] as $activity)
                        <div class="flex gap-3 text-xs border-b border-slate-50 pb-3 last:border-0 last:pb-0">
                            <div class="w-1.5 h-1.5 rounded-full {{ $activity['color'] ?? 'bg-slate-400' }} mt-1.5 shrink-0"></div>
                            <div>
                                <p class="font-medium text-slate-600">{!! $activity['message'] !!}</p>
                                <span class="text-[10px] font-mono text-slate-400 block mt-0.5">
                                    {{ $activity['meta'] ?? '' }} &bull; {{ $activity['time'] ?? '' }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-6">No recent activity yet.</p>
                    @endforelse
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100">
                    <a href="#" class="flex items-center justify-center gap-1 text-center text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                        See all activity
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>