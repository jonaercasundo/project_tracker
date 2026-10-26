<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'MMC Project Tracker') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; letter-spacing: -0.01em; }
            .glass-header { backdrop-filter: blur(16px) saturate(180%); -webkit-backdrop-filter: blur(16px) saturate(180%); }
            [x-cloak] { display: none !important; }
        </style>
    </head>
    <body class="antialiased bg-slate-50 text-slate-800 min-h-full flex flex-col relative selection:bg-blue-600 selection:text-white">
        @php
            $isAdminWorkspace = request()->routeIs(
                'admin.dashboard', 'admin.ai-configuration.*', 'admin.ai-management.*',
                'admin.access.*', 'admin.audit.*', 'roles.*', 'roleaccess.*',
                'companies.*', 'integrations.jarvis.*'
            );
        @endphp

        <div class="absolute inset-0 overflow-hidden pointer-events-none -z-50">
            <div class="absolute -top-[40%] -left-[20%] w-[80rem] h-[80rem] rounded-full bg-gradient-to-tr from-blue-200/20 to-indigo-100/10 blur-[128px]"></div>
            <div class="absolute top-[20%] -right-[30%] w-[60rem] h-[60rem] rounded-full bg-gradient-to-br from-slate-200/40 to-blue-100/20 blur-[96px]"></div>
        </div>

        @if ($isAdminWorkspace)
            <div x-data="{ mobileNavOpen: false, sidebarCollapsed: false }" class="min-h-screen flex bg-[#f4f7fb]">
                @include('layouts.admin-sidebar')

                <div class="min-w-0 flex-1 md:pl-0">
                    <header class="sticky top-0 z-30 h-[68px] border-b border-slate-200/80 bg-white/90 backdrop-blur-xl">
                        <div class="flex h-full items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                            <div class="flex min-w-0 items-center gap-3">
                                <button type="button" @click="mobileNavOpen = true" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 md:hidden" aria-label="Open administrator navigation">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                </button>
                                <div class="min-w-0">
                                    <nav aria-label="Breadcrumb" class="hidden items-center gap-2 text-[11px] font-medium text-slate-400 sm:flex">
                                        <a href="{{ route('admin.dashboard') }}" class="hover:text-blue-700">Administrator</a>
                                        <span aria-hidden="true">/</span>
                                        <span class="truncate text-slate-600">
                                            @if (request()->routeIs('admin.dashboard')) Dashboard
                                            @elseif (request()->routeIs('roles.*')) Roles &amp; permissions
                                            @elseif (request()->routeIs('roleaccess.*')) User management
                                            @elseif (request()->routeIs('companies.*')) Company management
                                            @elseif (request()->routeIs('admin.ai-configuration.*')) AI configuration
                                            @elseif (request()->routeIs('admin.ai-management.*')) AI management
                                            @elseif (request()->routeIs('admin.access.*')) Access management
                                            @elseif (request()->routeIs('admin.audit.*')) Audit &amp; security
                                            @else Integrations @endif
                                        </span>
                                    </nav>
                                    <h1 class="truncate text-sm font-bold text-slate-900 sm:text-base">
                                        @if (request()->routeIs('admin.dashboard')) Administrator overview
                                        @elseif (request()->routeIs('roles.index')) Role management
                                        @elseif (request()->routeIs('roles.create')) Create role
                                        @elseif (request()->routeIs('roles.edit')) Edit role
                                        @elseif (request()->routeIs('roleaccess.*')) User access
                                        @elseif (request()->routeIs('companies.*')) Company management
                                        @elseif (request()->routeIs('admin.ai-configuration.*')) AI configuration
                                        @elseif (request()->routeIs('admin.ai-management.*')) AI management
                                        @elseif (request()->routeIs('admin.access.permissions.ai')) AI permissions
                                        @elseif (request()->routeIs('admin.access.permissions.*')) Role permission matrix
                                        @elseif (request()->routeIs('admin.access.review')) User access review
                                        @elseif (request()->routeIs('admin.audit.*')) Activity &amp; security log
                                        @else JARVIS integration @endif
                                    </h1>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                                @php
                                    $activeCompany = auth()->user()?->currentCompany();
                                    $aiConfiguration = app(\App\Services\AiProviderConfiguration::class);
                                    $adminAiReady = $aiConfiguration->isEnabled() && $aiConfiguration->credentialSource() !== 'none';
                                @endphp
                                <span class="hidden rounded-full border px-2.5 py-1 text-[10px] font-semibold sm:inline-flex {{ $adminAiReady ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                                    <span class="mr-1.5 mt-1 h-1.5 w-1.5 rounded-full {{ $adminAiReady ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                    AI {{ $adminAiReady ? 'ready' : 'needs setup' }}
                                </span>
                                <span class="hidden max-w-36 truncate rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] font-semibold text-slate-600 sm:inline-flex">
                                    {{ $activeCompany?->code ?? 'Global administration' }}
                                </span>
                                <div x-data="{ profileOpen: false }" class="relative">
                                    <button type="button" @click="profileOpen = !profileOpen" @click.outside="profileOpen = false" :aria-expanded="profileOpen.toString()" class="flex items-center gap-2 rounded-xl px-2 py-1.5 text-left hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-800">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                                        <span class="hidden max-w-32 truncate text-xs font-semibold text-slate-700 lg:block">{{ auth()->user()->name }}</span>
                                        <svg class="hidden h-3.5 w-3.5 text-slate-400 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                                    </button>
                                    <div x-show="profileOpen" x-cloak x-transition.opacity class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                                        <div class="border-b border-slate-100 px-3 py-2">
                                            <p class="truncate text-xs font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                                            <p class="truncate text-[11px] text-slate-500">{{ auth()->user()->email }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('logout') }}" class="p-1">@csrf<button class="w-full rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-rose-700">Sign out</button></form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>

                    @isset($header)
                        <div class="border-b border-slate-200/70 bg-white px-4 py-3 sm:px-6 lg:px-8">{{ $header }}</div>
                    @endisset

                    <main class="min-h-[calc(100vh-68px)] px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-7">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        @else
            <div class="min-h-screen flex flex-col relative">
                <div class="relative z-50 shadow-sm shadow-slate-100/50">@include('layouts.navigation')</div>
                @isset($header)
                    <header class="glass-header bg-white/75 sticky top-0 z-40 border-b border-slate-200/60 transition-all duration-300 dynamic-header">
                        <div class="max-w-7xl mx-auto py-4.5 px-6 sm:px-8 lg:px-12 flex items-center justify-between">
                            <div class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl transition-all duration-300">{{ $header }}</div>
                            @isset($headerActions)<div class="flex items-center gap-3">{{ $headerActions }}</div>@endisset
                        </div>
                    </header>
                @endisset
                <main class="flex-grow w-full max-w-7xl mx-auto px-4 py-6 sm:px-8 sm:py-8 lg:px-12 lg:py-10 transition-all duration-300">
                    <div class="animate-fadeIn">{{ $slot }}</div>
                </main>
                <footer class="mt-auto border-t border-slate-200/50 bg-white/40 backdrop-blur-sm py-4 text-center text-xs font-medium text-slate-400">
                    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 flex flex-col sm:flex-row items-center justify-between gap-2">
                        <p>&copy; {{ date('Y') }} Metro-Mobilia Corporation. All rights reserved.</p><p class="text-slate-300">MMC Tracker v2.1.0</p>
                    </div>
                </footer>
            </div>
            <script>
                window.addEventListener('scroll', () => {
                    const header = document.querySelector('.dynamic-header');
                    if (header) {
                        if (window.scrollY > 20) { header.classList.remove('py-4.5'); header.classList.add('py-3', 'shadow-sm', 'bg-white/90'); }
                        else { header.classList.remove('py-3', 'shadow-sm', 'bg-white/90'); header.classList.add('py-4.5'); }
                    }
                });
            </script>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        @endif
        <x-ai-assistant />
    </body>
</html>
