<x-project_app-layout>
    <div class="max-w-5xl mx-auto p-6 space-y-6 text-slate-800">

        {{-- HEADER --}}
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-black text-slate-900">
                        {{ $project->project_name }}
                    </h1>
                    <p class="text-sm text-slate-400 mt-1">
                        Project Details View
                    </p>
                </div>

                <a href="{{ route('projects.index') }}"
                   class="px-4 py-2 text-xs font-bold bg-slate-100 hover:bg-slate-200 rounded-xl border border-slate-200">
                    Back to List
                </a>
            </div>
        </div>

        {{-- MAIN INFO --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            {{-- LEFT --}}
            <div class="bg-white p-6 rounded-2xl border border-slate-200 space-y-4">

                <div>
                    <p class="text-xs text-slate-400 uppercase">Reference No</p>
                    <p class="font-mono font-bold text-slate-700">
                        {{ $project->ref_no }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-slate-400 uppercase">Agency</p>
                    <p class="font-semibold text-slate-800">
                        {{ $project->agency }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-slate-400 uppercase">Status</p>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                        {{ str_contains(strtolower($project->status), 'complete') ? 'bg-emerald-50 text-emerald-700' :
                           (str_contains(strtolower($project->status), 'pend') ? 'bg-amber-50 text-amber-700' :
                           'bg-blue-50 text-blue-700') }}">
                        {{ $project->status }}
                    </span>
                </div>

            </div>

            {{-- RIGHT --}}
            <div class="bg-white p-6 rounded-2xl border border-slate-200 space-y-4">

                <div>
                    <p class="text-xs text-slate-400 uppercase">Contract Amount</p>
                    <p class="text-lg font-bold text-slate-900">
                        ₱ {{ number_format($project->contract_amount, 2) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-slate-400 uppercase">ABC</p>
                    <p class="font-semibold text-slate-800">
                        ₱ {{ number_format($project->ABC, 2) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-slate-400 uppercase">Timeline</p>
                    <p class="font-medium text-slate-700">
                        {{ \Carbon\Carbon::parse($project->start_date)->format('M d, Y') }}
                        →
                        {{ \Carbon\Carbon::parse($project->end_date)->format('M d, Y') }}
                    </p>
                </div>

            </div>

        </div>

        {{-- RAW INFO (optional debug-style section) --}}
        <div class="bg-white p-6 rounded-2xl border border-slate-200">
            <h2 class="text-sm font-bold text-slate-700 mb-4">Raw Data</h2>

            <pre class="text-xs bg-slate-50 p-4 rounded-xl overflow-auto text-slate-600">
{{ json_encode($project, JSON_PRETTY_PRINT) }}
            </pre>
        </div>

    </div>
</x-project_app-layout>