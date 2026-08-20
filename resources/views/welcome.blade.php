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

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-lg shadow-brand-600/30">
                        Launch App
                    </a>
                @else
                    <a href="/login" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-lg shadow-brand-600/30">
                        Sign In
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
                METRO v2.1 Pipeline Engine Released
            </div>

            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-extrabold tracking-tight text-white leading-[1.1] max-w-4xl mx-auto">
                METRO <span class="bg-gradient-to-r from-brand-500 via-blue-400 to-indigo-400 bg-clip-text text-transparent">Operations Pipeline</span>
            </h1>

            <p class="mt-6 text-base sm:text-xl text-slate-400 max-w-2xl mx-auto font-normal leading-relaxed">
                Connect field teams, operation tracking, and quality approvals into one seamlessly automated operational pipeline. Stop bottlenecks before they start.
            </p>

            <!-- Hero Action CTA -->
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                @auth
                    <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}" class="w-full sm:w-auto px-8 py-4 rounded-xl text-sm font-bold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-xl shadow-brand-600/30">
                        Launch Dashboard
                    </a>
                @else
                    <a href="/login" class="w-full sm:w-auto px-8 py-4 rounded-xl text-sm font-bold bg-brand-600 hover:bg-brand-500 text-white transition-all shadow-xl shadow-brand-600/30 flex items-center justify-center gap-2">
                        <span>Sign In to METRO</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                @endauth
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-800 py-12 text-slate-500 text-xs mt-auto">
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