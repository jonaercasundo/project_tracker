<div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-6h6v6" /></svg>
</div>
<p class="mt-4 font-semibold text-slate-700">No schools found.</p>
<p class="mt-1 text-sm text-slate-500">Adjust the search and filters, or add your first school.</p>
<div class="mt-5 flex justify-center gap-2">
    <a href="{{ route('schools.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Reset filters</a>
    <a href="{{ route('schools.create', $listQuery ?? []) }}" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-bold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">Add New School</a>
</div>