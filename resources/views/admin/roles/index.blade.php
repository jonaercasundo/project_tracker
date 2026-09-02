<x-app-layout>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm mb-6">
            <a href="{{ route('admin.dashboard') }}"
               class="text-slate-500 hover:text-blue-600">
                Dashboard
            </a>

            <span class="text-slate-300">/</span>

            <span class="font-medium text-slate-900">
                Role Management
            </span>
        </nav>

        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">

            <div>
                <h1 class="text-xl font-bold text-slate-900">
                    Role Management
                </h1>

                <p class="text-sm text-slate-500 mt-1">
                    Create and manage system roles.
                </p>
            </div>

            <a href="{{ route('roles.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-xl shadow-sm">

                <svg class="w-4 h-4"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2.5"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M12 4.5v15m7.5-7.5h-15" />
                </svg>

                Add Role
            </a>
        </div>

        {{-- Success --}}
        @if(session('success'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Errors --}}
        @if($errors->any())
            <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Roles --}}
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">

                            <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Role
                            </th>

                            <th class="text-left px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Users
                            </th>

                            <th class="text-right px-5 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Actions
                            </th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse($roles as $role)

                            <tr class="hover:bg-slate-50">

                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-3">

                                        <div class="w-9 h-9 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">

                                            <svg class="w-4 h-4"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 stroke-width="2"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      d="M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z" />
                                            </svg>

                                        </div>

                                        <div>
                                            <div class="font-semibold text-slate-900">
                                                {{ $role->name }}
                                            </div>

                                            <div class="text-xs text-slate-400">
                                                {{ $role->guard_name }}
                                            </div>
                                        </div>

                                    </div>

                                </td>

                                <td class="px-5 py-4 text-slate-600">
                                    {{ $role->users_count ?? 0 }}
                                </td>

                                <td class="px-5 py-4">

                                    <div class="flex justify-end gap-2">

                                        <a href="{{ route('roles.edit', $role) }}"
                                           class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-blue-600 hover:border-blue-200 text-xs font-semibold">
                                            Edit
                                        </a>

                                        <form method="POST"
                                              action="{{ route('roles.destroy', $role) }}"
                                              onsubmit="return confirm('Delete this role?');">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit"
                                                    class="px-3 py-1.5 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 text-xs font-semibold">
                                                Delete
                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="3"
                                    class="px-5 py-12 text-center text-slate-400">
                                    No roles found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-app-layout>