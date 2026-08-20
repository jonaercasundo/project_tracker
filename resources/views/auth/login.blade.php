<x-guest-layout>
    <div class="space-y-6" x-data="{ showPassword: false }">
        
        <!-- Header & Branding -->
        <div class="text-center sm:text-left mb-6">
            <div class="inline-flex sm:hidden items-center gap-2.5 mb-5 justify-center">
                <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-slate-900">MMC <span class="text-blue-600 font-medium">Tracker</span></span>
            </div>
            
            <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900">Welcome Back</h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1.5 leading-relaxed">Sign in with your credentials to access your operation pipeline.</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4 text-xs sm:text-sm p-3.5 bg-emerald-50/80 text-emerald-700 rounded-xl border border-emerald-200/60 font-medium" :status="session('status')" />

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" class="text-slate-700 font-bold text-[11px] tracking-wider uppercase mb-1.5" />
                <div class="relative">
                    <x-text-input id="email" 
                        class="block w-full px-4 py-3 pl-11 bg-slate-50/80 border border-slate-200/80 rounded-xl text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150" 
                        type="email" 
                        name="email" 
                        :value="old('email')" 
                        placeholder="operator@metro.app"
                        required 
                        autofocus 
                        autocomplete="username" />
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-500 font-medium" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('Password')" class="text-slate-700 font-bold text-[11px] tracking-wider uppercase mb-1.5" />
                <div class="relative">
                    <x-text-input id="password" 
                        ::type="showPassword ? 'text' : 'password'"
                        class="block w-full px-4 py-3 pl-11 pr-11 bg-slate-50/80 border border-slate-200/80 rounded-xl text-slate-900 text-sm placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all duration-150"
                        name="password"
                        placeholder="••••••••"
                        required 
                        autocomplete="current-password" />
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                        <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.682-.763c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m-6.165-3.13a3 3 0 10-4.243-4.243" /><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" /></svg>
                    </button>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-500 font-medium" />
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between text-xs sm:text-sm pt-1">
                <label for="remember_me" class="inline-flex items-center cursor-pointer select-none group">
                    <input id="remember_me" type="checkbox" class="w-4 h-4 rounded-md border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500/20 focus:ring-offset-0 transition-colors" name="remember">
                    <span class="ms-2 text-slate-600 group-hover:text-slate-900 transition-colors">{{ __('Remember me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="font-semibold text-blue-600 hover:text-blue-700 transition-colors rounded focus:outline-none focus:ring-2 focus:ring-blue-500/20" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <x-primary-button class="w-full justify-center py-3.5 px-5 bg-blue-600 hover:bg-blue-500 active:bg-blue-700 text-white font-bold text-xs sm:text-sm rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-xl hover:shadow-blue-500/30 hover:-translate-y-0.5 focus:ring-4 focus:ring-blue-500/20 border-0 transition-all duration-150">
                    {{ __('Sign In to Tracker') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>