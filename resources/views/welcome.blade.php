<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900 text-slate-100 antialiased font-sans scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>METRO — Real-Time Operations Pipeline Platform</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
                    colors: { brand: { 500: '#3B82F6', 600: '#2563EB', 700: '#1D4ED8' } }
                }
            }
        }
    </script>
</head>
<body class="min-h-full flex flex-col bg-slate-900 text-slate-100">

    <!-- Top Commercial Header -->
    <header class="bg-slate-900/80 backdrop-blur-md border-b border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-brand-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-500/20">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <span class="text-2xl font-bold tracking-tight text-white">METRO</span>
            </div>

            <!-- Main Nav Links -->
            <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-400">
                <a href="#features" class="hover:text-white transition-colors">Features</a>
                <a href="#pipeline" class="hover:text-white transition-colors">Pipeline Flow</a>
                <a href="#solutions" class="hover:text-white transition-colors">Solutions</a>
                <a href="#pricing" class="hover:text-white transition-colors">Pricing</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-lg shadow-brand-600/30">
                        Launch App
                    </a>
                @else
                    <a href="/login" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-white px-3 py-2 transition-colors">Sign In</a>
                    <a href="/register" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-lg shadow-brand-600/30">
                        Start Free Trial
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-12 pb-20 md:pt-24 md:pb-32 overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_-20%,rgba(37,99,235,0.25),rgba(255,255,255,0))]"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-500/10 border border-brand-500/20 text-brand-400 text-xs sm:text-sm font-semibold mb-8">
                <span class="w-2 h-2 rounded-full bg-brand-500 animate-pulse"></span>
                METRO v4.2 Pipeline Engine Released
            </div>

            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white leading-[1.1] max-w-4xl mx-auto">
                Total Control Over Your <span class="bg-gradient-to-r from-brand-500 via-blue-400 to-indigo-400 bg-clip-text text-transparent">Operations Pipeline</span>
            </h1>

            <p class="mt-6 text-base sm:text-xl text-slate-400 max-w-2xl mx-auto font-normal leading-relaxed">
                Connect field teams, dispatch logistics, and quality approvals into one seamlessly automated operational pipeline. Stop bottlenecks before they start.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="/register" class="w-full sm:w-auto px-8 py-4 rounded-xl text-sm font-bold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-xl shadow-brand-600/30">
                    Get Started Free — 14-Day Trial
                </a>
                <a href="#pipeline" class="w-full sm:w-auto px-8 py-4 rounded-xl text-sm font-bold bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-all">
                    Explore Pipeline Demo
                </a>
            </div>

            <!-- Social Proof Bar -->
            <div class="mt-16 pt-10 border-t border-slate-800/80 max-w-5xl mx-auto">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Trusted by modern logistics & field operation teams</p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-8 sm:gap-16 opacity-60 grayscale hover:grayscale-0 transition-all">
                    <span class="text-lg font-bold tracking-wider text-slate-400">APEX LOGISTICS</span>
                    <span class="text-lg font-bold tracking-wider text-slate-400">VANGUARD FIELD</span>
                    <span class="text-lg font-bold tracking-wider text-slate-400">ROUTEKIND</span>
                    <span class="text-lg font-bold tracking-wider text-slate-400">NEXTFLOW</span>
                </div>
            </div>

        </div>
    </section>

    <!-- Visual Interactive Pipeline Preview Section -->
    <section id="pipeline" class="py-20 bg-slate-950/50 border-y border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-xs font-bold text-brand-500 uppercase tracking-widest mb-2">Automated Workflow</h2>
                <p class="text-3xl sm:text-4xl font-bold text-white tracking-tight">How METRO Routes Work Orders</p>
                <p class="mt-4 text-slate-400 text-sm sm:text-base">Move work from initial intake to final billing with zero manual hand-offs.</p>
            </div>

            <!-- Stage Steps Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                
                <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-brand-500/50 transition-all relative">
                    <div class="text-brand-500 font-extrabold text-2xl mb-4">01</div>
                    <h3 class="text-lg font-bold text-white mb-2">Intake & Scheduling</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Work tickets are ingested, auto-tagged with SLAs, and prioritized instantly.</p>
                </div>

                <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-brand-500/50 transition-all relative">
                    <div class="text-brand-500 font-extrabold text-2xl mb-4">02</div>
                    <h3 class="text-lg font-bold text-white mb-2">Field Dispatch</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Smart routing sends tickets to nearby crews via mobile app with full job specs.</p>
                </div>

                <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-brand-500/50 transition-all relative">
                    <div class="text-brand-500 font-extrabold text-2xl mb-4">03</div>
                    <h3 class="text-lg font-bold text-white mb-2">Quality Gate</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">On-site photos, signatures, and telemetry are verified by automated checks.</p>
                </div>

                <div class="bg-slate-900 p-6 rounded-2xl border border-slate-800 hover:border-brand-500/50 transition-all relative">
                    <div class="text-brand-500 font-extrabold text-2xl mb-4">04</div>
                    <h3 class="text-lg font-bold text-white mb-2">Closeout & Invoice</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Sign-offs trigger immediate invoice generation and customer confirmation.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- Feature Grid Section -->
    <section id="features" class="py-20 md:py-28">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-xs font-bold text-brand-500 uppercase tracking-widest mb-2">Built for Scale</h2>
                <p class="text-3xl sm:text-4xl font-bold text-white tracking-tight">Features Built for High-Volume Operations</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                
                <div class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800">
                    <div class="w-12 h-12 rounded-2xl bg-brand-500/10 border border-brand-500/20 text-brand-400 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Live Telemetry & GPS</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">Track crews, equipment assets, and delivery states on a live unified operational map.</p>
                </div>

                <div class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">SLA Guardrails</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">Automatic alerts highlight jobs nearing SLA limits, protecting contract performance scores.</p>
                </div>

                <div class="bg-slate-900/60 p-8 rounded-3xl border border-slate-800">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Instant ERP Sync</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">Seamlessly feed pipeline completions into SAP, Oracle, QuickBooks, or custom Webhooks.</p>
                </div>

            </div>

        </div>
    </section>

    <!-- Bottom CTA Banner -->
    <section class="py-16 bg-gradient-to-r from-brand-700 to-brand-600 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight">Ready to streamline your operations?</h2>
            <p class="mt-4 text-brand-100 text-base sm:text-lg max-w-xl mx-auto">Set up your pipeline rules in under 10 minutes. No credit card required.</p>
            <div class="mt-8">
                <a href="/register" class="inline-block px-8 py-4 rounded-xl text-sm font-bold bg-slate-900 hover:bg-slate-800 text-white transition-all shadow-2xl">
                    Start Your 14-Day Free Trial
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-800 py-12 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-200 text-sm">METRO</span>
                <span>© {{ date('Y') }} METRO Pipeline Ops, Inc. All rights reserved.</span>
            </div>
            <div class="flex items-center gap-6">
                <a href="#" class="hover:text-slate-300 transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-slate-300 transition-colors">Terms of Service</a>
                <a href="#" class="hover:text-slate-300 transition-colors">Security</a>
            </div>
        </div>
    </footer>

</body>
</html>