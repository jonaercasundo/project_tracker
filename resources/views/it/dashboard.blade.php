<x-it_app>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">

    <div class="bg-white p-6 rounded-2xl border border-slate-200">
        <h1 class="text-2xl font-bold text-slate-900">
            IT Dashboard
        </h1>

        <p class="text-sm text-slate-500 mt-1">
            Welcome to IT Module Control Center
        </p>
    </div>

    {{-- STATS CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-6">
            <div class="text-sm text-emerald-700 font-semibold">Total Projects</div>
            <div class="text-2xl font-bold text-emerald-900 mt-2">
                {{ \App\Models\PplForm::count() }}
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6">
            <div class="text-sm text-blue-700 font-semibold">Total ABC</div>
            <div class="text-2xl font-bold text-blue-900 mt-2">
                ₱{{ number_format(\App\Models\PplForm::sum('abc'),2) }}
            </div>
        </div>

        <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6">
            <div class="text-sm text-indigo-700 font-semibold">Active Projects</div>
            <div class="text-2xl font-bold text-indigo-900 mt-2">
                {{ \App\Models\PplForm::whereNotNull('project_code')->count() }}
            </div>
        </div>

    </div>

</div>
</x-it_app>