<x-project_app-layout>
<div class="max-w-6xl mx-auto p-6 space-y-6 text-slate-800">

    {{-- HEADER --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-start gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900">
                    {{ $project->project_name }}
                </h1>
                <p class="text-sm text-slate-400">
                    Project CRM Dashboard
                </p>
            </div>

            <a href="{{ route('projects.index') }}"
               class="px-4 py-2 text-xs font-bold bg-slate-100 hover:bg-slate-200 rounded-xl border border-slate-200">
                Back
            </a>
        </div>
    </div>

    {{-- TABS --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- TAB BUTTONS --}}
        <div class="flex border-b border-slate-200 text-xs font-bold text-slate-500">

            <button onclick="openTab('overview')" class="tab-btn px-5 py-3 hover:bg-slate-50">
                Overview
            </button>

            <button onclick="openTab('timeline')" class="tab-btn px-5 py-3 hover:bg-slate-50">
                Timeline
            </button>

            <button onclick="openTab('deliveries')" class="tab-btn px-5 py-3 hover:bg-slate-50">
                Deliveries
            </button>

            <button onclick="openTab('logs')" class="tab-btn px-5 py-3 hover:bg-slate-50">
                Logs
            </button>

        </div>

        {{-- TAB CONTENT --}}
        <div class="p-6 space-y-6">

            {{-- ================= OVERVIEW ================= --}}
            <div id="overview" class="tab-content">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-slate-400">Ref No</p>
                            <p class="font-mono font-bold">{{ $project->ref_no }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-400">Agency</p>
                            <p class="font-semibold">{{ $project->agency }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-400">Status</p>
                            <span class="px-3 py-1 text-xs rounded-full font-bold bg-blue-50 text-blue-700">
                                {{ $project->status }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-slate-400">Contract Amount</p>
                            <p class="text-lg font-bold">
                                ₱ {{ number_format($project->contract_amount, 2) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-400">ABC</p>
                            <p class="font-semibold">
                                ₱ {{ number_format($project->ABC, 2) }}
                            </p>
                        </div>
                    </div>

                </div>

            </div>

            {{-- ================= TIMELINE ================= --}}
            <div id="timeline" class="tab-content hidden">

                <div class="border-l-2 border-slate-200 pl-4 space-y-4">

                    <div>
                        <p class="text-xs text-slate-400">Start Date</p>
                        <p class="font-semibold">
                            {{ $project->start_date?->format('M d, Y') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-slate-400">End Date</p>
                        <p class="font-semibold">
                            {{ $project->end_date?->format('M d, Y') }}
                        </p>
                    </div>

                </div>

            </div>

            {{-- ================= DELIVERIES ================= --}}
            <div id="deliveries" class="tab-content hidden">

                <div class="text-sm text-slate-500">
                    @if(isset($deliveries) && $deliveries->count())
                        @foreach($deliveries as $delivery)
                            <div class="p-3 border rounded-lg mb-2">
                                <div class="font-bold">{{ $delivery->title ?? 'Delivery' }}</div>
                                <div class="text-xs text-slate-400">{{ $delivery->created_at }}</div>
                            </div>
                        @endforeach
                    @else
                        No deliveries found.
                    @endif
                </div>

            </div>

            {{-- ================= LOGS ================= --}}
            <div id="logs" class="tab-content hidden">

                <div class="text-sm text-slate-500 space-y-2">

                    <div class="p-3 border rounded-lg">
                        <span class="font-bold">Created:</span>
                        {{ $project->created_at?->format('M d, Y h:i A') }}
                    </div>

                    <div class="p-3 border rounded-lg">
                        <span class="font-bold">Last Updated:</span>
                        {{ $project->updated_at?->format('M d, Y h:i A') }}
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>

{{-- TAB SCRIPT --}}
<script>
function openTab(tabName) {
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.add('hidden'));

    document.getElementById(tabName).classList.remove('hidden');
}
</script>

</x-project_app-layout>