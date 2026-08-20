<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>METRO — All-in-One Business App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="METRO is the operations sheet for construction and field teams — projects, crews, and approvals on one live plan.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['"Space Grotesk"', 'sans-serif'],
                        sans: ['Inter', 'sans-serif'],
                        mono: ['"Space Mono"', 'monospace'],
                    },
                    colors: {
                        ink: '#0B1E39',
                        blueprint: '#14315C',
                        line: '#4C7EA8',
                        amber: '#F4A100',
                        paper: '#F7F5F0',
                        concrete: '#7C8593',
                    },
                }
            }
        }
    </script>

    <style>
        /* Blueprint grid — the signature texture, used sparingly on dark panels only */
        .grid-sheet {
            background-image:
                linear-gradient(rgba(76,126,168,0.35) 1px, transparent 1px),
                linear-gradient(90deg, rgba(76,126,168,0.35) 1px, transparent 1px),
                linear-gradient(rgba(76,126,168,0.15) 1px, transparent 1px),
                linear-gradient(90deg, rgba(76,126,168,0.15) 1px, transparent 1px);
            background-size: 88px 88px, 88px 88px, 22px 22px, 22px 22px;
            background-position: -1px -1px, -1px -1px, -1px -1px, -1px -1px;
        }
        /* Drafting corner crop marks on sheet cards */
        .sheet-card { position: relative; }
        .sheet-card::before,
        .sheet-card::after,
        .sheet-card .corner-tl,
        .sheet-card .corner-br { content: ''; position: absolute; width: 14px; height: 14px; pointer-events: none; }
        .sheet-card::before { top: -1px; left: -1px; border-top: 2px solid #0B1E39; border-left: 2px solid #0B1E39; }
        .sheet-card::after { bottom: -1px; right: -1px; border-bottom: 2px solid #0B1E39; border-right: 2px solid #0B1E39; }
        .dim-line { border-top: 1px dashed rgba(255,255,255,0.35); }
        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.001ms !important; animation-iteration-count: 1 !important; transition-duration: 0.001ms !important; }
        }
    </style>
</head>

<body class="bg-paper text-ink antialiased font-sans min-h-full flex flex-col justify-between">

<header class="bg-paper/90 backdrop-blur-md sticky top-0 z-50 border-b border-ink/10">
    <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
        <div class="flex items-center gap-3 group">
            <div class="w-10 h-10 bg-ink rounded-sm flex items-center justify-center text-amber shadow-sm group-hover:rotate-3 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M4 21V7l8-4 8 4v14M9 21v-6h6v6" />
                </svg>
            </div>
            <span class="text-lg font-display font-bold tracking-tight text-ink">METRO
                <span class="hidden sm:inline text-concrete font-mono text-[11px] font-normal tracking-widest align-middle ml-1">ALL-IN-ONE BUSINESS APP</span>
            </span>
        </div>

        <div>
            @auth
                <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}"
                   class="inline-flex items-center justify-center px-5 py-2.5 rounded-md font-semibold text-sm bg-ink/5 text-ink hover:bg-ink/10 transition-colors duration-200">
                    Go to Dashboard
                </a>
            @else
                <a href="/login"
                   class="inline-flex items-center justify-center px-5 py-2.5 rounded-md font-semibold text-sm bg-ink text-paper hover:bg-blueprint transition-colors duration-200">
                    Sign In
                </a>
            @endauth
        </div>
    </div>
</header>

<main class="flex-grow">

    <!-- HERO: a live blueprint sheet, not a generic gradient hero -->
    <section class="relative overflow-hidden bg-ink text-paper">
        <div class="grid-sheet absolute inset-0"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-ink/40 via-ink/70 to-ink"></div>

        <div class="relative max-w-7xl mx-auto px-6 pt-16 pb-20 lg:pt-24 lg:pb-28">
            <div class="flex items-center gap-3 font-mono text-xs text-line tracking-widest uppercase mb-8">
                <span class="w-2 h-2 rounded-full bg-amber animate-pulse"></span>
                Sheet 01 / Site Overview
            </div>

            <h1 class="text-4xl sm:text-5xl md:text-6xl font-display font-bold tracking-tight leading-[1.1] max-w-3xl">
                One plan for the job, the crew, and the paperwork.
            </h1>
            <p class="mt-6 text-lg md:text-xl text-slate-300 max-w-2xl leading-relaxed">
                METRO replaces the spreadsheet stack and the group chat with a single working sheet — projects, approvals, and field updates, drawn to scale and always current.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row gap-4">
                @auth
                    <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}"
                       class="inline-flex items-center justify-center px-8 py-4 rounded-md font-bold text-ink bg-amber hover:bg-amber/90 shadow-xl shadow-amber/10 transition-all duration-200">
                        Go to Dashboard
                    </a>
                @else
                    <a href="/register"
                       class="inline-flex items-center justify-center px-8 py-4 rounded-md font-bold text-ink bg-amber hover:bg-amber/90 shadow-xl shadow-amber/10 transition-all duration-200">
                        Start free — create a workspace
                    </a>
                    <a href="/login"
                       class="inline-flex items-center justify-center px-8 py-4 rounded-md font-semibold text-paper border border-white/20 hover:bg-white/5 transition-colors duration-200">
                        Sign in
                    </a>
                @endauth
            </div>

            <!-- Dimension-line stat strip -->
            <div class="dim-line mt-16 pt-6 grid grid-cols-2 sm:grid-cols-4 gap-6 max-w-3xl font-mono text-sm">
                <div>
                    <div class="text-2xl font-display font-bold text-amber">01</div>
                    <div class="text-slate-400 mt-1">Workspace, every crew</div>
                </div>
                <div>
                    <div class="text-2xl font-display font-bold text-amber">24/7</div>
                    <div class="text-slate-400 mt-1">Field-to-office sync</div>
                </div>
                <div>
                    <div class="text-2xl font-display font-bold text-amber">3</div>
                    <div class="text-slate-400 mt-1">Access tiers, no overlap</div>
                </div>
                <div>
                    <div class="text-2xl font-display font-bold text-amber">100%</div>
                    <div class="text-slate-400 mt-1">Status, always current</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES: styled as three sheets from a drawing set -->
    <section class="max-w-7xl mx-auto px-6 py-20 lg:py-28">
        <div class="max-w-2xl mb-14">
            <span class="font-mono text-xs text-line tracking-widest uppercase">Drawing set</span>
            <h2 class="mt-3 text-3xl md:text-4xl font-display font-bold text-ink tracking-tight">Three sheets, one job.</h2>
            <p class="mt-4 text-concrete leading-relaxed">Every workspace ships with these three views. Nothing to configure before your first project goes up.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6">

            <div class="sheet-card bg-white p-8 border border-ink/10 hover:border-ink/30 hover:-translate-y-1 transition-all duration-200">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-11 h-11 bg-blueprint rounded-sm flex items-center justify-center text-paper">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.5a2.25 2.25 0 0 0 2.25 2.25h1.5A2.25 2.25 0 0 0 15 18.75v-1.5m-6 0h6m-6 0-1.5-9m7.5 9 1.5-9m-9 0h9M6 8.25h12" />
                        </svg>
                    </div>
                    <span class="font-mono text-xs text-concrete">A-101</span>
                </div>
                <h3 class="font-display font-bold text-xl text-ink tracking-tight">Project Tracking</h3>
                <p class="text-concrete mt-3 leading-relaxed">
                    Blueprint updates, field metrics, and milestones live in one project record — no separate log to reconcile at end of week.
                </p>
            </div>

            <div class="sheet-card bg-white p-8 border border-ink/10 hover:border-ink/30 hover:-translate-y-1 transition-all duration-200">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-11 h-11 bg-blueprint rounded-sm flex items-center justify-center text-paper">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <span class="font-mono text-xs text-concrete">A-102</span>
                </div>
                <h3 class="font-display font-bold text-xl text-ink tracking-tight">Role-Based Access</h3>
                <p class="text-concrete mt-3 leading-relaxed">
                    Admins, engineers, and subcontractors each get the module they need to do their job — and nothing they don't.
                </p>
            </div>

            <div class="sheet-card bg-white p-8 border border-ink/10 hover:border-ink/30 hover:-translate-y-1 transition-all duration-200">
                <div class="flex items-center justify-between mb-6">
                    <div class="w-11 h-11 bg-blueprint rounded-sm flex items-center justify-center text-paper">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12c0 5.385 4.365 9.75 9.75 9.75s9.75-4.365 9.75-9.75S17.385 2.25 12 2.25 2.25 6.615 2.25 12ZM12 6v6l4 2" />
                        </svg>
                    </div>
                    <span class="font-mono text-xs text-concrete">A-103</span>
                </div>
                <h3 class="font-display font-bold text-xl text-ink tracking-tight">Real-Time Status</h3>
                <p class="text-concrete mt-3 leading-relaxed">
                    A task closed on-site shows as closed in the office within seconds — no delay between the field and the record.
                </p>
            </div>

        </div>
    </section>

    <!-- CTA band -->
    <section class="bg-blueprint text-paper">
        <div class="max-w-7xl mx-auto px-6 py-16 flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <h2 class="text-2xl md:text-3xl font-display font-bold tracking-tight">Ready to put the job on one sheet?</h2>
                <p class="mt-2 text-slate-200">Set up your workspace in a few minutes — no drawings required.</p>
            </div>
            @auth
                <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}"
                   class="inline-flex items-center justify-center px-8 py-4 rounded-md font-bold text-ink bg-amber hover:bg-amber/90 transition-all duration-200 whitespace-nowrap">
                    Go to Dashboard
                </a>
            @else
                <a href="/register"
                   class="inline-flex items-center justify-center px-8 py-4 rounded-md font-bold text-ink bg-amber hover:bg-amber/90 transition-all duration-200 whitespace-nowrap">
                    Create a workspace
                </a>
            @endauth
        </div>
    </section>

</main>

<footer class="border-t border-ink/10 bg-paper">
    <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-concrete font-mono">
        <div>Metro-Mobilia Corporation &copy; {{ date('Y') }}</div>
        <div class="flex gap-6">
            <a href="#" class="hover:text-ink transition-colors">Privacy Policy</a>
            <a href="#" class="hover:text-ink transition-colors">Terms of Service</a>
            <a href="#" class="hover:text-ink transition-colors">Support Contact</a>
        </div>
    </div>
</footer>

</body>
</html>