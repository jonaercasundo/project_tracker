<div class="h-full flex flex-col bg-white">

    <div class="h-16 flex items-center px-6 border-b border-slate-100 select-none">
        <div class="flex items-center gap-2.5">
            <div class="w-6 h-6 rounded-lg bg-blue-600 flex items-center justify-center shadow-sm shadow-blue-500/20">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-10.5v10.5"></path>
                </svg>
            </div>
            <div class="font-extrabold text-[13px] text-slate-900 tracking-wider uppercase">
                Project Module
            </div>
        </div>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">

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

        <a href="{{ route('projects.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('projects.index') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('projects.index'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('projects.index') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-19.5 0A2.25 2.25 0 004.5 15h15a2.25 2.25 0 002.25-2.25m-19.5 0v.25A2.25 2.25 0 004.5 18h15a2.25 2.25 0 002.25-2.25v-.25m-19.5 0V9M21.75 12V9M2.25 9l1.652-3.303A2.25 2.25 0 015.896 4.5h12.208a2.25 2.25 0 011.996 1.197L21.75 9"></path>
            </svg>
            <span>Project List</span>
        </a>

        <a href="{{ route('deliveries.tracking') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('deliveries.tracking') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('deliveries.tracking'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('deliveries.tracking') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125a1.125 1.125 0 001.125-1.125V9.75M16.5 18.75a1.5 1.5 0 000-3 1.5 1.5 0 000 3zm0 0h1.5m-13.5-4.5h16.5M5.625 4.5h11.114a1.13 1.13 0 01.8.326l2.091 2.092a1.13 1.13 0 01.326.8v3.207a1.13 1.13 0 01-.326.8l-2.092 2.091a1.13 1.13 0 01-.8.326H5.625a1.125 1.125 0 01-1.125-1.１２５V5.625A１．１２５ １．１２５ ０ ０１５．６２５ ４．５z"></path>
            </svg>
            <span>Tracking Deliveries</span>
        </a>

        <a href="#"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-₁₀₀/₈₀ transition-all duration-₁₅₀">
            <svg class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="０ ０ ２４ ２４">
                <path stroke-linecap="round" stroke-linejoin="round" d="M₁₃．₅ ₂₁v₋₇．₅a．₇₅．₇₅ ₀ ₀₁．₇₅₋．₇₅h₃a．₇₅．₇₅ ₀ ₀₁．₇₅．₇₅V₂₁m₋₄．₅ ₀H₂．３６m₁₁．₁４ ₀H₁８m₀ ₀h₂．₂伍A₂．₂伍₂．₂伍 ₀ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴏ ᴈ">

        <a href="#"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80 transition-all duration-150">
            <svg class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25A7.5 7.5 0 1119.5 10.5z"></path>
            </svg>
            <span>Logistics Channel</span>
        </a>

    </nav>

    {{-- ACCOUNT IDENTITY CONTROL MODULE --}}
    <div class="p-3 border-t border-slate-100 bg-slate-50/30 space-y-1">
        
        <a href="{{ route('profile.edit') }}" 
           class="group flex items-center gap-3 p-2 rounded-xl transition-all duration-150 hover:bg-slate-50 active:bg-slate-100">
            <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 font-bold text-xs shrink-0 tracking-tight group-hover:border-slate-300 transition-colors">
                {{ fallback_avatar_initials(Auth::user()->name ?? 'User') }}
            </div>
            <div class="flex-grow min-w-0 select-none">
                <div class="text-[11px] font-bold text-slate-800 truncate leading-none">
                    {{ Auth::user()->name ?? 'System Identity' }}
                </div>
                <div class="text-[10px] text-slate-400 truncate mt-0.5 font-medium leading-none">
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

    <div class="p-4 border-t border-slate-100 flex items-center justify-between select-none bg-slate-50/50">
        <span class="text-[10px] font-bold text-slate-400 tracking-wide uppercase">Core System</span>
        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-mono font-bold bg-slate-200/60 text-slate-500">v2.1.0</span>
    </div>

</div>