<x-finance_app-layout>
    <x-slot name="header"><h1 class="text-lg font-bold text-slate-900">PPL Dashboard</h1></x-slot>
    <div id="schema" class="rounded-xl border border-amber-200 bg-amber-50 p-6" role="status">
        <h2 class="text-lg font-semibold text-amber-950">Financial-plan schema review required</h2>
        <p class="mt-2 text-sm text-amber-900">The current database lacks columns or tables required by saved plan monitoring. Historical PPL Forms remain available. Apply the reviewed financial-plan migrations in staging before enabling dashboard totals and reports.</p>
        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('ppl_forms.index') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-amber-950">Review PPL Forms</a>
            <a href="{{ route('finance.ppl.sources.index') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-amber-950">Review source mapping</a>
        </div>
    </div>
</x-finance_app-layout>
