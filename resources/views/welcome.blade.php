<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>METRO — The All-in-One Business Operating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="METRO unifies your projects, crews, and operational workflows into a single, intuitive real-time workspace.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#F4F7FF',
                            100: '#E8EFFF',
                            500: '#3B82F6',
                            600: '#2563EB',
                            900: '#0F172A',
                        },
                        surface: '#FAFAFA',
                    }
                }
            }
        }
    </script>
    <style>
        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.001ms !important; animation-iteration-count: 1 !important; transition-duration: 0.001ms !important; }
        }
    </style>
</head>

<body class="bg-surface text-slate-900 antialiased font-sans min-h-full flex flex-col justify-between selection:bg-brand-500 selection:text-white">

<!-- Header -->
<header class="bg-surface/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200/60">
    <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
        <a href="/" class="flex items-center gap-2.5 group">
            <div class="w-9 h-9 bg-brand-900 rounded-xl flex items-center justify-center text-white shadow-sm group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <span class="text-xl font-bold tracking-tight text-slate-900">METRO</span>
        </a>

        <div class="flex items-center gap-4">
            @auth
                <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}"
                   class="inline-flex items-center justify-center px-5 py-2.5 rounded-full font-semibold text-sm bg-slate-900 text-white hover:bg-slate-800 transition-all duration-200 shadow-sm hover:shadow">
                    Dashboard
                </a>
            @else
                <a href="/login" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition-colors px-3 py-2">
                    Sign In
                </a>
                <a href="/register"
                   class="inline-flex items-center justify-center px-5 py-2.5 rounded-full font-semibold text-sm bg-brand-600 text-white hover:bg-brand-500 transition-all duration-200 shadow-sm hover:shadow-md">
                    Get Started Free
                </a>
            @endauth
        </div>
    </div>
</header>

<main class="flex-grow">

    <!-- Hero Section -->
    <section class="relative overflow-hidden pt-20 pb-24 md:pt-28 md:pb-32">
        <!-- Minimal Ambient Gradient Glow -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-gradient-to-tr from-brand-500/10 via-blue-400/10 to-indigo-500/10 blur-[120px] rounded-full pointer-events-none -z-10"></div>

        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-50 border border-brand-100 text-brand-600 text-xs font-semibold tracking-wide uppercase mb-8">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                The Modern Business OS
            </div>

            <h1 class="text-4xl sm:text-6xl md:text-7xl font-bold tracking-tight text-slate-900 max-w-4xl mx-auto leading-[1.08]">
                Run your entire operations on one platform.
            </h1>
            
            <p class="mt-6 text-lg md:text-xl text-slate-600 font-body max-w-2xl mx-auto leading-relaxed">
                METRO consolidates project management, team collaboration, and approval workflows into one seamless, real-time operating workspace.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center items-center">
                @auth
                    <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-full font-semibold text-white bg-brand-600 hover:bg-brand-500 shadow-lg shadow-brand-500/20 transition-all duration-200">
                        Go to Dashboard
                    </a>
                @else
                    <a href="/register"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-full font-semibold text-white bg-brand-600 hover:bg-brand-500 shadow-lg shadow-brand-500/20 transition-all duration-200">
                        Start your workspace free
                    </a>
                    <a href="/login"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-full font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors duration-200">
                        Sign in
                    </a>
                @endauth
            </div>

            <!-- Stats Bar -->
            <div class="mt-20 pt-10 border-t border-slate-200/80 max-w-4xl mx-auto grid grid-cols-2 md:grid-cols-4 gap-8">
                <div>
                    <div class="text-3xl font-bold text-slate-900">01</div>
                    <div class="text-xs font-medium text-slate-500 mt-1 uppercase tracking-wider">Unified Platform</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-slate-900">Real-Time</div>
                    <div class="text-xs font-medium text-slate-500 mt-1 uppercase tracking-wider">Field Sync</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-slate-900">3-Tier</div>
                    <div class="text-xs font-medium text-slate-500 mt-1 uppercase tracking-wider">Access Control</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-slate-900">99.9%</div>
                    <div class="text-xs font-medium text-slate-500 mt-1 uppercase tracking-wider">Uptime Reliability</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="max-w-7xl mx-auto px-6 py-20">
        <div class="text-center max-w-2xl mx-auto mb-16">
            <h2 class="text-xs font-semibold text-brand-600 uppercase tracking-widest">Everything You Need</h2>
            <p class="mt-2 text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">Designed for complete clarity.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-8 rounded-2xl border border-slate-200/70 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="w-12 h-12 bg-brand-50 rounded-xl flex items-center justify-center text-brand-600 mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-slate-900 tracking-tight">Project Management</h3>
                <p class="text-slate-600 font-body text-sm mt-2 leading-relaxed">
                    Track milestones, resource distribution, and field logs from a central dashboard with zero configuration friction.
                </p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-slate-200/70 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="w-12 h-12 bg-brand-50 rounded-xl flex items-center justify-center text-brand-600 mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-slate-900 tracking-tight">Role-Based Access</h3>
                <p class="text-slate-600 font-body text-sm mt-2 leading-relaxed">
                    Deliver tailor-made views for admins, managers, and external contractors ensuring high privacy and zero data overlap.
                </p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-slate-200/70 shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="w-12 h-12 bg-brand-50 rounded-xl flex items-center justify-center text-brand-600 mb-6">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="font-bold text-lg text-slate-900 tracking-tight">Instant Updates</h3>
                <p class="text-slate-600 font-body text-sm mt-2 leading-relaxed">
                    Updates recorded on site immediately reflect across administrative views without lag or sheet reconciliation.
                </p>
            </div>
        </div>
    </section>

    <!-- Minimal CTA Box -->
    <section class="max-w-7xl mx-auto px-6 py-12">
        <div class="bg-slate-900 text-white rounded-3xl p-10 md:p-16 flex flex-col md:flex-row items-center justify-between gap-8">
            <div class="max-w-xl text-center md:text-left">
                <h2 class="text-2xl md:text-3xl font-bold tracking-tight">Ready to streamline your workflow?</h2>
                <p class="mt-2 text-slate-400 font-body text-sm md:text-base">Set up your workspace in minutes and connect your entire operation.</p>
            </div>
            @auth
                <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}"
                   class="inline-flex items-center justify-center px-8 py-3.5 rounded-full font-semibold text-slate-900 bg-white hover:bg-slate-100 transition-all duration-200 whitespace-nowrap">
                    Go to Dashboard
                </a>
            @else
                <a href="/register"
                   class="inline-flex items-center justify-center px-8 py-3.5 rounded-full font-semibold text-slate-900 bg-white hover:bg-slate-100 transition-all duration-200 whitespace-nowrap">
                    Create a Free Workspace
                </a>
            @endauth
        </div>
    </section>

</main>

<!-- Footer -->
<footer class="border-t border-slate-200/80 bg-surface mt-12">
    <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-body text-slate-500">
        <div>Metro-Mobilia Corporation &copy; {{ date('Y') }}</div>
        <div class="flex gap-6">
            <a href="#" class="hover:text-slate-900 transition-colors">Privacy Policy</a>
            <a href="#" class="hover:text-slate-900 transition-colors">Terms of Service</a>
            <a href="#" class="hover:text-slate-900 transition-colors">Support</a>
        </div>
    </div>
</footer>

</body>
</html>