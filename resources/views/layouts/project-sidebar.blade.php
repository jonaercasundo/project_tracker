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
    <!-- Current Company -->
    @php
        $currentUser = Auth::user();
        $currentCompany = $currentUser?->currentCompany();
        $companyId = $currentCompany?->company_id;
    @endphp

    @if($currentUser && $currentUser->companies->count() > 0)

        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/40">

            <div class="flex items-center gap-2 mb-2">

                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 21h18M5 21V7a2 2 0 012-2h10a2 2 0 012 2v14M9 9h1m-1 4h1m4-4h1m-1 4h1M9 21v-4h6v4"
                        />
                    </svg>
                </div>

                <div class="min-w-0">
                    <div class="text-[9px] uppercase tracking-wider font-bold text-slate-400">
                        Current Company
                    </div>
                </div>

            </div>

            <form
                method="POST"
                action="{{ route('company.switch') }}"
            >
                @csrf

                <select
                    name="company_id"
                    onchange="this.form.submit()"
                    class="w-full text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-2 pr-8 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10 cursor-pointer"
                >

                    @foreach($currentUser->companies->where('is_active', true) as $company)

                        <option
                            value="{{ $company->company_id }}"
                            @selected($companyId == $company->company_id)
                        >
                            {{ $company->name }}
                        </option>

                    @endforeach

                </select>

            </form>

        </div>

    @endif
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

        <!-- Billing Document Status -->
        <a href="{{ route('projects.reports.analytics') }}"
           @if(request()->routeIs('projects.reports.analytics')) aria-current="page" @endif
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden {{ request()->routeIs('projects.reports.analytics') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('projects.reports.analytics'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('projects.reports.analytics') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5h15M6.75 16.5V12m5.25 4.5V7.5m5.25 9V10.5" />
            </svg>
            <span class="min-w-0 whitespace-normal leading-4">Billing Document Status</span>
        </a>

        <!-- Bidding Docs -->
        <a href="{{ route('project.bidding.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('project.bidding.index') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('project.bidding.index'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('project.bidding.index') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125a1.125 1.125 0 ００１．１２５－１．１２５V５．６２５A１．１２５ １．１２５ ０ ０１５％６２５４％５z"></path>
            </svg>
            <span>Bidding Docs</span>
        </a>

        <!-- Project List -->
        <div class="px-4 pt-4 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Operations</div>
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

        <a href="{{ route('operations.lifecycle.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden {{ request()->routeIs('operations.lifecycle.*') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
            @if(request()->routeIs('operations.lifecycle.*'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('operations.lifecycle.*') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h7m6-4v7m-3.5-3.5h7" />
            </svg>
            <span>Lifecycle Monitoring</span>
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

        @if($currentUser?->hasAnyRole(['user', 'Administrator']) && $currentCompany?->code === 'MMC' && $currentCompany?->is_active)
            <a href="{{ route('deliveries.monitoring') }}"
               @if(request()->routeIs('deliveries.monitoring')) aria-current="page" @endif
               class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden {{ request()->routeIs('deliveries.monitoring') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                @if(request()->routeIs('deliveries.monitoring'))
                    <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
                @endif
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 14l4-4 4 3 5-7" />
                </svg>
                <span>Delivery Monitoring</span>
            </a>
            <a href="{{ route('deliveries.return-monitoring') }}"
               @if(request()->routeIs('deliveries.return-monitoring')) aria-current="page" @endif
               class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden {{ request()->routeIs('deliveries.return-monitoring') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                @if(request()->routeIs('deliveries.return-monitoring'))
                    <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
                @endif
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('deliveries.return-monitoring') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 14 4 9l5-5M4 9h11a5 5 0 0 1 5 5v1M15 10l5 5-5 5" />
                </svg>
                <span>Return Monitoring</span>
            </a>
        @endif

        @if($currentCompany?->code === 'MMC' && $currentCompany?->is_active)
            @can('delivery.audit.view')
                <a href="{{ route('delivery-audit-trail.index') }}"
                   @if(request()->routeIs('delivery-audit-trail.*')) aria-current="page" @endif
                   class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden {{ request()->routeIs('delivery-audit-trail.*') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                    @if(request()->routeIs('delivery-audit-trail.*'))
                        <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
                    @endif
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" /></svg>
                    <span>Delivery Audit Trail</span>
                </a>
            @endcan
        @endif

        @if($currentCompany?->code === 'MMC' && $currentCompany?->is_active && ($currentUser?->hasRole('Administrator') || $currentUser?->hasRole('user') || $currentUser?->can('billing.qr.scan') || $currentUser?->can('billing.records.view')))
            @php($billingMenuActive = request()->routeIs('billing.dashboard', 'billing.prepare*', 'billing.batches.*', 'billing.records.*', 'billing.scanner*', 'billing.google-sheets-settings*'))
            <div x-data="{ open: {{ $billingMenuActive ? 'true' : 'false' }} }" class="block">
                <button type="button" @click="open = !open" :aria-expanded="open"
                        class="w-full flex items-center justify-between gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 focus:outline-none {{ $billingMenuActive ? 'bg-slate-50 text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                    <span class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0 {{ $billingMenuActive ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6v6h-6zm10.5 0h6v6h-6zm-10.5 10.5h6v6h-6zm10.5 1.5h1.5m3-1.5v1.5m-4.5 3h1.5m3-1.5v1.5m-4.5-4.5h4.5v4.5h-4.5z" />
                        </svg>
                        <span>Billing</span>
                    </span>
                    <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <ul x-show="open" x-collapse class="relative mt-1 ml-6 pl-4 space-y-0.5 before:content-[''] before:absolute before:left-0 before:top-1 before:bottom-1 before:w-px before:bg-slate-200">
                    @can('billing.qr.scan')
                        <li>
                            <a href="{{ route('billing.prepare') }}" @if(request()->routeIs('billing.prepare*')) aria-current="page" @endif
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors {{ request()->routeIs('billing.prepare*') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                <svg class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('billing.prepare*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m-6 4h6m-6 4h6m-9 6h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span>Prepare for Billing</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('billing.scanner') }}" @if(request()->routeIs('billing.scanner*')) aria-current="page" @endif
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors {{ request()->routeIs('billing.scanner*') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                <svg class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('billing.scanner*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75h6v6h-6zm10.5 0h6v6h-6zm-10.5 10.5h6v6h-6zm10.5 1.5h1.5m3-1.5v1.5m-4.5 3h1.5m3-1.5v1.5m-4.5-4.5h4.5v4.5h-4.5z" /></svg>
                                <span>Billing QR Scanner</span>
                            </a>
                        </li>
                    @endcan
                    @can('billing.records.view')
                        <li>
                            <a href="{{ route('billing.batches.index') }}" @if(request()->routeIs('billing.batches.*')) aria-current="page" @endif
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors {{ request()->routeIs('billing.batches.*') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                <svg class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('billing.batches.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                <span>Billing Batches</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('billing.records.index') }}" @if(request()->routeIs('billing.records.*')) aria-current="page" @endif
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors {{ request()->routeIs('billing.records.*') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                <svg class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('billing.records.*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 4h8l4 4v12H4V4h4zm0 0v5h8V4m-8 9h8m-8 4h8" /></svg>
                                <span>Billing History</span>
                            </a>
                        </li>
                    @endcan
                    @if($currentUser?->hasRole('user'))
                        <li>
                            <a href="{{ route('billing.google-sheets-settings') }}" @if(request()->routeIs('billing.google-sheets-settings*')) aria-current="page" @endif
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors {{ request()->routeIs('billing.google-sheets-settings*') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                                <svg class="w-3.5 h-3.5 shrink-0 {{ request()->routeIs('billing.google-sheets-settings*') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4.75h16v14.5H4zM4 9.5h16M9 4.75v14.5m6-14.5v14.5M6 12h1m-1 3h1m10-3h1m-1 3h1" /></svg>
                                <span>Google Sheets Sync</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        <a href="{{ route('catalog-items.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('catalog-items.*') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('catalog-items.*'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('catalog-items.*') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
                    <a href="{{ route('operation_inventory.index') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors
                    {{ request()->routeIs('operation_inventory.index') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-3.5 h-3.5 shrink-0 transition-colors {{ request()->routeIs('operation_inventory.index') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Inventory List</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('inventory.balances') }}"
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-colors
                    {{ request()->routeIs('inventory.balances') ? 'bg-blue-50 text-blue-600' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}">
                        <svg class="w-3.5 h-3.5 shrink-0 transition-colors {{ request()->routeIs('inventory.balances') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h16.5v15h-16.5zM7.5 8.25h3m-3 3.75h3m-3 3.75h3m3-7.5h3m-3 3.75h3m-3 3.75h3" />
                        </svg>
                        <span>Inventories</span>
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

        <a href="{{ route('schools.index') }}"
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden
           {{ request()->routeIs('schools.*') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('schools.*'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('schools.*') ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600 transition-colors' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6M9 10h.01M15 10h.01M12 4V2" />
            </svg>
            <span>School Management</span>
        </a>

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

        @if($currentUser?->hasRole('user') && $currentCompany?->code === 'MMC' && $currentCompany?->is_active)
        <!-- Logistics Channel -->
        <a href="{{ route('logistics-channel.index') }}"
           @if(request()->routeIs('logistics-channel.*')) aria-current="page" @endif
           class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-150 relative overflow-hidden {{ request()->routeIs('logistics-channel.*') ? 'bg-blue-50/80 text-blue-600' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50 active:bg-slate-100/80' }}">
            @if(request()->routeIs('logistics-channel.*'))
                <div class="absolute left-0 top-2 bottom-2 w-[3px] bg-blue-600 rounded-r-md"></div>
            @endif
            <svg class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-slate-600 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25A7.5 7.5 0 1119.5 10.5z"></path>
            </svg>
            <span>Logistics Channel</span>
        </a>
        @endif

        <x-ai-assistant-nav-link />
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
