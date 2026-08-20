<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900 antialiased font-sans">
<head>
    <meta charset="UTF-8">
    <title>METRO — Welcome</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: { brand: { 50: '#EFF6FF', 100: '#DBEAFE', 500: '#3B82F6', 600: '#2563EB', 900: '#1E3A8A' } }
                }
            }
        }
    </script>
</head>
<body class="min-h-full flex flex-col bg-slate-50">

    <!-- Top Navigation Header (Desktop & Mobile) -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-brand-600 rounded-xl flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-xl font-bold tracking-tight text-slate-900">METRO</span>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex flex-col text-right">
                            <span class="text-xs font-semibold text-slate-900">{{ auth()->user()->name ?? 'User' }}</span>
                            <span class="text-[11px] text-slate-500">{{ auth()->user()->email ?? 'user@metro.app' }}</span>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center font-bold text-slate-600 text-xs">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                        </div>
                    </div>
                @else
                    <a href="/login" class="text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 px-3 py-2">Sign In</a>
                    <a href="/register" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-sm">Get Started</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Main Workspace -->
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8">
        
        <!-- Welcome Hero Banner -->
        <div class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-brand-900 rounded-3xl p-6 sm:p-10 text-white shadow-xl overflow-hidden mb-8">
            <div class="relative z-10 max-w-2xl">
                <span class="inline-block px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-medium text-brand-100 mb-4">
                    Workspace Ready
                </span>
                <h1 class="text-2xl sm:text-4xl font-bold tracking-tight leading-tight">
                    Welcome back, {{ auth()->user()->name ?? 'Operator' }}
                </h1>
                <p class="mt-3 text-slate-300 text-sm sm:text-base leading-relaxed">
                    Here is an overview of your active operations sheet. All field teams and project approvals are currently synced.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-md">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="/login" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-white text-slate-900 hover:bg-slate-100 transition-all">
                            Sign In to Start
                        </a>
                        <a href="/register" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-white/10 hover:bg-white/20 text-white transition-all">
                            Create Account
                        </a>
                    @endauth
                </div>
            </div>
        </div>

        <!-- Quick Status Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
            
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Field Sync Active</h3>
                    <p class="text-xs text-slate-500 mt-1">Real-time updates streaming from active job sites.</p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-brand-50 text-brand-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Access Control</h3>
                    <p class="text-xs text-slate-500 mt-1">3 active user roles assigned without overlap.</p>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-start gap-4 sm:col-span-2 lg:col-span-1">
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Pending Approvals</h3>
                    <p class="text-xs text-slate-500 mt-1">No outstanding items require approval today.</p>
                </div>
            </div>

        </div>

        <!-- Getting Started Checklist -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Quick Start Checklist</h2>
            <p class="text-xs text-slate-500 mt-0.5">Complete these steps to set up your primary workspace view.</p>

            <div class="mt-6 space-y-3 sm:space-y-4">
                <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-brand-600 text-white flex items-center justify-center text-[10px] font-bold">1</div>
                        <span class="text-xs sm:text-sm font-medium text-slate-800">Set up job site parameters</span>
                    </div>
                    <span class="text-[11px] font-semibold text-brand-600 bg-brand-50 px-2.5 py-1 rounded-md">Complete</span>
                </div>

                <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-slate-300 text-slate-700 flex items-center justify-center text-[10px] font-bold">2</div>
                        <span class="text-xs sm:text-sm font-medium text-slate-800">Invite team members & assign roles</span>
                    </div>
                    <a href="#" class="text-[11px] font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-2.5 py-1 rounded-md">Start</a>
                </div>

                <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-5 h-5 rounded-full bg-slate-300 text-slate-700 flex items-center justify-center text-[10px] font-bold">3</div>
                        <span class="text-xs sm:text-sm font-medium text-slate-800">Configure real-time field sync</span>
                    </div>
                    <a href="#" class="text-[11px] font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-2.5 py-1 rounded-md">Configure</a>
                </div>
            </div>
        </div>

    </main>

</body>
</html>