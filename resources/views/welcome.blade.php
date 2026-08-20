<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900 antialiased font-sans">
<head>
    <meta charset="UTF-8">
    <title>METRO — Enterprise App Suite</title>
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

    <!-- Header Navigation -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-brand-600 rounded-xl flex items-center justify-center text-white shadow-sm">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-bold tracking-tight text-slate-900">METRO</span>
                    <span class="hidden sm:inline-block px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[11px] font-semibold border border-slate-200">Suite v4.2</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex flex-col text-right">
                            <span class="text-xs font-semibold text-slate-900">{{ auth()->user()->name ?? 'User' }}</span>
                            <span class="text-[11px] text-slate-500">{{ auth()->user()->email ?? 'user@metro.app' }}</span>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-brand-50 border border-brand-100 flex items-center justify-center font-bold text-brand-600 text-xs">
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
    <main class="flex-1 max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 space-y-8">
        
        <!-- Odoo-style Welcome Banner -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-brand-50 text-brand-600 rounded-full text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                    Live Workspace Operational
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                    Welcome to your METRO Hub, {{ auth()->user()->name ?? 'Administrator' }}
                </h1>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Access your active business apps, review operational telemetry, or complete your organization setup.
                </p>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                @auth
                    <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-sm text-center">
                        Main Dashboard
                    </a>
                @else
                    <a href="/login" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800 transition-all text-center">
                        Sign In
                    </a>
                @endauth
            </div>
        </div>

        <!-- SaaS App Switcher Grid (Odoo / Google Workspace Style) -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Business Applications</h2>
                <span class="text-xs text-slate-500 font-medium">6 Apps Installed</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                
                <a href="#" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col items-center text-center group">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
                    </div>
                    <span class="text-xs font-bold text-slate-900">Projects</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Tasks & Sheets</span>
                </a>

                <a href="#" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col items-center text-center group">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    </div>
                    <span class="text-xs font-bold text-slate-900">Inventory</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Stock & Logistics</span>
                </a>

                <a href="#" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col items-center text-center group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-xs font-bold text-slate-900">Invoicing</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Billing & Ledger</span>
                </a>

                <a href="#" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col items-center text-center group">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <span class="text-xs font-bold text-slate-900">CRM</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Leads & Pipeline</span>
                </a>

                <a href="#" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col items-center text-center group">
                    <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <span class="text-xs font-bold text-slate-900">Field Service</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Crew & On-Site</span>
                </a>

                <a href="#" class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col items-center text-center group">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <span class="text-xs font-bold text-slate-900">Employees</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">HR & Payroll</span>
                </a>

            </div>
        </div>

        <!-- SaaS Onboarding & Setup Progress -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Organization Setup Progress</h2>
                        <p class="text-xs text-slate-500 mt-0.5">3 of 4 steps completed</p>
                    </div>
                    <span class="text-xs font-bold text-brand-600 bg-brand-50 px-3 py-1 rounded-full">75% Complete</span>
                </div>

                <div class="w-full bg-slate-100 rounded-full h-2 mb-6">
                    <div class="bg-brand-600 h-2 rounded-full w-3/4"></div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">✓</div>
                            <span class="text-xs sm:text-sm font-medium text-slate-800">Company Profile & Branding</span>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md">Configured</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">✓</div>
                            <span class="text-xs sm:text-sm font-medium text-slate-800">Database & Domain Binding</span>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md">Configured</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">✓</div>
                            <span class="text-xs sm:text-sm font-medium text-slate-800">Role-Based User Permissions</span>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md">Configured</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-brand-50/50 border border-brand-100 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-brand-600 text-white flex items-center justify-center text-[10px] font-bold">4</div>
                            <span class="text-xs sm:text-sm font-medium text-slate-900">Invite Field Operators & Managers</span>
                        </div>
                        <a href="#" class="text-[11px] font-semibold text-white bg-brand-600 hover:bg-brand-500 px-3 py-1 rounded-md transition-colors">Pending Action</a>
                    </div>
                </div>
            </div>

            <!-- Quick Telemetry Side Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 mb-1">System Health</h2>
                    <p class="text-xs text-slate-500 mb-6">Real-time SaaS telemetry status</p>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100">
                            <span class="text-slate-500">Database Engine</span>
                            <span class="font-semibold text-emerald-600 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Online
                            </span>
                        </div>

                        <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100">
                            <span class="text-slate-500">Active Field Workers</span>
                            <span class="font-semibold text-slate-900">18 Connected</span>
                        </div>

                        <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100">
                            <span class="text-slate-500">Sync Latency</span>
                            <span class="font-semibold text-slate-900">&lt; 120ms</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-slate-100">
                    <a href="#" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                        View Audit Log
                    </a>
                </div>
            </div>

        </div>

    </main>

</body>
</html>