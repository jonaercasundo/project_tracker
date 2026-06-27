<div class="h-full flex flex-col bg-white">

    {{-- HEADER --}}
    <div class="h-16 flex items-center px-6 border-b border-slate-100 select-none">
        <div class="flex items-center gap-2.5">

            <div class="w-6 h-6 rounded-lg bg-emerald-600 flex items-center justify-center shadow-sm shadow-emerald-500/20">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 1v22m5-18H9.5a3.5 3.5 0 000 7H14.5a3.5 3.5 0 010 7H7"/>
                </svg>
            </div>

            <div class="font-extrabold text-[13px] text-slate-900 tracking-wider uppercase">
                Finance Module
            </div>

        </div>
    </div>

    {{-- NAVIGATION --}}
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">

        {{-- DASHBOARD --}}
        <a href="{{ route('finance.dashboard') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('finance.dashboard') ? 'bg-emerald-50/80 text-emerald-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">

            @if(request()->routeIs('finance.dashboard'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-emerald-600 rounded-r-md"></div>
            @endif

            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('finance.dashboard') ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 13h18M3 6h18M3 20h18"/>
            </svg>

            <span>Finance Dashboard</span>
        </a>

        {{-- PPL FORMS --}}
        <a href="{{ route('ppl_forms.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('ppl_forms.*') ? 'bg-emerald-50/80 text-emerald-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">

            @if(request()->routeIs('ppl_forms.*'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-emerald-600 rounded-r-md"></div>
            @endif

            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('ppl_forms.*') ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
            </svg>

            <span>PPL Forms</span>
        </a>

        {{-- BIDDING DOCUMENT --}}
        <a href="{{ route('bidding.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('bidding_forms.*') ? 'bg-emerald-50/80 text-emerald-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">

            @if(request()->routeIs('bidding_forms.*'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-emerald-600 rounded-r-md"></div>
            @endif

            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('bidding.*') ? 'text-emerald-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M20.25 7.5H3.75A1.5 1.5 0 002.25 9v8.25A1.5 1.5 0 003.75 18.75h16.5A1.5 1.5 0 0021.75 17.25V9A1.5 1.5 0 0020.25 7.5z"/>
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 7.5V6A1.5 1.5 0 0110.5 4.5h3A1.5 1.5 0 0115 6v1.5"/>
            </svg>

            <span>Bidding Document</span>
        </a>

        {{-- PROJECT FINANCIALS --}}
        <a href="#"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 8c-3 0-5 1-5 3s2 3 5 3 5 1 5 3-2 3-5 3m0-16v16"/>
            </svg>
            <span>Project Financials</span>
        </a>

        {{-- REPORTS --}}
        <a href="#"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 17v-6h6v6m-7 4h8a2 2 0 002-2V7a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Reports</span>
        </a>

        {{-- IMPORT --}}
        <a href="#"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:text-slate-900 hover:bg-slate-50">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M4 4v16h16M8 14l3-3 3 3 4-4"/>
            </svg>
            <span>Import Data</span>
        </a>

    </nav>

    {{-- USER SECTION --}}
    <div class="p-3 border-t border-slate-100 bg-slate-50/30 space-y-1">

        <a href="{{ route('profile.edit') }}"
           class="group flex items-center gap-3 p-2 rounded-xl transition-all duration-150 hover:bg-slate-50">

            <div class="w-8 h-8 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 font-bold text-xs">
                {{ fallback_avatar_initials(Auth::user()->name ?? 'User') }}
            </div>

            <div class="flex-grow min-w-0">
                <div class="text-[11px] font-bold text-slate-800 truncate">
                    {{ Auth::user()->name }}
                </div>
                <div class="text-[10px] text-slate-400 truncate">
                    {{ Auth::user()->email }}
                </div>
            </div>

        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-3 px-3 py-2 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl text-xs font-bold">
                <span>Logout</span>
            </button>
        </form>

    </div>

    {{-- FOOTER --}}
    <div class="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
        <span class="text-[10px] font-bold text-slate-400 uppercase">Finance System</span>
        <span class="text-[9px] font-mono bg-slate-200 px-2 py-0.5 rounded">v1.0.0</span>
    </div>

</div>