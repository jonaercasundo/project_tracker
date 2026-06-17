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
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
                letter-spacing: -0.01em;
            }
            /* Custom glassmorphism refinement */
            .glass-header {
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
            }
        </style>
    </head>
    <body class="antialiased bg-slate-50 text-slate-800 min-h-full flex flex-col relative selection:bg-blue-600 selection:text-white">
        
        <div class="absolute inset-0 overflow-hidden pointer-events-none -z-50">
            <div class="absolute -top-[40%] -left-[20%] w-[80rem] h-[80rem] rounded-full bg-gradient-to-tr from-blue-200/20 to-indigo-100/10 blur-[128px]"></div>
            <div class="absolute top-[20%] -right-[30%] w-[60rem] h-[60rem] rounded-full bg-gradient-to-br from-slate-200/40 to-blue-100/20 blur-[96px]"></div>
        </div>

        <div class="min-h-screen flex flex-col relative">
            
            <div class="relative z-50 shadow-sm shadow-slate-100/50">
                @include('layouts.navigation')
            </div>

            @isset($header)
                <header class="glass-header bg-white/75 sticky top-0 z-40 border-b border-slate-200/60 transition-all duration-300 dynamic-header">
                    <div class="max-w-7xl mx-auto py-4.5 px-6 sm:px-8 lg:px-12 flex items-center justify-between">
                        <div class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl transition-all duration-300">
                            {{ $header }}
                        </div>
                        
                        @isset($headerActions)
                            <div class="flex items-center gap-3">
                                {{ $headerActions }}
                            </div>
                        @endisset
                    </div>
                </header>
            @endisset

            <main class="flex-grow w-full max-w-7xl mx-auto px-4 py-6 sm:px-8 sm:py-8 lg:px-12 lg:py-10 transition-all duration-300">
                
                <div class="animate-fadeIn">
                    {{ $slot }}
                </div>
                
            </main>

            <footer class="mt-auto border-t border-slate-200/50 bg-white/40 backdrop-blur-sm py-4 text-center text-xs font-medium text-slate-400">
                <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-12 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p>&copy; {{ date('Y') }} Metro-Mobilia Corporation. All rights reserved.</p>
                    <p class="text-slate-300">MMC Tracker v2.1.0</p>
                </div>
            </footer>
        </div>

        <script>
            // Shrink header slightly when scrolling down for better dashboard view room
            window.addEventListener('scroll', () => {
                const header = document.querySelector('.dynamic-header');
                if (header) {
                    if (window.scrollY > 20) {
                        header.classList.remove('py-4.5');
                        header.classList.add('py-3', 'shadow-sm', 'bg-white/90');
                    } else {
                        header.classList.remove('py-3', 'shadow-sm', 'bg-white/90');
                        header.classList.add('py-4.5');
                    }
                }
            });
        </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>