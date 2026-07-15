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
                Project Module
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

        <!-- Bidding Docs -->
        <a href="{{ route('bidding.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('bidding.index') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('bidding.index'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('bidding.index') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125a1.125 1.125 0 ００１．１２５－１．１２５V５．６２５A１．１２５ １．１２５ ０ ０１５％６２５４％５z"></path>
            </svg>
            <span>Bidding Docs</span>
        </a>

        <!-- Project List -->
        <a href="{{ route('projects.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-₁₅₀ relative overflow-hidden
           {{ request()->routeIs('projects.index') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-₁₀₀/8₀' }}">
            @if(request()->routeIs('projects.index'))
                <div class="absolute left-₀ top-₂ bottom-₂ w-[₃px] bg-blue-6₀ rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('projects.index') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0A2.25 2.25 0 004.5 15h15a2.25 2.25 0 002.25-2.25m-19.5 0v.25A2.25 2.25 0 004.5 18h15a2.25 2.25 0 002.25-2.25v-.25m-19.5 0V9M21.75 12V9M2.25 9l1.652-3.303A2.25 2.25 0 015.896 4.5h12.208a2.25 2.25 0 011.996 1.197L21.75 9"></path>
            </svg>
            <span>Project List</span>
        </a>

        <!-- Tracking Deliveries -->
        <a href="{{ route('deliveries.tracking') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('deliveries.tracking') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('deliveries.tracking'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('deliveries.tracking') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125a1.125 1.125 0 001.125-1.125V9.75M16.5 18.75a1.5 1.5 0 000-3 1.5 1.5 0 000 3zm0 0h1.5m-13.5-4.5h16.5M5.625 4.5h11.114a1.13 1.13 0 01.8.326l2.091 2.092a1.13 1.13 0 01.326.8v3.207a1.13 1.13 0 01-.326.8l-2.092 2.091a1.13 1.13 0 01-.8.326H5.625a1.125 1.125 0 01-1.125-1.125V5.625A1.125 1.125 0 015.625 4.5z"></path>
            </svg>
            <span>Tracking Deliveries</span>
        </a>

        <!-- Item List -->
        <a href="{{ route('items.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('items.index') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('items.index'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('items.index') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5.25A2.25 2.25 0 0111.25 3h1.5A2.25 2.25 0 0115 5.25V6h2.25A2.25 2.25 0 0119.5 8.25v10.5A2.25 2.25 0 0117.25 21H6.75A2.25 2.25 0 014.5 18.75V8.25A2.25 2.25 0 016.75 6H9v-.75zM8.25 10.5h7.5m-7.5 3h7.5m-7.5 3h4.5"/>
            </svg>
            <span>Item List</span>
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
                    <a href="{{ route('inventory.index') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors
                    {{ request()->routeIs('inventory.index') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-3.5 h-3.5 shrink-0 transition-colors {{ request()->routeIs('inventory.index') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Inventory List</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('inventory.summary') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors
                    {{ request()->routeIs('inventory.summary') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-3.5 h-3.5 shrink-0 transition-colors {{ request()->routeIs('inventory.summary') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                        <span>Inventory Summary</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('inventory.history') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors
                    {{ request()->routeIs('inventory.history') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-3.5 h-3.5 shrink-0 transition-colors {{ request()->routeIs('inventory.history') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Inventory History</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Automated PDF Data Population -->
        <a href="{{ route('school.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('school.index') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('school.index'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('school.index') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v4.125A2.625 2.625 0 0116.875 21H7.125A2.625 2.625 0 014.5 18.375V5.625A2.625 2.625 0 017.125 3h6.22a2.625 2.625 0 011.856.769l3.03 3.03a2.625 2.625 0 01.769 1.856V9.75"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m0 0l-2.25-2.25M12 16.5l2.25-2.25"/>
            </svg>
            <span>Automated PDF Population</span>
        </a>

        <!-- Logistics Channel -->
        <a href="#"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80 transition-all duration-150">
            <svg class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25A7.5 7.5 0 1119.5 10.5z"></path>
            </svg>
            <span>Logistics Channel</span>
        </a>

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