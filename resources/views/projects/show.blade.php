<x-project_app-layout>
<div class="max-w-6xl mx-auto p-6 space-y-6 text-slate-800">

    {{-- HEADER --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex justify-between items-center gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">
                    {{ $project->project_name }}
                </h1>
                <p class="text-xs font-medium text-slate-400 mt-1 uppercase tracking-wider">
                    Project CRM Dashboard
                </p>
            </div>

            <a href="{{ route('projects.index') }}"
               class="px-4 py-2 text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition-colors duration-200 rounded-xl border border-slate-200/60 shadow-sm">
                Back to List
            </a>
        </div>
    </div>

    {{-- TABS CONTAINER --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- TAB BUTTONS --}}
        <div class="flex border-b border-slate-200 bg-slate-50/50 text-xs font-bold text-slate-500">
            <button onclick="openTab(event, 'overview')" class="tab-btn px-6 py-3.5 border-b-2 border-blue-600 bg-white text-blue-600 transition-all duration-200">
                Overview
            </button>
            <button onclick="openTab(event, 'timeline')" class="tab-btn px-6 py-3.5 border-b-2 border-transparent hover:bg-slate-50 text-slate-500 hover:text-slate-700 transition-all duration-200">
                Timeline
            </button>
            <button onclick="openTab(event, 'deliveries')" class="tab-btn px-6 py-3.5 border-b-2 border-transparent hover:bg-slate-50 text-slate-500 hover:text-slate-700 transition-all duration-200">
                Deliveries
            </button>
            <button onclick="openTab(event, 'logs')" class="tab-btn px-6 py-3.5 border-b-2 border-transparent hover:bg-slate-50 text-slate-500 hover:text-slate-700 transition-all duration-200">
                Logs
            </button>
        </div>

        {{-- TAB PANELS --}}
        <div class="p-6">

            {{-- OVERVIEW TAB --}}
            <div id="overview" class="tab-content block space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <div class="space-y-5">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Ref No</p>
                            <p class="font-mono font-bold text-base text-slate-900">{{ $project->ref_no }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Agency</p>
                            <p class="font-semibold text-slate-800">{{ $project->agency }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</p>
                            <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                {{ $project->status }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-5 bg-slate-50/60 p-5 rounded-xl border border-slate-100">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Contract Amount</p>
                            <p class="text-xl font-black text-slate-900">
                                ₱{{ number_format($project->contract_amount, 2) }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-200/60">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Approved Budget for Contract (ABC)</p>
                            <p class="text-base font-bold text-slate-700">
                                ₱{{ number_format($project->ABC, 2) }}
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            {{-- TIMELINE TAB --}}
            <div id="timeline" class="tab-content hidden space-y-4">
                <div class="relative border-l-2 border-slate-200 pl-6 ml-2 space-y-6">
                    
                    <div class="relative">
                        <div class="absolute -left-[31px] top-1 bg-white border-2 border-blue-600 rounded-full w-3 h-3"></div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Start Date</p>
                        <p class="font-bold text-slate-800">
                            {{ $project->start_date ? $project->start_date->format('M d, Y') : 'Not Set' }}
                        </p>
                    </div>

                    <div class="relative">
                        <div class="absolute -left-[31px] top-1 bg-white border-2 border-slate-400 rounded-full w-3 h-3"></div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">End Date</p>
                        <p class="font-bold text-slate-800">
                            {{ $project->end_date ? $project->end_date->format('M d, Y') : 'Not Set' }}
                        </p>
                    </div>

                </div>
            </div>

            {{-- DELIVERIES TAB --}}
            <div id="deliveries" class="tab-content hidden">
                <div class="space-y-3">
                    @if(isset($deliveries) && $deliveries->count())
                        @foreach($deliveries as $delivery)
                            <div class="p-4 border border-slate-200 rounded-xl bg-white shadow-sm flex justify-between items-center hover:border-slate-300 transition-colors">
                                <div>
                                    <div class="font-bold text-slate-800">{{ $delivery->title ?? 'Delivery item' }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">Logged: {{ $delivery->created_at }}</div>
                                </div>
                                <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-600 rounded-md">Item</span>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-8 border border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                            <p class="text-sm font-medium text-slate-400">No deliveries listed for this project yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- LOGS TAB --}}
            <div id="logs" class="tab-content hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-slate-600">
                    
                    <div class="p-4 border border-slate-200/80 rounded-xl bg-slate-50/30 flex items-center justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Date Created</span>
                            <span class="font-medium text-slate-800">{{ $project->created_at ? $project->created_at->format('M d, Y h:i A') : 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="p-4 border border-slate-200/80 rounded-xl bg-slate-50/30 flex items-center justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-0.5">Last Updated</span>
                            <span class="font-medium text-slate-800">{{ $project->updated_at ? $project->updated_at->format('M d, Y h:i A') : 'N/A' }}</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

{{-- TAB SCRIPT --}}
<script>
function openTab(event, tabName) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => {
        content.classList.add('hidden');
        content.classList.remove('block');
    });

    // Remove active styles from all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => {
        btn.classList.remove('border-blue-600', 'text-blue-600', 'bg-white');
        btn.classList.add('border-transparent', 'text-slate-500', 'hover:bg-slate-50');
    });

    // Show current tab content
    const activeContent = document.getElementById(tabName);
    activeContent.classList.remove('hidden');
    activeContent.classList.add('block');

    // Add active styles to clicked button
    const clickedButton = event.currentTarget;
    clickedButton.classList.remove('border-transparent', 'text-slate-500', 'hover:bg-slate-50');
    clickedButton.classList.add('border-blue-600', 'text-blue-600', 'bg-white');
}
</script>
</x-project_app-layout>