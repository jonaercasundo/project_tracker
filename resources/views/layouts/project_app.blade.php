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

        <link rel="stylesheet" href="{{ Vite::asset('resources/css/app.css') }}">
        <script src="{{ Vite::asset('resources/js/app.js') }}" defer></script>
        
        <style>
            body {
                font-family: 'Plus Jakarta Sans', sans-serif;
                letter-spacing: -0.01em;
            }
            [x-cloak] { display: none !important; }
            
            /* Custom glassmorphism configuration refinement */
            .glass-header {
                backdrop-filter: blur(16px) saturate(180%);
                -webkit-backdrop-filter: blur(16px) saturate(180%);
            }
        </style>
    </head>
    <body class="antialiased bg-slate-50 text-slate-800 min-h-full relative selection:bg-blue-600 selection:text-white">
        
        {{-- BACKDROP DECORATIVE ORBS --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none -z-50">
            <div class="absolute -top-[40%] -left-[20%] w-[80rem] h-[80rem] rounded-full bg-gradient-to-tr from-blue-200/20 to-indigo-100/10 blur-[128px]"></div>
            <div class="absolute top-[20%] -right-[30%] w-[60rem] h-[60rem] rounded-full bg-gradient-to-br from-slate-200/40 to-blue-100/20 blur-[96px]"></div>
        </div>

        {{-- MAIN LAYOUT ENGINE CONTAINER --}}
        <div class="min-h-screen flex flex-col relative">
            
            <aside class="w-64 bg-white border-r border-slate-200/80 shadow-sm fixed inset-y-0 left-0 z-50 h-screen overflow-y-auto">
                @include('layouts.project-sidebar')
            </aside>

            <div class="flex-1 pl-64 flex flex-col min-h-screen">
                
                @isset($header)
                    <header class="glass-header bg-white/75 sticky top-0 z-40 border-b border-slate-200/60 transition-all duration-300 dynamic-header py-4">
                        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between">
                            <div class="text-base font-bold tracking-tight text-slate-900 sm:text-lg transition-all duration-300 header-title">
                                {{ $header }}
                            </div>
                            
                            @isset($headerActions)
                                <div class="flex items-center gap-2">
                                    {{ $headerActions }}
                                </div>
                            @endisset
                        </div>
                    </header>
                @endisset

                <main class="flex-grow w-full max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8 transition-all duration-300">
                    <div>
                        {{ $slot }}
                    </div>
                </main>

                <footer class="mt-auto border-t border-slate-200/40 bg-white/40 backdrop-blur-sm py-4 text-center text-[11px] font-medium text-slate-400">
                    <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
                        <p>&copy; {{ date('Y') }} Metro-Mobilia Corporation. All rights reserved.</p>
                        <p class="text-slate-300 tracking-wide font-mono">MMC Tracker v2.1.0</p>
                    </div>
                </footer>

            </div>
        </div>

        {{-- LAYOUT EVENT INTERACTIONS INTERCEPTOR --}}
        <script>
            // Shrink header spacing profile dynamically on user container scroll ranges
            window.addEventListener('scroll', () => {
                const header = document.querySelector('.dynamic-header');
                if (header) {
                    if (window.scrollY > 20) {
                        header.classList.remove('py-4');
                        header.classList.add('py-2.5', 'shadow-sm', 'bg-white/90');
                    } else {
                        header.classList.remove('py-2.5', 'shadow-sm', 'bg-white/90');
                        header.classList.add('py-4');
                    }
                }
            });
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </body>
</html>