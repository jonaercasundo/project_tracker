<div class="w-72 h-full flex flex-col bg-white border-r border-slate-100 select-none">

    {{-- Brand --}}
    <div class="h-16 flex items-center px-6 border-b border-slate-100">

        <div class="flex items-center gap-3">

            <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center shadow-md shadow-emerald-500/20">

                📦

            </div>

            <div>

                <div class="font-extrabold text-[12px] tracking-wider uppercase text-slate-900">

                    Warehouse Module

                </div>

                <div class="text-[10px] text-slate-400">

                    Inventory Management

                </div>

            </div>

        </div>

    </div>


    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-4 py-4 space-y-1">

        {{-- Dashboard --}}
        <a href="{{ route('warehouse.dashboard') }}"
           class="group flex items-center gap-3 whitespace-nowrap px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('warehouse.dashboard') ? 'bg-emerald-50 text-emerald-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">

            @if(request()->routeIs('warehouse.dashboard'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-emerald-600 rounded-r-md"></div>
            @endif

            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M3 12l9-9 9 9M4.5 10.5V21h15V10.5"/>
            </svg>

            Dashboard

        </a>


        {{-- Inventory --}}
        <div
            x-data="{ open: {{ request()->routeIs('warehouse.inventory.*')
                || request()->routeIs('warehouse.packages.*')
                || request()->routeIs('warehouse.categories.*')
                || request()->routeIs('warehouse.adjustments.*')
                    ? 'true' : 'false' }} ">

            <button
                @click="open=!open"
                class="w-full flex items-center justify-between whitespace-nowrap px-4 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-50">

                <div class="flex items-center gap-3">

                    📦

                    <span>Inventory</span>

                </div>

                <svg class="w-4 h-4 transition-transform"
                     :class="open ? 'rotate-90' : ''"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M9 5l7 7-7 7"/>

                </svg>

            </button>

            <ul
                x-show="open"
                x-collapse
                class="ml-6 mt-1 space-y-1 border-l border-slate-200 pl-4">

                <li>

                    <a href="{{ route('warehouse.dashboard') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-bold hover:bg-slate-50">

                        Inventory List

                    </a>

                </li>

                <li>

                    <a href="{{ route('warehouse.packages.index') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-bold hover:bg-slate-50">

                        Packages

                    </a>

                </li>

                <li>

                    <a href="{{ route('warehouse.categories.index') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-bold hover:bg-slate-50">

                        Categories

                    </a>

                </li>

                <li>

                    <a href="{{ route('warehouse.adjustments.index') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-bold hover:bg-slate-50">

                        Stock Adjustments

                    </a>

                </li>

            </ul>

        </div>


        {{-- Warehouse Operations --}}
        <div
            x-data="{ open: {{ request()->routeIs('warehouse.stock*') ? 'true' : 'false' }} }">

            <button
                @click="open=!open"
                class="w-full flex items-center justify-between px-4 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-50">

                <div class="flex items-center gap-3">

                    🏭

                    <span>Warehouse Operations</span>

                </div>

                <svg class="w-4 h-4 transition-transform"
                     :class="open ? 'rotate-90' : ''"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     viewBox="0 0 24 24">

                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M9 5l7 7-7 7"/>

                </svg>

            </button>

            <ul
                x-show="open"
                x-collapse
                class="ml-6 mt-1 space-y-1 border-l border-slate-200 pl-4">

                <li>
                    <a href="{{ route('warehouse.stock-in') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-bold hover:bg-slate-50">

                        Stock In

                    </a>
                </li>

                <li>
                    <a href="{{ route('warehouse.stock-out') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-bold hover:bg-slate-50">

                        Stock Out

                    </a>
                </li>

                <li>
                    <a href="{{ route('warehouse.transfer') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-bold hover:bg-slate-50">

                        Transfer

                    </a>
                </li>

                <li>
                    <a href="{{ route('warehouse.returns') }}"
                       class="block rounded-lg px-3 py-2 text-xs font-bold hover:bg-slate-50">

                        Returns

                    </a>
                </li>

            </ul>

        </div>


        {{-- Reports --}}
        <a href="{{ route('warehouse.history') }}"
           class="group flex items-center gap-3 whitespace-nowrap px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50">

            📊

            Inventory History

        </a>

        <a href="{{ route('warehouse.transactions') }}"
           class="group flex items-center gap-3 whitespace-nowrap  px-4 py-2.5 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50">

            📑

            Transaction History

        </a>

    </nav>


    {{-- User Footer --}}
    <div class="p-3 border-t border-slate-100 bg-slate-50">

        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-3 p-2 rounded-xl hover:bg-white">

            <div
                class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-xs font-bold">

                {{ fallback_avatar_initials(Auth::user()->name) }}

            </div>

            <div>

                <div class="text-[11px] font-bold">

                    {{ Auth::user()->name }}

                </div>

                <div class="text-[10px] text-slate-400">

                    {{ Auth::user()->email }}

                </div>

            </div>

        </a>

        <form method="POST" action="{{ route('logout') }}" class="mt-2">

            @csrf

            <button
                class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold text-red-600 hover:bg-red-50">

                🚪 Logout

            </button>

        </form>

    </div>

    {{-- Version --}}
    <div class="border-t border-slate-100 bg-slate-50 p-4 flex justify-between">

        <span class="text-[9px] uppercase text-slate-400 font-bold">

            Warehouse

        </span>

        <span class="text-[9px] font-mono bg-slate-200 px-2 py-1 rounded">

            v1.0.0

        </span>

    </div>

</div>