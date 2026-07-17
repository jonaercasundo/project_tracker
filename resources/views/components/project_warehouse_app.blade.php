<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'MMC Warehouse') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    @vite(['resources/css/app.css','resources/js/app.js'])

    <style>

        body{
            font-family:'Plus Jakarta Sans',sans-serif;
            letter-spacing:-0.01em;
        }

        [x-cloak]{
            display:none!important;
        }

        .glass-header{
            backdrop-filter:blur(16px) saturate(180%);
            -webkit-backdrop-filter:blur(16px) saturate(180%);
        }

    </style>

</head>

<body class="antialiased bg-slate-50 text-slate-800 min-h-full relative selection:bg-blue-600 selection:text-white">

    {{-- Background --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none -z-50">

        <div class="absolute -top-[40%] -left-[20%] w-[80rem] h-[80rem] rounded-full bg-gradient-to-tr from-blue-200/20 to-indigo-100/10 blur-[128px]"></div>

        <div class="absolute top-[20%] -right-[30%] w-[60rem] h-[60rem] rounded-full bg-gradient-to-br from-slate-200/40 to-blue-100/20 blur-[96px]"></div>

    </div>

    <div class="min-h-screen flex">

        {{-- Sidebar --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-slate-200 shadow-sm overflow-y-auto">

            @include('components.project-warehouse-sidebar')

        </aside>

        {{-- Content --}}
        <div class="flex-1 ml-72 flex flex-col">

            @isset($header)

                <header class="glass-header sticky top-0 z-40 bg-white/75 border-b border-slate-200 dynamic-header py-4">

                    <div class="max-w-7xl mx-auto px-6 flex items-center justify-between">

                        <div class="text-lg font-bold text-slate-900">

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

            <main class="flex-1">

                <div class="max-w-7xl mx-auto px-6 py-6">

                    {{ $slot }}

                </div>

            </main>

            <footer class="border-t border-slate-200 bg-white/40 py-4 text-center text-xs text-slate-400">

                <div class="max-w-7xl mx-auto flex items-center justify-between px-6">

                    <span>

                        © {{ date('Y') }} Metro Mobilia Corporation

                    </span>

                    <span class="font-mono">

                        Warehouse Management v1.0

                    </span>

                </div>

            </footer>

        </div>

    </div>

    <script>

        window.addEventListener('scroll',()=>{

            const header=document.querySelector('.dynamic-header');

            if(!header) return;

            if(window.scrollY>20){

                header.classList.remove('py-4');

                header.classList.add('py-2.5','shadow-sm','bg-white/90');

            }else{

                header.classList.remove('py-2.5','shadow-sm','bg-white/90');

                header.classList.add('py-4');

            }

        });

    </script>

    @stack('scripts')

</body>

</html>