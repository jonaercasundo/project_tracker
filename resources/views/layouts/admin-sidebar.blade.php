<div x-show="mobileNavOpen" x-cloak @keydown.escape.window="closeMobileNavigation()" class="fixed inset-0 z-50 bg-slate-950/40 md:hidden" @click.self="closeMobileNavigation()">
    <button type="button" class="absolute inset-0 h-full w-full" aria-label="Close navigation" @click="closeMobileNavigation()"></button>
</div>

<aside id="admin-sidebar" :class="[sidebarCollapsed ? 'md:w-[72px]' : 'md:w-[260px]', mobileNavOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0']" class="fixed inset-y-0 left-0 z-[55] flex w-[260px] flex-col border-r border-slate-200/80 bg-white transition-[width,transform] duration-200 motion-reduce:transition-none md:sticky md:top-0 md:h-screen md:shrink-0">
    <div class="flex h-[68px] shrink-0 items-center justify-between border-b border-slate-100 px-4">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3" aria-label="Project Tracker Administrator home">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#123b72] text-white shadow-sm">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5h6l2 3h8v11H4zm4-8h8m-8 4h5"/></svg>
            </span>
            <span x-show="!sidebarCollapsed" x-transition.opacity class="min-w-0 leading-tight">
                <span class="block truncate text-xs font-extrabold tracking-wide text-slate-900">PROJECT TRACKER</span>
                <span class="mt-1 block text-[10px] font-semibold text-slate-400">ADMIN CONTROL CENTER</span>
            </span>
        </a>
        <button type="button" @click="sidebarManuallyToggled = true; sidebarCollapsed = !sidebarCollapsed" class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 md:inline-flex" :aria-label="sidebarCollapsed ? 'Expand navigation' : 'Collapse navigation'" :aria-expanded="(!sidebarCollapsed).toString()">
            <svg class="h-4 w-4 transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m14 5-7 7 7 7m-7-7h13"/></svg>
        </button>
        <button type="button" @click="closeMobileNavigation()" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 md:hidden" aria-label="Close navigation">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <nav x-ref="adminNavigation" aria-label="Administrator navigation" class="flex-1 space-y-4 overflow-y-auto px-3 py-4">
        <a href="{{ route('admin.dashboard') }}" @class([
            'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-bold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500',
            'bg-blue-50 text-blue-800' => request()->routeIs('admin.dashboard'),
            'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('admin.dashboard'),
        ]) title="Dashboard">
            <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12 12 4l9 8M5.5 10v9h13v-9M9.5 19v-5h5v5"/></svg>
            <span x-show="!sidebarCollapsed">Dashboard</span>
        </a>

        <div class="space-y-1">
            <p x-show="!sidebarCollapsed" class="px-3 pb-1 text-[9px] font-extrabold uppercase tracking-[.16em] text-slate-400">User management</p>
            <a href="{{ route('roleaccess.index') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500', 'bg-blue-50 text-blue-800' => request()->routeIs('roleaccess.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('roleaccess.*')]) title="All users and company assignments">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 19h5v-1a4 4 0 0 0-5.4-3.77M16 19H8m8 0v-1a5 5 0 0 0-10 0v1m10-11a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm5 2a3 3 0 1 1-6 0"/></svg>
                <span x-show="!sidebarCollapsed">All users &amp; access</span>
            </a>
            <a href="{{ route('roleaccess.index', ['add' => 1]) }}" class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Add user">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg><span x-show="!sidebarCollapsed">Add user</span>
            </a>
            <a href="{{ route('admin.access.review') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500', 'bg-blue-50 text-blue-800' => request()->routeIs('admin.access.review'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('admin.access.review')]) title="User access review">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5h18v14H3zM7 9h4m-4 4h10m-10 3h7"/></svg><span x-show="!sidebarCollapsed">User access review</span>
            </a>
        </div>

        <div class="space-y-1">
            <p x-show="!sidebarCollapsed" class="px-3 pb-1 text-[9px] font-extrabold uppercase tracking-[.16em] text-slate-400">Roles &amp; permissions</p>
            <a href="{{ route('roles.index') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500', 'bg-blue-50 text-blue-800' => request()->routeIs('roles.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('roles.*')]) title="Role management">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3 20 6v5c0 5-3.4 8.3-8 10-4.6-1.7-8-5-8-10V6l8-3zm-3 9 2 2 4-4"/></svg><span x-show="!sidebarCollapsed">Role management</span>
            </a>
            <a href="{{ route('admin.access.permissions.index') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500', 'bg-blue-50 text-blue-800' => request()->routeIs('admin.access.permissions.index'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('admin.access.permissions.index')]) title="Role permission matrix">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v14H4zM9 5v14m5-14v14M4 10h16m-16 5h16"/></svg><span x-show="!sidebarCollapsed">Permission matrix</span>
            </a>
        </div>

        <div class="space-y-1">
            <p x-show="!sidebarCollapsed" class="px-3 pb-1 text-[9px] font-extrabold uppercase tracking-[.16em] text-slate-400">AI management</p>
            @can('ai.assistant.use')
            <a href="{{ route('admin.ai-management.index') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-violet-500', 'bg-violet-50 text-violet-800' => request()->routeIs('admin.ai-management.*'), 'text-slate-600 hover:bg-violet-50 hover:text-violet-800' => ! request()->routeIs('admin.ai-management.*')]) title="AI overview, tools and usage">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3zm7 12 .8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15z"/></svg><span x-show="!sidebarCollapsed">AI overview &amp; tools</span>
            </a>
            @endcan
            <a href="{{ route('admin.access.permissions.ai') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-violet-500', 'bg-violet-50 text-violet-800' => request()->routeIs('admin.access.permissions.ai'), 'text-slate-600 hover:bg-violet-50 hover:text-violet-800' => ! request()->routeIs('admin.access.permissions.ai')]) title="AI permission matrix">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 9h8m-8 4h5m-8 7 3-3h9a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7a3 3 0 0 0-3 3v13z"/></svg><span x-show="!sidebarCollapsed">AI permissions</span>
            </a>
            @can('ai.settings.manage')
                <a href="{{ route('admin.ai-configuration.index') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-violet-500', 'bg-violet-50 text-violet-800' => request()->routeIs('admin.ai-configuration.*'), 'text-slate-600 hover:bg-violet-50 hover:text-violet-800' => ! request()->routeIs('admin.ai-configuration.*')]) title="OpenAI configuration">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.36-6.36-2.12 2.12M7.76 16.24l-2.12 2.12m12.72 0-2.12-2.12M7.76 7.76 5.64 5.64M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg><span x-show="!sidebarCollapsed">OpenAI configuration</span>
                </a>
            @endcan
        </div>

        <div class="space-y-1">
            <p x-show="!sidebarCollapsed" class="px-3 pb-1 text-[9px] font-extrabold uppercase tracking-[.16em] text-slate-400">Company management</p>
            <a href="{{ route('companies.index') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500', 'bg-blue-50 text-blue-800' => request()->routeIs('companies.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('companies.*')]) title="Companies">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 20h18M5 20V7l7-3 7 3v13M9 10h1m-1 4h1m4-4h1m-1 4h1m-4 6v-4h2v4"/></svg><span x-show="!sidebarCollapsed">Companies</span>
            </a>
            <a href="{{ route('roleaccess.index') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Company users and assignments">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 20V6l8-3 8 3v14M8 9h2m-2 4h2m4-4h2m-2 4h2M9 20v-4h6v4"/></svg><span x-show="!sidebarCollapsed">Company users</span>
            </a>
        </div>

        <div class="space-y-1">
            <p x-show="!sidebarCollapsed" class="px-3 pb-1 text-[9px] font-extrabold uppercase tracking-[.16em] text-slate-400">Integrations</p>
            <a href="{{ route('integrations.jarvis.index') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500', 'bg-blue-50 text-blue-800' => request()->routeIs('integrations.jarvis.*'), 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('integrations.jarvis.*')]) title="JARVIS API token">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 7a5 5 0 0 0-8.6 3.5c0 1 .3 1.9.8 2.7L4 15.5V20h4.5l2-2h2l2-2h2l2-2a5 5 0 0 0-2.5-8.5M16 8h.01"/></svg><span x-show="!sidebarCollapsed">JARVIS API</span>
            </a>
            @if (auth()->user()->currentCompany()?->code === 'MMC' && auth()->user()->hasRole('user'))
                <a href="{{ route('logistics-channel.index') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500" title="MMC webhooks and connectors">
                    <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10v10H7zM3 12h4m10 0h4M12 3v4m0 10v4"/></svg><span x-show="!sidebarCollapsed">MMC webhooks</span>
                </a>
            @endif
        </div>

        <div class="space-y-1">
            <p x-show="!sidebarCollapsed" class="px-3 pb-1 text-[9px] font-extrabold uppercase tracking-[.16em] text-slate-400">Monitoring &amp; security</p>
            <a href="{{ route('admin.dashboard') }}#system-health" class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500" title="System health">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12h4l2-6 4 12 2-6h6"/></svg><span x-show="!sidebarCollapsed">System health</span>
            </a>
            <a href="{{ route('admin.audit.index') }}" @class(['group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500', 'bg-blue-50 text-blue-800' => request()->routeIs('admin.audit.index') && request('event') !== 'user.login', 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! request()->routeIs('admin.audit.index') || request('event') === 'user.login']) title="Audit log">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 4h12v16H6zM9 8h6m-6 4h6m-6 4h3"/></svg><span x-show="!sidebarCollapsed">Audit logs</span>
            </a>
            <a href="{{ route('admin.audit.index', ['event' => 'user.login']) }}" class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Login activity">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14 4h5v16h-5m-3-4 4-4-4-4m4 4H4"/></svg><span x-show="!sidebarCollapsed">Login activity</span>
            </a>
            <a href="{{ route('admin.ai-management.index') }}#failed-requests" class="group flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-semibold text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500" title="Failed AI requests">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3 22 20H2L12 3zm0 6v5m0 3h.01"/></svg><span x-show="!sidebarCollapsed">Failed AI requests</span>
            </a>
        </div>

        <div class="border-t border-slate-100 pt-3">
            <a href="{{ route('assistant.workspace') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold text-violet-700 transition-colors hover:bg-violet-50 focus:outline-none focus:ring-2 focus:ring-violet-500" title="Universal AI Assistant">
                <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3z"/></svg><span x-show="!sidebarCollapsed">AI Assistant</span>
            </a>
        </div>
    </nav>

    <div x-show="!sidebarCollapsed" class="m-3 rounded-xl bg-[#f2f6fb] px-3 py-3">
        <p class="text-[10px] font-bold text-slate-700">Administrator access</p>
        <p class="mt-1 text-[10px] leading-relaxed text-slate-500">Global roles; company membership continues to scope business data.</p>
    </div>
</aside>
