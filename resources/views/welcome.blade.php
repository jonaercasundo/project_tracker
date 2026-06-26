<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <title>MMC Project Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>

<body class="bg-slate-50 text-slate-800 antialiased font-sans min-h-full flex flex-col justify-between">

<header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-200/80 transition-all">
    <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
        <div class="flex items-center gap-3 group">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75c.621 0 1.125.504 1.125 1.125v1.875c0 .621-.504 1.125-1.125 1.125H5.625a1.125 1.125 0 0 1-1.125-1.125V5.625c0-.621.504-1.125 1.125-1.125Z" />
                </svg>
            </div>
            <span class="text-xl font-bold tracking-tight text-slate-900">MMC <span class="text-blue-600 font-medium">Tracker</span></span>
        </div>

        <div>
            @auth
                <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}"
                class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl font-semibold text-sm bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors duration-200">
                    Go to Dashboard
                </a>
            @else
                <a href="/login" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl font-semibold text-sm bg-blue-600 text-white shadow-sm hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-600/20 transition-all duration-200">
                    Sign In
                </a>
            @endauth
        </div>
    </div>
</header>

<main class="flex-grow">
    <section class="relative overflow-hidden pt-24 pb-20 lg:pt-32 lg:pb-28">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 bg-gradient-to-b from-blue-50/50 to-transparent -z-10 pointer-events-none rounded-full blur-3xl"></div>
        
        <div class="max-w-4xl mx-auto text-center px-6">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 mb-6 border border-blue-100">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                Enterprise Ready
            </span>
            
            <h2 class="text-4xl sm:text-5xl md:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.15]">
                Welcome to <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">MMC Project Tracker</span>
            </h2>

            <p class="mt-6 text-lg md:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed">
                Manage high-scale construction and internal workflows. Track progress, assign responsibilities, and monitor operations in real-time.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row justify-center items-center gap-4">
                @auth
                    <a href="{{ \App\Services\DashboardService::route(auth()->user()) }}"
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl font-semibold text-sm bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors duration-200">
                        Go to Dashboard
                    </a>
                @else
                    <a href="/register" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-xl font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-xl shadow-blue-600/20 hover:shadow-blue-600/30 hover:-translate-y-0.5 transition-all duration-200">
                        Get Started
                    </a>
                    <a href="/login" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-4 rounded-xl font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 transition-colors duration-200">
                        Learn More
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid md:grid-cols-3 gap-8">
            
            <div class="bg-white p-8 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all duration-200 group">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 mb-6 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12.75c.621 0 1.125.504 1.125 1.125v13.5c0 .621-.504 1.125-1.125 1.125H3V3Z" />
                    </svg>
                </div>
                <h3 class="font-bold text-xl text-slate-900 tracking-tight">Project Management</h3>
                <p class="text-slate-600 mt-3 leading-relaxed">
                    Track blueprint updates, field metrics, and internal projects seamlessly within a single environment.
                </p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all duration-200 group">
                <div class="w-12 h-12 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 mb-6 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286Z" />
                    </svg>
                </div>
                <h3 class="font-bold text-xl text-slate-900 tracking-tight">Role-Based Access</h3>
                <p class="text-slate-600 mt-3 leading-relaxed">
                    Secure your platform natively. Administrators, engineers, and sub-contractors interact with tailored visual access modules.
                </p>
            </div>

            <div class="bg-white p-8 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md hover:border-slate-300/80 transition-all duration-200 group">
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 mb-6 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                    </svg>
                </div>
                <h3 class="font-bold text-xl text-slate-900 tracking-tight">Real-Time Tracking</h3>
                <p class="text-slate-600 mt-3 leading-relaxed">
                    Eliminate project delays. Streamline status milestones, live task feeds, and automated updates synchronously.
                </p>
            </div>

        </div>
    </section>
</main>

<footer class="border-t border-slate-200 bg-white">
    <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500">
        <div>
            Metro-Mobilia Corporation &copy; {{ date('Y') }}
        </div>
        <div class="flex gap-6">
            <a href="#" class="hover:text-slate-800 transition-colors">Privacy Policy</a>
            <a href="#" class="hover:text-slate-800 transition-colors">Terms of Service</a>
            <a href="#" class="hover:text-slate-800 transition-colors">Support Contact</a>
        </div>
    </div>
</footer>

</body>
</html>