<x-app-layout>
    <div class="space-y-8">
        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-bl from-blue-50 to-transparent -z-10 rounded-bl-full pointer-events-none"></div>
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">
                        Welcome Back, {{ auth()->user()->name }}
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        System Control Panel &bull; Access Level: <span class="font-semibold text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md text-xs uppercase tracking-wide border border-rose-100">Administrator</span>
                    </p>
                </div>
                
                <div class="flex flex-wrap gap-4 text-xs font-medium text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200/60">
                    <div>
                        <span class="text-slate-400 block uppercase tracking-wider font-bold">Emp ID</span>
                        <span class="text-slate-900 font-mono text-sm">{{ auth()->user()->employee_id ?? 'N/A' }}</span>
                    </div>
                    <div class="border-l border-slate-200 pl-4">
                        <span class="text-slate-400 block uppercase tracking-wider font-bold">Department</span>
                        <span class="text-slate-900 text-sm">{{ auth()->user()->department ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between group hover:border-blue-300 transition-colors">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Total Registered Staff</span>
                    <span class="text-3xl font-extrabold text-slate-900 block mt-2">
                        {{ \App\Models\User::count() }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between group hover:border-indigo-300 transition-colors">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">Active Managers / Admins</span>
                    <span class="text-3xl font-extrabold text-slate-900 block mt-2">
                        {{ \App\Models\User::whereIn('role', ['manager', 'admin'])->count() }}
                    </span>
                </div>
                <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between group hover:border-emerald-300 transition-colors">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block">System Environment</span>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-lg font-bold text-slate-800">Operational</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 group-hover:scale-105 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl p-6">
            <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-1">Administrative Utilities</h3>
            <p class="text-xs text-slate-500 mb-6">Quick-access routing pathways for global platform overrides.</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="#" class="inline-flex items-center gap-3 px-5 py-4 bg-slate-50 hover:bg-blue-50 border border-slate-200/70 hover:border-blue-200 text-slate-700 hover:text-blue-700 rounded-xl font-semibold text-sm transition-all duration-150 group">
                    <span class="p-2 bg-white rounded-lg border border-slate-200 group-hover:border-blue-100 shadow-sm text-slate-500 group-hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                    </span>
                    Add New System User
                </a>

                <a href="#" class="inline-flex items-center gap-3 px-5 py-4 bg-slate-50 hover:bg-indigo-50 border border-slate-200/70 hover:border-indigo-200 text-slate-700 hover:text-indigo-700 rounded-xl font-semibold text-sm transition-all duration-150 group">
                    <span class="p-2 bg-white rounded-lg border border-slate-200 group-hover:border-indigo-100 shadow-sm text-slate-500 group-hover:text-indigo-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </span>
                    Manage Security Access
                </a>

                <a href="#" class="inline-flex items-center gap-3 px-5 py-4 bg-slate-50 hover:bg-slate-900 border border-slate-200/70 hover:border-slate-900 text-slate-700 hover:text-white rounded-xl font-semibold text-sm transition-all duration-150 group">
                    <span class="p-2 bg-white rounded-lg border border-slate-200 group-hover:border-slate-800 shadow-sm text-slate-500 group-hover:text-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z"></path></svg>
                    </span>
                    Generate Audit Reports
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-6 bg-slate-50 min-h-screen">
            
            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden lg:col-span-2 flex flex-col">
                
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">User Directory</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Manage access control and view personnel assignments.</p>
                    </div>
                    
                    <div class="flex items-center flex-wrap sm:flex-nowrap gap-3">
                        <div class="relative w-full sm:w-64">
                            <input type="text" placeholder="Filter staff member..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 pl-8 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all placeholder-slate-400">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>

                        <div x-data="{ open: false }">
                            <button @click="open = true" class="flex items-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition-colors shadow-sm shadow-blue-500/10">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path></svg>
                                Add User
                            </button>

                            <div 
                                x-show="open" 
                                x-cloak 
                                class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center z-50 p-4"
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                            >
                                <div 
                                    @click.away="open = false" 
                                    class="bg-white w-full max-w-lg rounded-2xl p-6 shadow-xl border border-slate-100 flex flex-col max-h-[90vh]"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
                                >
                                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4 shrink-0">
                                        <div>
                                            <h2 class="text-base font-bold text-slate-900">Add New User</h2>
                                            <p class="text-[11px] text-slate-400 font-normal mt-0.5">Provision a new personnel credential asset.</p>
                                        </div>
                                        <button @click="open = false" class="text-slate-400 hover:text-slate-600 rounded-xl p-1.5 hover:bg-slate-50 transition-colors focus:outline-none focus:ring-2 focus:ring-slate-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>

                                    <form method="POST" action="{{ route('users.store') }}" class="space-y-4 overflow-y-auto pr-1 flex-grow">
                                        @csrf

                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                                                <input type="text" name="name" placeholder="e.g. Jane Doe" required
                                                    class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 placeholder-slate-400 text-slate-800 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address <span class="text-red-500">*</span></label>
                                                <input type="email" name="email" placeholder="jane.doe@company.com" required
                                                    class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 placeholder-slate-400 text-slate-800 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">Account Password <span class="text-red-500">*</span></label>
                                                <input type="password" name="password" placeholder="••••••••••••" required
                                                    class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 placeholder-slate-400 text-slate-800 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                                            </div>
                                        </div>

                                        <hr class="border-slate-100 my-2" />

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">Emp ID</label>
                                                <input type="text" name="employee_id" placeholder="EMP-2026-000"
                                                    class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 placeholder-slate-400 font-mono text-slate-800 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">Department</label>
                                                <input type="text" name="department" placeholder="e.g. Engineering"
                                                    class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 placeholder-slate-400 text-slate-800 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">Position</label>
                                                <input type="text" name="position" placeholder="e.g. Senior Developer"
                                                    class="w-full text-xs border border-slate-200 rounded-xl p-2.5 bg-slate-50/50 placeholder-slate-400 text-slate-800 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all">
                                            </div>

                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700 mb-1">Access Authorization Role</label>
                                                <select name="role" 
                                                    class="w-full text-xs border border-slate-200 bg-slate-50/50 rounded-xl p-2.5 text-slate-700 focus:outline-none focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all appearance-none cursor-pointer">
                                                    @foreach($roles as $role)
                                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100 shrink-0 mt-4">
                                            <button type="button" @click="open = false"
                                                class="px-4 py-2 text-xs font-bold text-slate-600 bg-white hover:bg-slate-50 active:bg-slate-100 border border-slate-200 rounded-xl transition-all focus:outline-none focus:ring-4 focus:ring-slate-100">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                class="px-4 py-2 text-xs font-bold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl shadow-sm shadow-blue-500/10 transition-all focus:outline-none focus:ring-4 focus:ring-blue-500/20">
                                                Save Account
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                    <table class="w-full text-left border-collapse table-auto">
                        <thead>
                            <tr class="bg-slate-50/70 border-b border-slate-200 text-slate-400 uppercase tracking-wider font-bold text-[10px] select-none">
                                <th class="py-3 px-6">User / Contact Details</th>
                                <th class="py-3 px-4">Emp_ID</th>
                                <th class="py-3 px-4">Department</th>
                                <th class="py-3 px-4">Role</th>
                                <th class="py-3 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                           @forelse($users as $user)
                                <tr class="hover:bg-slate-50/60 transition-colors duration-150">

                                    <td class="py-3.5 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center font-bold text-slate-600 border">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>

                                            <div>
                                                <span class="text-slate-900 font-semibold block">
                                                    {{ $user->name }}
                                                </span>
                                                <span class="text-[11px] text-slate-400">
                                                    {{ $user->email }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="py-3.5 px-4 font-mono text-slate-500 text-[11px]">
                                        {{ $user->employee_id ?? 'N/A' }}
                                    </td>

                                    <td class="py-3.5 px-4">
                                        <div class="font-medium text-slate-900">{{ $user->department ?? 'N/A' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $user->position ?? 'Staff' }}</div>
                                    </td>

                                    <td class="py-3.5 px-4">
                                        @forelse($user->getRoleNames() as $role)
                                            <span class="text-[10px] px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md">
                                                {{ $role }}
                                            </span>
                                        @empty
                                            <span class="text-[11px] text-slate-400 italic">None</span>
                                        @endforelse
                                    </td>

                                    <td class="py-3.5 px-6 text-right">
                                        <div class="flex justify-end gap-3">

                                            <button @click="activeModal = 'edit-{{ $user->id }}'"
                                                class="text-blue-600 text-xs font-bold">
                                                Modify Role
                                            </button>

                                            <button @click="activeModal = 'delete-{{ $user->id }}'"
                                                class="text-red-500 text-xs font-bold">
                                                Remove
                                            </button>

                                        </div>
                                    </td>

                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-slate-400">
                                        No users found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @foreach($users as $user)

                        <!-- EDIT MODAL -->
                        <div x-show="activeModal === 'edit-{{ $user->id }}'"
                            x-cloak
                            class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">

                            <div @click.away="activeModal = null"
                                class="bg-white p-6 rounded-2xl w-full max-w-md">

                                <h2 class="font-bold mb-4">Edit Role</h2>

                                <form method="POST" action="{{ route('roleaccess.update') }}">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">

                                    <select name="role" class="w-full border p-2 rounded">
                                        @foreach($roles as $role)
                                            <option value="{{ $role->name }}">
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="flex justify-end gap-2 mt-4">
                                        <button type="button" @click="activeModal = null">Cancel</button>
                                        <button type="submit">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- DELETE MODAL -->
                        <div x-show="activeModal === 'delete-{{ $user->id }}'"
                            x-cloak
                            class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">

                            <div @click.away="activeModal = null"
                                class="bg-white p-6 rounded-2xl w-full max-w-md">

                                <p>Delete {{ $user->name }}?</p>

                                <form method="POST" action="{{ url('/users/' . $user->id) }}">
                                    @csrf
                                    @method('DELETE')

                                    <div class="flex justify-end gap-2 mt-4">
                                        <button type="button" @click="activeModal = null">Cancel</button>
                                        <button class="text-red-600">Delete</button>
                                    </div>
                                </form>

                            </div>
                        </div>

                    @endforeach
                
                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
                    <span class="font-medium">Showing 2 profile resources</span>
                    <div class="flex items-center gap-2">
                        <button disabled class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-300 cursor-not-allowed font-semibold">Prev</button>
                        <button disabled class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-slate-300 cursor-not-allowed font-semibold">Next</button>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-6 flex flex-col h-fit">
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900">Live Security Logs</h3>
                        <span class="flex h-2 w-2 relative">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Real-time system events tracking.</p>
                </div>
                
                <div class="space-y-4 flex-grow overflow-y-auto max-h-[340px] pr-1">
                    <div class="flex gap-3 text-xs border-b border-slate-50 pb-3 last:border-0 last:pb-0">
                        <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                        <div>
                            <p class="font-medium text-slate-600"><span class="font-semibold text-slate-900">John Doe</span> signed into terminal block</p>
                            <span class="text-[10px] font-mono text-slate-400 block mt-0.5">IP: 192.168.1.42 &bull; 4 mins ago</span>
                        </div>
                    </div>

                    <div class="flex gap-3 text-xs border-b border-slate-50 pb-3 last:border-0 last:pb-0">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 mt-1.5 shrink-0"></div>
                        <div>
                            <p class="font-medium text-slate-600"><span class="font-semibold text-slate-900">New Account Created</span> via client registry portal</p>
                            <span class="text-[10px] font-mono text-slate-400 block mt-0.5">ID: MMC-2026-081 &bull; 1 hour ago</span>
                        </div>
                    </div>

                    <div class="flex gap-3 text-xs border-b border-slate-50 pb-3 last:border-0 last:pb-0">
                        <div class="w-1.5 h-1.5 rounded-full bg-amber-500 mt-1.5 shrink-0"></div>
                        <div>
                            <p class="font-medium text-slate-600"><span class="font-semibold text-slate-900">Configuration Matrix Update</span> key adjustments saved</p>
                            <span class="text-[10px] font-mono text-slate-400 block mt-0.5">User: System Admin &bull; 3 hours ago</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <a href="#" class="flex items-center justify-center gap-1 text-center text-xs font-bold text-blue-600 hover:text-blue-700 transition-colors">
                        Launch Global Log Viewer
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path></svg>
                    </a>
                </div>
            </div>

        </div>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('modal', { active: null });
            });
        </script>
    </div>
</x-app-layout>