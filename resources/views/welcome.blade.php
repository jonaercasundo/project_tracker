<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-900 antialiased font-sans">
<head>
    <meta charset="UTF-8">
    <title>METRO — Operations Pipeline Hub</title>
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
                    <span class="px-2 py-0.5 bg-brand-50 text-brand-700 rounded text-[11px] font-semibold border border-brand-100">Pipeline Ops</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex flex-col text-right">
                            <span class="text-xs font-semibold text-slate-900">{{ auth()->user()->name ?? 'Dispatch Lead' }}</span>
                            <span class="text-[11px] text-slate-500">{{ auth()->user()->email ?? 'ops@metro.app' }}</span>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-brand-50 border border-brand-100 flex items-center justify-center font-bold text-brand-600 text-xs">
                            {{ strtoupper(substr(auth()->user()->name ?? 'OP', 0, 2)) }}
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
        
        <!-- Welcome Operational Banner -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Pipeline Flowing — 98.4% On-Time Completion
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                    Operations Control, {{ auth()->user()->name ?? 'Dispatcher' }}
                </h1>
                <p class="text-sm text-slate-500 leading-relaxed">
                    Monitor active job stages, resolve bottlenecks, and route queued work orders through the pipeline.
                </p>
            </div>

            <div class="flex items-center gap-3 w-full sm:w-auto">
                @auth
                    <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-sm text-center">
                        Open Pipeline View
                    </a>
                @else
                    <a href="/login" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-slate-900 text-white hover:bg-slate-800 transition-all text-center">
                        Sign In to Dispatch
                    </a>
                @endauth
            </div>
        </div>

        <!-- Pipeline Stages / Quick Access Grid -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Active Pipeline Stages</h2>
                <span class="text-xs text-slate-500 font-medium">42 Jobs Moving</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                
                <a href="#" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            1
                        </div>
                        <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full">12 Pending</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900">Queued & Intake</span>
                    <span class="text-[11px] text-slate-400 mt-0.5">New work tickets awaiting review</span>
                </a>

                <a href="#" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            2
                        </div>
                        <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded-full">18 Active</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900">In Execution</span>
                    <span class="text-[11px] text-slate-400 mt-0.5">Field teams on active sites</span>
                </a>

                <a href="#" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                            3
                        </div>
                        <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-full">7 Review</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900">Quality Gate</span>
                    <span class="text-[11px] text-slate-400 mt-0.5">Approval & inspection check</span>
                </a>

                <a href="#" class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:border-brand-500 hover:shadow-md transition-all flex flex-col group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                            4
                        </div>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full">5 Ready</span>
                    </div>
                    <span class="text-sm font-bold text-slate-900">Completed & Billing</span>
                    <span class="text-[11px] text-slate-400 mt-0.5">Signed off and invoiced</span>
                </a>

            </div>
        </div>

        <!-- Pipeline Setup & Action Feed -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Pipeline Operational Checklist</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Keep routing parameters and field teams synced</p>
                    </div>
                    <span class="text-xs font-bold text-brand-600 bg-brand-50 px-3 py-1 rounded-full">Ready to Dispatch</span>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">✓</div>
                            <span class="text-xs sm:text-sm font-medium text-slate-800">Job Ticket Stage Rules Configured</span>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md">Verified</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[10px] font-bold">✓</div>
                            <span class="text-xs sm:text-sm font-medium text-slate-800">Field Crew Routing & GPS Linked</span>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md">Active</span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-brand-50/50 border border-brand-100 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-brand-600 text-white flex items-center justify-center text-[10px] font-bold">!</div>
                            <span class="text-xs sm:text-sm font-medium text-slate-900">Assign Unallocated Tickets in Intake Stage</span>
                        </div>
                        <a href="#" class="text-[11px] font-semibold text-white bg-brand-600 hover:bg-brand-500 px-3 py-1 rounded-md transition-colors">Assign (12)</a>
                    </div>
                </div>
            </div>

            <!-- Pipeline Metrics Widget -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm flex flex-col justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 mb-1">Pipeline Health</h2>
                    <p class="text-xs text-slate-500 mb-6">Real-time job throughput</p>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100">
                            <span class="text-slate-500">Avg. Cycle Time</span>
                            <span class="font-semibold text-slate-900">4.2 Hours / Ticket</span>
                        </div>

                        <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100">
                            <span class="text-slate-500">Stage Bottlenecks</span>
                            <span class="font-semibold text-emerald-600">None Detected</span>
                        </div>

                        <div class="flex items-center justify-between text-xs pb-3 border-b border-slate-100">
                            <span class="text-slate-500">Field Hand-off Rate</span>
                            <span class="font-semibold text-slate-900">99.1% Success</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-slate-100">
                    <a href="#" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors">
                        View Pipeline Analytics
                    </a>
                </div>
            </div>

        </div>

    </main>

</body>
</html>