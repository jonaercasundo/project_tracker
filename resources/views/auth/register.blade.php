<x-guest-layout>
    <div class="space-y-6">
        <div class="text-center sm:text-left mb-4">
            <div class="inline-flex sm:hidden items-center gap-2 mb-4 justify-center">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75c.621 0 1.125.504 1.125 1.125v1.875c0 .621-.504 1.125-1.125 1.125H5.625a1.125 1.125 0 0 1-1.125-1.125V5.625c0-.621.504-1.125 1.125-1.125Z" />
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight text-slate-900">MMC <span class="text-blue-600 font-medium">Tracker</span></span>
            </div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Create Account</h2>
            <p class="text-sm text-slate-500 mt-1">Get started with the Metro-Mobilia project management system.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="name" :value="__('Full Name')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                <x-text-input id="name" 
                    class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150" 
                    type="text" 
                    name="name" 
                    :value="old('name')" 
                    placeholder="John Doe"
                    required 
                    autofocus 
                    autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-1.5 text-xs text-rose-500 font-medium" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="email" :value="__('Email Address')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                    <x-text-input id="email" 
                        class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150" 
                        type="email" 
                        name="email" 
                        :value="old('email')" 
                        placeholder="name@company.com"
                        required 
                        autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-500 font-medium" />
                </div>

                <div>
                    <x-input-label for="employee_id" :value="__('Employee ID')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                    <x-text-input id="employee_id" 
                        class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150" 
                        type="text" 
                        name="employee_id" 
                        :value="old('employee_id')" 
                        placeholder="e.g., MMC-2026-042"
                        required />
                    <x-input-error :messages="$errors->get('employee_id')" class="mt-1.5 text-xs text-rose-500 font-medium" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="department" :value="__('Department')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                    <x-text-input id="department" 
                        class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150" 
                        type="text" 
                        name="department" 
                        :value="old('department')" 
                        placeholder="e.g., Construction, HR"
                        required />
                    <x-input-error :messages="$errors->get('department')" class="mt-1.5 text-xs text-rose-500 font-medium" />
                </div>

                <div>
                    <x-input-label for="position" :value="__('Position')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                    <x-text-input id="position" 
                        class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150" 
                        type="text" 
                        name="position" 
                        :value="old('position')" 
                        placeholder="e.g., Project Engineer"
                        required />
                    <x-input-error :messages="$errors->get('position')" class="mt-1.5 text-xs text-rose-500 font-medium" />
                </div>
            </div>

            <div>
                <x-input-label for="role" :value="__('Requested System Role')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                <div class="relative">
                    <select id="role" name="role" required
                        class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150 appearance-none cursor-pointer">
                        <option value="" disabled selected class="text-slate-400">Select your system access tier...</option>
                        <option value="user" {{ old('role') == 'user' ? 'selected' : '' }}>Standard User</option>
                        <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>Project Manager</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('role')" class="mt-1.5 text-xs text-rose-500 font-medium" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="password" :value="__('Password')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                    <x-text-input id="password" 
                        class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        required 
                        autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-500 font-medium" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                    <x-text-input id="password_confirmation" 
                        class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150"
                        type="password"
                        name="password_confirmation" 
                        placeholder="••••••••"
                        required 
                        autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-xs text-rose-500 font-medium" />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2">
                <a class="text-sm font-medium text-blue-600 hover:text-blue-700 order-2 sm:order-1 text-center sm:text-left transition-colors duration-150 rounded focus:outline-none focus:ring-2 focus:ring-blue-500/20" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-primary-button class="w-full sm:w-auto justify-center py-3.5 px-6 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-500/10 hover:shadow-xl hover:shadow-blue-500/20 hover:-translate-y-0.5 focus:ring-4 focus:ring-blue-500/20 border-0 transition-all duration-150 order-1 sm:order-2">
                    {{ __('Register Account') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>