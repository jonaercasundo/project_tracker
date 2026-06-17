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
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Welcome Back</h2>
            <p class="text-sm text-slate-500 mt-1">Please sign in to access your project workspace.</p>
        </div>

        <x-auth-session-status class="mb-4 text-sm p-3 bg-emerald-50 text-emerald-700 rounded-xl border border-emerald-100" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="email" :value="__('Email')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                <x-text-input id="email" 
                    class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150" 
                    type="email" 
                    name="email" 
                    :value="old('email')" 
                    placeholder="name@company.com"
                    required 
                    autofocus 
                    autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-500 font-medium" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Password')" class="text-slate-700 font-semibold text-xs tracking-wide uppercase mb-1.5" />
                <x-text-input id="password" 
                    class="block w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150"
                    type="password"
                    name="password"
                    placeholder="••••••••"
                    required 
                    autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-500 font-medium" />
            </div>

            <div class="flex items-center justify-between text-sm pt-1">
                <label for="remember_me" class="inline-flex items-center cursor-pointer select-none group">
                    <input id="remember_me" type="checkbox" class="w-4 h-4 rounded-md border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500/20 focus:ring-offset-0" name="remember">
                    <span class="ms-2 text-slate-600 group-hover:text-slate-900 transition-colors duration-150">{{ __('Remember me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="font-medium text-blue-600 hover:text-blue-700 transition-colors duration-150 rounded focus:outline-none focus:ring-2 focus:ring-blue-500/20" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <div class="pt-2">
                <x-primary-button class="w-full justify-center py-3.5 px-5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-500/10 hover:shadow-xl hover:shadow-blue-500/20 hover:-translate-y-0.5 focus:ring-4 focus:ring-blue-500/20 border-0 transition-all duration-150">
                    {{ __('Sign In to Tracker') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>