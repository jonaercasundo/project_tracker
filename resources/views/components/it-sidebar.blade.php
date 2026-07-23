<div class="h-full flex flex-col bg-white border-r border-slate-100 select-none">

    <!-- Header / Brand Section -->
    <div class="h-16 flex items-center px-6 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center shadow-md shadow-blue-500/10">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-10.5v10.5"></path>
                </svg>
            </div>
            <div class="font-extrabold text-[12px] text-slate-900 tracking-wider uppercase">
                IT Module
            </div>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">

        <!-- Dashboard Overview -->
        <a href="{{ route('projects.dashboard') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('projects.dashboard') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('projects.dashboard'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('projects.dashboard') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"></path>
            </svg>
            <span>Dashboard Overview</span>
        </a>
        <!-- Inventory Dropdown Module -->
        <div x-data="{ open: {{ request()->routeIs('inventory.*') ? 'true' : 'false' }} }" class="block">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none
                    {{ request()->routeIs('inventory.*') ? 'bg-slate-50 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 shrink-0 transition-colors {{ request()->routeIs('inventory.*') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' }}" 
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor" 
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                    <span>Inventory</span>
                </div>
                <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <ul x-show="open" 
                x-collapse 
                class="relative mt-1 ml-6 pl-4 space-y-0.5 before:content-[''] before:absolute before:left-0 before:top-1 before:bottom-1 before:w-px before:bg-slate-200">
                
                <li>
                    <a href="{{ route('it.asset.index') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors
                    {{ request()->routeIs('it.asset.index') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-3.5 h-3.5 shrink-0 transition-colors {{ request()->routeIs('it.asset.index') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Asset Accountability Form</span>
                    </a>
                </li> 
            </ul>
        </div>
    </nav>

    <!-- Account Identity Panel Footer -->
    <div class="p-3 border-t border-slate-100 bg-slate-50/40 space-y-1">
        
        <a href="{{ route('profile.edit') }}" 
           class="group flex items-center gap-3 p-2 rounded-xl transition-all duration-150 hover:bg-slate-50 active:bg-slate-100">
            <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 font-bold text-xs shrink-0 tracking-tight group-hover:border-slate-300 transition-colors">
                {{ fallback_avatar_initials(Auth::user()->name ?? 'User') }}
            </div>
            <div class="flex-grow min-w-0 select-none">
                <div class="text-[11px] font-bold text-slate-800 truncate leading-none">
                    {{ Auth::user()->name ?? 'System Identity' }}
                </div>
                <div class="text-[10px] text-slate-400 truncate mt-1 font-medium leading-none">
                    {{ Auth::user()->email ?? 'session@metro-mobilia.local' }}
                </div>
            </div>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" 
                    class="group w-full flex items-center gap-3 px-3 py-2 text-slate-500 hover:text-red-600 hover:bg-red-50/60 active:bg-red-100/50 rounded-xl text-xs font-bold transition-all duration-150 text-left focus:outline-none">
                <svg class="w-4 h-4 text-slate-400 group-hover:text-red-500 transition-colors shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"></path>
                </svg>
                <span>Terminate Session</span>
            </button>
        </form>
    </div>

    <!-- Core System Specifications -->
    <div class="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/60">
        <span class="text-[9px] font-bold text-slate-400 tracking-wider uppercase">Core System</span>
        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-mono font-bold bg-slate-200/60 text-slate-600">v2.1.0</span>
    </div>

</div>