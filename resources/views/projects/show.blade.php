<x-project_app-layout>
<div class="max-w-6xl mx-auto p-6 space-y-6 text-slate-800">

    {{-- HEADER --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-black text-slate-900">
                    {{ $project->project_name }}
                </h1>
                <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-wider">
                    Project CRM Dashboard
                </p>
            </div>

            <a href="{{ route('projects.index') }}"
               class="px-4 py-2 text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 rounded-xl border border-slate-200">
                Back
            </a>
        </div>
    </div>

    {{-- TABS --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- TAB NAV --}}
        <div role="tablist"
             class="flex border-b bg-slate-50/50 text-xs font-bold text-slate-500">

            @php
                $tabs = ['overview' => 'Overview', 'timeline' => 'Timeline', 'deliveries' => 'Deliveries', 'logs' => 'Logs'];
            @endphp

            @foreach($tabs as $key => $label)
                <button
                    type="button"
                    role="tab"
                    data-tab="{{ $key }}"
                    onclick="openTab(event, '{{ $key }}')"
                    class="tab-btn px-6 py-3.5 border-b-2 transition-all duration-200
                        {{ $loop->first ? 'border-blue-600 text-blue-600 bg-white' : 'border-transparent hover:bg-slate-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- CONTENT --}}
        <div class="p-6">

            {{-- OVERVIEW --}}
            <div id="overview" class="tab-content space-y-6">

                <div class="grid md:grid-cols-2 gap-6">

                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-slate-400 uppercase">Ref No</p>
                            <p class="font-mono font-bold">{{ $project->ref_no }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-400 uppercase">Agency</p>
                            <p class="font-semibold">{{ $project->agency }}</p>
                        </div>

                        <div>
                            <p class="text-xs text-slate-400 uppercase">Status</p>
                            <span class="px-3 py-1 text-xs font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                {{ $project->status }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-5 rounded-xl border">
                        <p class="text-xs text-slate-400 uppercase">Contract Amount</p>
                        <p class="text-2xl font-black">
                            ₱{{ number_format($project->contract_amount ?? 0, 2) }}
                        </p>

                        <div class="mt-4 border-t pt-3">
                            <p class="text-xs text-slate-400 uppercase">ABC</p>
                            <p class="font-bold">
                                ₱{{ number_format($project->ABC ?? 0, 2) }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- TIMELINE --}}
            <div id="timeline" class="tab-content hidden">
                <div class="border-l-2 border-slate-200 pl-6 space-y-6">

                    <div>
                        <p class="text-xs text-slate-400 uppercase">Start Date</p>
                        <p class="font-semibold">
                            {{ optional($project->start_date)->format('M d, Y') ?? 'Not Set' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-slate-400 uppercase">End Date</p>
                        <p class="font-semibold">
                            {{ optional($project->end_date)->format('M d, Y') ?? 'Not Set' }}
                        </p>
                    </div>

                </div>
            </div>

            {{-- DELIVERIES --}}
            <div id="deliveries" class="tab-content hidden space-y-3">

                @forelse($deliveries ?? [] as $delivery)
                    <div class="p-4 border rounded-xl flex justify-between items-center hover:border-slate-300">
                        <div>
                            <div class="font-semibold">
                                {{ $delivery->title ?? 'Delivery item' }}
                            </div>
                            <div class="text-xs text-slate-400">
                                {{ optional($delivery->created_at)->format('M d, Y h:i A') }}
                            </div>
                        </div>

                        <span class="text-xs px-2 py-1 bg-slate-100 rounded">
                            Item
                        </span>
                    </div>
                @empty
                    <div class="p-6 text-center text-slate-400 border rounded-xl bg-slate-50">
                        No deliveries yet.
                    </div>
                @endforelse

            </div>

            {{-- LOGS --}}
            <div id="logs" class="tab-content hidden grid sm:grid-cols-2 gap-4">

                <div class="p-4 border rounded-xl bg-slate-50">
                    <p class="text-xs text-slate-400 uppercase">Created</p>
                    <p class="font-medium">
                        {{ optional($project->created_at)->format('M d, Y h:i A') }}
                    </p>
                </div>

                <div class="p-4 border rounded-xl bg-slate-50">
                    <p class="text-xs text-slate-400 uppercase">Updated</p>
                    <p class="font-medium">
                        {{ optional($project->updated_at)->format('M d, Y h:i A') }}
                    </p>
                </div>

            </div>

        </div>
    </div>
</div>

{{-- SCRIPT --}}
<script>
function openTab(event, tabName) {

    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-blue-600', 'text-blue-600', 'bg-white');
        btn.classList.add('border-transparent', 'text-slate-500');
    });

    document.getElementById(tabName).classList.remove('hidden');

    const btn = event.currentTarget;
    btn.classList.add('border-blue-600', 'text-blue-600', 'bg-white');

    localStorage.setItem('activeProjectTab', tabName);
}

// restore last tab
document.addEventListener('DOMContentLoaded', () => {
    const saved = localStorage.getItem('activeProjectTab');
    if (saved) {
        const btn = document.querySelector(`[data-tab="${saved}"]`);
        if (btn) btn.click();
    }
});
</script>

</x-project_app-layout>