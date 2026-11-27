<x-project_app-layout>
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <section class="relative overflow-hidden rounded-3xl bg-[#172a45] px-6 py-8 text-white shadow-xl shadow-slate-900/10 sm:px-9 sm:py-10">
            <div class="absolute -right-16 -top-24 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="absolute -right-2 -top-10 h-52 w-52 rounded-full border border-white/10"></div>
            <div class="relative max-w-3xl">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.22em] text-cyan-300">MMC · People operations</p>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Human Resources</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">A central workspace for employee records, workplace services, and the people behind MMC’s work.</p>
                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-semibold text-white/90">
                        <span class="h-1.5 w-1.5 rounded-full bg-cyan-300"></span>
                        HR workspace
                    </span>
                    <span class="text-xs text-slate-400">MMC Tracker</span>
                </div>
            </div>
        </section>

        <section aria-labelledby="hr-areas-heading" class="space-y-3">
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-[0.18em] text-blue-700">Workspace map</p>
                <h2 id="hr-areas-heading" class="mt-1 text-lg font-extrabold tracking-tight text-slate-900">People services</h2>
                <p class="mt-1 text-sm text-slate-500">HR work areas are organized here as the module is established.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['Employee records', 'Employee profiles and employment details', 'M4 7h16M4 12h16M4 17h10', 'Directory'],
                    ['Time and attendance', 'Attendance and timekeeping', 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'Timekeeping'],
                    ['Leave and benefits', 'Leave requests and employee benefits', 'M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z', 'Employee services'],
                    ['Recruitment', 'Hiring and candidate coordination', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m6-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm12-3v6m3-3h-6', 'Talent'],
                ] as [$title, $description, $icon, $category])
                    <article class="flex min-h-48 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-100/60">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-500">{{ $category }}</span>
                        </div>
                        <h3 class="mt-5 text-sm font-extrabold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1.5 text-xs leading-5 text-slate-500">{{ $description }}</p>
                        <div class="mt-auto pt-5 text-[10px] font-semibold text-slate-400">Module area</div>
                    </article>
                @endforeach
            </div>
        </section>

        <aside class="flex items-start gap-3 rounded-2xl border border-cyan-100 bg-cyan-50/70 p-4 text-xs leading-5 text-cyan-950">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-cyan-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8h.01M11 12h1v4h1m8-4a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <p><strong class="font-bold">Module foundation.</strong> Employee data and HR workflows can be added to these areas as requirements are defined.</p>
        </aside>
    </div>
</x-project_app-layout>
