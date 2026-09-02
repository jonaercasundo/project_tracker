<x-app-layout>
{{-- ================= BREADCRUMB ================= --}}
<nav class="flex items-center gap-2 text-sm mb-6" aria-label="Breadcrumb">

    <a
        href="{{ route('admin.dashboard') }}"
        class="inline-flex items-center gap-1.5 text-slate-500 hover:text-blue-600 transition-colors"
    >
        {{-- Home icon --}}
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
                d="M3 10.5L12 3l9 7.5M5.25 9v10.5a1.5 1.5 0 001.5 1.5h10.5a1.5 1.5 0 001.5-1.5V9"
            />
        </svg>

        Dashboard
    </a>

    <svg
        class="w-4 h-4 text-slate-300"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M9 5l7 7-7 7"
        />
    </svg>

    <span class="font-medium text-slate-900">
        Role Access Management
    </span>

</nav>
{{--
    This view uses Tailwind (compiled via Vite) for all styling and Alpine.js
    for modal open/close behavior — the standard pairing in a Laravel + Vite +
    Tailwind stack (this is what Laravel Breeze ships with by default).

    Requirements:
    - Tailwind configured via Vite (resources/css/app.css + vite.config.js), already
      assumed present since x-app-layout / other Tailwind views exist in this app.
    - Alpine.js available globally. If not already installed:
        npm install alpinejs
      then in resources/js/app.js:
        import Alpine from 'alpinejs';
        window.Alpine = Alpine;
        Alpine.start();
      (Breeze/Jetstream apps already have this wired up.)
    - Icons: this file uses inline SVGs (no icon font dependency), consistent
      with the dashboard view.
--}}

<style>[x-cloak] { display: none !important; }</style>

@php
    $avatarPalette = ['#2f5fe0', '#7c3aed', '#0d9488', '#e0405a', '#d97706', '#4f46e5'];
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8" x-data="{ addOpen: false }">

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z" />
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Role Access Management</h1>
                <p class="text-sm text-slate-500">Manage user accounts, company access, and roles.</p>
            </div>
        </div>

        <button
            type="button"
            @click="addOpen = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Add New User
        </button>
    </div>

    {{-- ================= FLASH MESSAGES ================= --}}
    @if(session('success'))
        <div class="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 text-sm px-4 py-3 mb-4">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 text-rose-800 text-sm px-4 py-3 mb-4">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div>
                <p class="font-semibold">Please check the following:</p>
                <ul class="list-disc pl-5 mt-1 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- ================= SEARCH ================= --}}
    <form method="GET" action="{{ url()->current() }}" class="flex flex-wrap items-center gap-3 bg-white border border-slate-200 rounded-xl p-3 mb-4">
        <div class="flex items-center gap-2 flex-1 min-w-[240px] bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus-within:border-blue-500 focus-within:bg-white transition-colors">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <label for="user-search" class="sr-only">Search users</label>
            <input
                id="user-search"
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search by name, email, or department..."
                class="w-full bg-transparent border-0 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-0">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-lg transition-colors">
            Search
        </button>
        @if(request('search'))
            <a href="{{ url()->current() }}" class="text-sm font-medium text-slate-500 hover:text-slate-900">Clear</a>
        @endif
    </form>

    {{-- ================= USER TABLE ================= --}}
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left font-semibold text-slate-500 text-xs uppercase tracking-wide px-5 py-3">Name</th>
                        <th class="text-left font-semibold text-slate-500 text-xs uppercase tracking-wide px-5 py-3">Email</th>
                        <th class="text-left font-semibold text-slate-500 text-xs uppercase tracking-wide px-5 py-3">Employee ID</th>
                        <th class="text-left font-semibold text-slate-500 text-xs uppercase tracking-wide px-5 py-3">Department</th>
                        <th class="text-left font-semibold text-slate-500 text-xs uppercase tracking-wide px-5 py-3">Companies</th>
                        <th class="text-right font-semibold text-slate-500 text-xs uppercase tracking-wide px-5 py-3">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    @php
                        $avatarColor = $avatarPalette[$user->user_id % count($avatarPalette)];
                    @endphp

                    <tr
                        class="hover:bg-slate-50/60 transition-colors"
                        x-data="{
                            editOpen: false,
                            deleteOpen: false,
                            resetOpen: false
                        }"
                    >
                        {{-- NAME --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-9 h-9 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0"
                                    style="background: {{ $avatarColor }};">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $user->name }}</div>
                                    @if($user->position)
                                        <div class="text-xs text-slate-400">{{ $user->position }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-3.5 text-slate-700">{{ $user->email }}</td>
                        <td class="px-5 py-3.5 text-slate-700">{{ $user->employee_id ?? 'N/A' }}</td>
                        <td class="px-5 py-3.5 text-slate-700">{{ $user->department ?? 'N/A' }}</td>

                        {{-- COMPANIES --}}
                        <td class="px-5 py-3.5">
                            @forelse($user->companies as $company)
                                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 mr-1 mb-1">
                                    {{ $company->name }}
                                </span>
                            @empty
                                <span class="text-slate-400 text-sm">No company assigned</span>
                            @endforelse
                        </td>

                        {{-- ACTION --}}
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="editOpen = true"
                                    title="Edit {{ $user->name }}"
                                    aria-label="Edit {{ $user->name }}"
                                    class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 flex items-center justify-center hover:border-blue-300 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    @click="deleteOpen = true"
                                    title="Delete {{ $user->name }}"
                                    aria-label="Delete {{ $user->name }}"
                                    class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 flex items-center justify-center hover:border-rose-300 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="resetOpen = true"
                                    title="Reset password for {{ $user->name }}"
                                    aria-label="Reset password for {{ $user->name }}"
                                    class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500
                                        flex items-center justify-center
                                        hover:border-amber-300 hover:text-amber-600
                                        hover:bg-amber-50 transition-colors">

                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                        aria-hidden="true">

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15.75 5.25a3.75 3.75 0 11-7.5 0
                                            3.75 3.75 0 017.5 0zM4.5 20.25a8.25
                                            8.25 0 0115 0M14.25 12.75l2.25 2.25
                                            2.25-2.25m-2.25 2.25V9.75" />

                                    </svg>

                                </button>
                            </div>
                        </td>

                        {{-- EDIT MODAL --}}
                        <template x-teleport="body">

                            <div
                                x-show="editOpen"
                                x-cloak
                                @keydown.escape.window="editOpen = false"
                                class="fixed inset-0 z-50 flex items-center justify-center p-4">

                                {{-- Overlay --}}
                                <div
                                    x-show="editOpen"
                                    x-transition.opacity
                                    @click="editOpen = false"
                                    class="absolute inset-0 bg-slate-900/50">
                                </div>

                                {{-- Modal --}}
                                <div
                                    x-show="editOpen"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    class="relative bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden">

                                    <form
                                        method="POST"
                                        action="{{ route('roleaccess.update') }}">

                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="{{ $user->user_id }}">

                                        {{-- HEADER --}}
                                        <div class="flex items-start justify-between px-6 py-5 border-b border-slate-100">

                                            <div>
                                                <h2 class="text-base font-bold text-slate-900">
                                                    Edit User Access
                                                </h2>

                                                <p class="text-sm text-slate-500">
                                                    {{ $user->name }}
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                @click="editOpen = false"
                                                class="text-slate-400 hover:text-slate-600"
                                                aria-label="Close">

                                                <svg
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    viewBox="0 0 24 24">

                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />

                                                </svg>

                                            </button>

                                        </div>


                                        {{-- BODY --}}
                                        <div class="px-6 py-5 space-y-5">

                                            {{-- USER INFORMATION --}}
                                            <div class="grid grid-cols-2 gap-4">

                                                <div>
                                                    <label
                                                        class="block text-xs font-semibold text-slate-500 mb-1">
                                                        Name
                                                    </label>

                                                    <input
                                                        type="text"
                                                        class="w-full rounded-lg border-slate-200
                                                            bg-slate-50 text-slate-500
                                                            text-sm px-3 py-2"
                                                        value="{{ $user->name }}"
                                                        disabled>
                                                </div>

                                                <div>
                                                    <label
                                                        class="block text-xs font-semibold text-slate-500 mb-1">
                                                        Email
                                                    </label>

                                                    <input
                                                        type="text"
                                                        class="w-full rounded-lg border-slate-200
                                                            bg-slate-50 text-slate-500
                                                            text-sm px-3 py-2"
                                                        value="{{ $user->email }}"
                                                        disabled>
                                                </div>

                                            </div>


                                            {{-- COMPANIES --}}
                                            <div>

                                                <label
                                                    class="block text-xs font-semibold text-slate-700 mb-2">
                                                    Companies
                                                </label>

                                                <div
                                                    class="border border-slate-200 rounded-lg
                                                        p-3 max-h-48 overflow-y-auto space-y-1">

                                                    @foreach($companies as $company)

                                                        @php
                                                            $isAssigned = $user->companies
                                                                ->contains('company_id', $company->company_id);
                                                        @endphp

                                                        <label
                                                            class="flex items-center gap-3 p-2
                                                                rounded-lg cursor-pointer
                                                                hover:bg-slate-50">

                                                            <input
                                                                type="checkbox"
                                                                name="company_ids[]"
                                                                value="{{ $company->company_id }}"
                                                                @checked($isAssigned)
                                                                class="w-4 h-4 rounded border-slate-300
                                                                    text-blue-600
                                                                    focus:ring-blue-500">

                                                            <div class="min-w-0">

                                                                <div class="text-sm font-medium text-slate-800">
                                                                    {{ $company->name }}
                                                                </div>

                                                                @if($company->code)
                                                                    <div class="text-xs text-slate-400">
                                                                        {{ $company->code }}
                                                                    </div>
                                                                @endif

                                                            </div>

                                                        </label>

                                                    @endforeach

                                                </div>

                                                @error('company_ids')
                                                    <p class="text-xs text-rose-600 mt-1">
                                                        {{ $message }}
                                                    </p>
                                                @enderror

                                                @error('company_ids.*')
                                                    <p class="text-xs text-rose-600 mt-1">
                                                        {{ $message }}
                                                    </p>
                                                @enderror

                                            </div>


                                            {{-- ROLE --}}
                                            <div>

                                                <label
                                                    for="edit_role_{{ $user->user_id }}"
                                                    class="block text-xs font-semibold text-slate-700 mb-1">

                                                    Role

                                                </label>

                                                <select
                                                    name="roles[]"
                                                    id="edit_role_{{ $user->user_id }}"
                                                    class="w-full rounded-xl border-slate-300
                                                        focus:border-blue-500
                                                        focus:ring-blue-500"
                                                    required>

                                                    <option value="">
                                                        Select Role
                                                    </option>

                                                    @php
                                                        $currentRoles = $user->getRoleNames()->toArray();
                                                    @endphp

                                                    @foreach ($roles as $role)

                                                        <option
                                                            value="{{ $role->name }}"
                                                            @selected(in_array(
                                                                $role->name,
                                                                $currentRoles
                                                            ))>

                                                            {{ $role->name }}

                                                        </option>

                                                    @endforeach

                                                </select>

                                                @error('roles')
                                                    <p class="text-xs text-rose-600 mt-1">
                                                        {{ $message }}
                                                    </p>
                                                @enderror

                                                @error('roles.*')
                                                    <p class="text-xs text-rose-600 mt-1">
                                                        {{ $message }}
                                                    </p>
                                                @enderror

                                            </div>

                                        </div>


                                        {{-- FOOTER --}}
                                        <div
                                            class="flex justify-end gap-2 px-6 py-4
                                                border-t border-slate-100 bg-slate-50">

                                            <button
                                                type="button"
                                                @click="editOpen = false"
                                                class="px-4 py-2 text-sm font-semibold
                                                    rounded-lg border border-slate-200
                                                    bg-white text-slate-700
                                                    hover:bg-slate-100">

                                                Cancel

                                            </button>

                                            <button
                                                type="submit"
                                                class="px-4 py-2 text-sm font-semibold
                                                    rounded-lg bg-blue-600 text-white
                                                    hover:bg-blue-700">

                                                Save Changes

                                            </button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </template>

                        {{-- DELETE MODAL --}}
                        <template x-teleport="body">
                            <div
                                x-show="deleteOpen"
                                x-cloak
                                @keydown.escape.window="deleteOpen = false"
                                class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                <div x-show="deleteOpen" x-transition.opacity @click="deleteOpen = false" class="absolute inset-0 bg-slate-900/50"></div>

                                <div
                                    x-show="deleteOpen"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    class="relative bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden">

                                    <form method="POST" action="{{ route('roleaccess.destroy') }}">
                                        @csrf
                                        @method('DELETE')

                                        <input type="hidden" name="user_id" value="{{ $user->user_id }}">

                                        <div class="flex items-start justify-between px-6 py-5 border-b border-slate-100">
                                            <h2 class="text-base font-bold text-rose-600">Confirm Delete</h2>

                                            <button
                                                type="button"
                                                @click="deleteOpen = false"
                                                class="text-slate-400 hover:text-slate-600"
                                                aria-label="Close"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2"
                                                    viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="px-6 py-5">
                                            <p class="text-sm text-slate-600 mb-3">
                                                Are you sure you want to delete this user? This action cannot be undone.
                                            </p>

                                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5">
                                                <p class="text-sm font-semibold text-slate-900">
                                                    {{ $user->name }}
                                                </p>

                                                <p class="text-xs text-slate-500">
                                                    {{ $user->email }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50">
                                            <button
                                                type="button"
                                                @click="deleteOpen = false"
                                                class="px-4 py-2 text-sm font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-100"
                                            >
                                                Cancel
                                            </button>

                                            <button
                                                type="submit"
                                                class="px-4 py-2 text-sm font-semibold rounded-lg bg-rose-600 text-white hover:bg-rose-700"
                                            >
                                                Delete User
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>
                        {{-- RESET PASSWORD MODAL --}}
                        <template x-teleport="body">

                            <div
                                x-show="resetOpen"
                                x-cloak
                                @keydown.escape.window="resetOpen = false"
                                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                            >

                                {{-- Overlay --}}
                                <div
                                    x-show="resetOpen"
                                    x-transition.opacity
                                    @click="resetOpen = false"
                                    class="absolute inset-0 bg-slate-900/50"
                                ></div>


                                {{-- Modal --}}
                                <div
                                    x-show="resetOpen"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    class="relative bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden"
                                >

                                    <form
                                        method="POST"
                                        action="{{ route('users.reset-password') }}"
                                    >

                                        @csrf

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="{{ $user->user_id }}"
                                        >

                                        {{-- Header --}}
                                        <div class="flex items-start justify-between px-6 py-5 border-b border-slate-100">

                                            <div>

                                                <h2 class="text-base font-bold text-slate-900">
                                                    Reset Password
                                                </h2>

                                                <p class="text-sm text-slate-500 mt-1">
                                                    {{ $user->name }}
                                                </p>

                                            </div>

                                            <button
                                                type="button"
                                                @click="resetOpen = false"
                                                class="text-slate-400 hover:text-slate-600"
                                                aria-label="Close"
                                            >

                                                <svg
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    viewBox="0 0 24 24"
                                                >

                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12"
                                                    />

                                                </svg>

                                            </button>

                                        </div>


                                        {{-- Body --}}
                                        <div class="px-6 py-5 space-y-4">

                                            <div class="rounded-xl bg-amber-50 border border-amber-200 px-4 py-3">

                                                <p class="text-sm text-amber-800">

                                                    You are resetting the password for:

                                                </p>

                                                <p class="text-sm font-bold text-slate-900 mt-1">

                                                    {{ $user->name }}

                                                </p>

                                                <p class="text-xs text-slate-500">

                                                    {{ $user->email }}

                                                </p>

                                            </div>


                                            {{-- New Password --}}
                                            <div>

                                                <label
                                                    for="reset_password_{{ $user->user_id }}"
                                                    class="block text-xs font-semibold text-slate-700 mb-1"
                                                >
                                                    New Password
                                                </label>

                                                <input
                                                    id="reset_password_{{ $user->user_id }}"
                                                    type="password"
                                                    name="password"
                                                    class="w-full rounded-lg border-slate-200
                                                        text-sm px-3 py-2
                                                        focus:border-amber-500
                                                        focus:ring-amber-500/20"
                                                    placeholder="Enter new password"
                                                    minlength="8"
                                                    autocomplete="new-password"
                                                    required
                                                >

                                            </div>


                                            {{-- Confirm Password --}}
                                            <div>

                                                <label
                                                    for="reset_password_confirmation_{{ $user->user_id }}"
                                                    class="block text-xs font-semibold text-slate-700 mb-1"
                                                >
                                                    Confirm New Password
                                                </label>

                                                <input
                                                    id="reset_password_confirmation_{{ $user->user_id }}"
                                                    type="password"
                                                    name="password_confirmation"
                                                    class="w-full rounded-lg border-slate-200
                                                        text-sm px-3 py-2
                                                        focus:border-amber-500
                                                        focus:ring-amber-500/20"
                                                    placeholder="Confirm new password"
                                                    minlength="8"
                                                    autocomplete="new-password"
                                                    required
                                                >

                                            </div>

                                        </div>


                                        {{-- Footer --}}
                                        <div class="flex justify-end gap-2 px-6 py-4
                                                    border-t border-slate-100 bg-slate-50">

                                            <button
                                                type="button"
                                                @click="resetOpen = false"
                                                class="px-4 py-2 text-sm font-semibold
                                                    rounded-lg border border-slate-200
                                                    bg-white text-slate-700
                                                    hover:bg-slate-100"
                                            >
                                                Cancel
                                            </button>

                                            <button
                                                type="submit"
                                                class="px-4 py-2 text-sm font-semibold
                                                    rounded-lg bg-amber-600
                                                    text-white hover:bg-amber-700"
                                            >
                                                Reset Password
                                            </button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </template>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-14 text-slate-400">
                            <svg class="w-7 h-7 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                            </svg>
                            No users found.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($users) && $users instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="px-5 py-3 border-t border-slate-200">
                {{ $users->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- ================= ADD USER MODAL ================= --}}
    <template x-teleport="body">
        <div
            x-show="addOpen"
            x-cloak
            @keydown.escape.window="addOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div x-show="addOpen" x-transition.opacity @click="addOpen = false" class="absolute inset-0 bg-slate-900/50"></div>

            <div
                x-show="addOpen"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="relative bg-white w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden max-h-[90vh] flex flex-col">

                <form method="POST" action="{{ route('users.store') }}" autocomplete="off" class="flex flex-col overflow-hidden">
                    @csrf

                    <div class="flex items-start justify-between px-6 py-5 border-b border-slate-100 shrink-0">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Add New User</h2>
                            <p class="text-sm text-slate-500">Create a user and assign company access.</p>
                        </div>
                        <button type="button" @click="addOpen = false" class="text-slate-400 hover:text-slate-600" aria-label="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-6 overflow-y-auto">
                        {{-- BASIC INFORMATION --}}
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide pb-2 mb-3 border-b border-slate-100">Basic information</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="add_name" class="block text-xs font-semibold text-slate-700 mb-1">Full Name</label>
                                    <input
                                        id="add_name" type="text" name="name" value="{{ old('name') }}"
                                        class="w-full rounded-lg text-sm px-3 py-2 border {{ $errors->has('name') ? 'border-rose-400' : 'border-slate-200' }} focus:border-blue-500 focus:ring-blue-500/20"
                                        placeholder="Full Name" required>
                                    @error('name')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="add_email" class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                                    <input
                                        id="add_email" type="email" name="email" value="{{ old('email') }}"
                                        class="w-full rounded-lg text-sm px-3 py-2 border {{ $errors->has('email') ? 'border-rose-400' : 'border-slate-200' }} focus:border-blue-500 focus:ring-blue-500/20"
                                        placeholder="Email Address" required>
                                    @error('email')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="add_employee_id" class="block text-xs font-semibold text-slate-700 mb-1">Employee ID</label>
                                    <input
                                        id="add_employee_id" type="text" name="employee_id" value="{{ old('employee_id') }}"
                                        class="w-full rounded-lg text-sm px-3 py-2 border {{ $errors->has('employee_id') ? 'border-rose-400' : 'border-slate-200' }} focus:border-blue-500 focus:ring-blue-500/20"
                                        placeholder="Employee ID">
                                    @error('employee_id')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="add_department" class="block text-xs font-semibold text-slate-700 mb-1">Department</label>
                                    <input
                                        id="add_department" type="text" name="department" value="{{ old('department') }}"
                                        class="w-full rounded-lg text-sm px-3 py-2 border {{ $errors->has('department') ? 'border-rose-400' : 'border-slate-200' }} focus:border-blue-500 focus:ring-blue-500/20"
                                        placeholder="Department">
                                    @error('department')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- ACCESS --}}
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide pb-2 mb-3 border-b border-slate-100">
                                Access
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                {{-- COMPANIES --}}
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                                        Companies
                                    </label>

                                    <div class="w-full rounded-lg border border-slate-200 p-3
                                                max-h-40 overflow-y-auto space-y-2">

                                        @foreach($companies as $company)

                                            <label
                                                class="flex items-center gap-3 p-2 rounded-lg
                                                    hover:bg-slate-50 cursor-pointer">

                                                <input
                                                    type="checkbox"
                                                    name="company_ids[]"
                                                    value="{{ $company->company_id }}"
                                                    @checked(
                                                        in_array(
                                                            $company->company_id,
                                                            old('company_ids', [])
                                                        )
                                                    )
                                                    class="w-4 h-4 rounded border-slate-300
                                                        text-blue-600
                                                        focus:ring-blue-500">

                                                <div class="min-w-0">

                                                    <div class="text-sm font-medium text-slate-800">
                                                        {{ $company->name }}
                                                    </div>

                                                    @if($company->code)
                                                        <div class="text-xs text-slate-400">
                                                            {{ $company->code }}
                                                        </div>
                                                    @endif

                                                </div>

                                            </label>

                                        @endforeach

                                    </div>

                                    @error('company_ids')
                                        <p class="text-xs text-rose-600 mt-1">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                    @error('company_ids.*')
                                        <p class="text-xs text-rose-600 mt-1">
                                            {{ $message }}
                                        </p>
                                    @enderror
                                </div>


                                {{-- ROLE --}}
                                <div>

                                    <label
                                        for="add_role"
                                        class="block text-xs font-semibold text-slate-700 mb-1">
                                        Role
                                    </label>

                                    <select
                                        id="add_role"
                                        name="roles[]"
                                        class="w-full rounded-lg text-sm px-3 py-2 border
                                            {{ $errors->has('roles') ? 'border-rose-400' : 'border-slate-200' }}
                                            focus:border-blue-500 focus:ring-blue-500/20"
                                        required>

                                        <option value="">
                                            Select Role
                                        </option>

                                        @foreach($roles as $role)

                                            <option
                                                value="{{ $role->name }}"
                                                @selected(
                                                    in_array(
                                                        $role->name,
                                                        old('roles', [])
                                                    )
                                                )>

                                                {{ $role->name }}

                                            </option>

                                        @endforeach

                                    </select>

                                    @error('roles')
                                        <p class="text-xs text-rose-600 mt-1">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                    @error('roles.*')
                                        <p class="text-xs text-rose-600 mt-1">
                                            {{ $message }}
                                        </p>
                                    @enderror

                                </div>

                            </div>
                        </div>

                        {{-- PASSWORD --}}
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide pb-2 mb-3 border-b border-slate-100">Password</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="add_password" class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                                    <input
                                        id="add_password" type="password" name="password"
                                        class="w-full rounded-lg text-sm px-3 py-2 border {{ $errors->has('password') ? 'border-rose-400' : 'border-slate-200' }} focus:border-blue-500 focus:ring-blue-500/20"
                                        placeholder="Password" minlength="8" autocomplete="new-password" required>
                                    @error('password')
                                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="add_password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Confirm Password</label>
                                    <input
                                        id="add_password_confirmation" type="password" name="password_confirmation"
                                        class="w-full rounded-lg text-sm px-3 py-2 border border-slate-200 focus:border-blue-500 focus:ring-blue-500/20"
                                        placeholder="Confirm Password" minlength="8" autocomplete="new-password" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-slate-100 bg-slate-50 shrink-0">
                        <button type="button" @click="addOpen = false" class="px-4 py-2 text-sm font-semibold rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>

</div>
</x-app-layout>