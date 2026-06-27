<div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">

    <div class="overflow-x-auto">

        <table class="w-full text-sm text-left border-collapse">

            <thead class="bg-slate-50 border-b border-slate-200/60 text-[11px] uppercase font-bold tracking-wider text-slate-400">

                <tr>
                    <th class="px-6 py-4">
                        Bidding Projects
                    </th>

                    <th class="px-6 py-4 text-right">
                        Actions
                    </th>
                </tr>

            </thead>

            <tbody class="divide-y divide-slate-100">

                @forelse($projects as $project)

                <tr class="hover:bg-slate-50/70 transition">

                    <td colspan="2" class="px-6 py-5">

                        {{-- ================= HEADER ================= --}}
                        <div class="flex justify-between items-start gap-6">

                            <div class="flex-1">

                                <div class="flex items-center gap-2 flex-wrap">

                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-semibold bg-blue-50 text-blue-700 border border-blue-100 uppercase tracking-wide">

                                        {{ $project->project_id }}

                                    </span>

                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-semibold bg-slate-100 text-slate-700 uppercase">

                                        LOT {{ $project->lot_no ?? '-' }}

                                    </span>

                                    {{-- STATUS --}}
                                    @switch($project->status)

                                        @case('Draft')

                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-bold uppercase">
                                                Draft
                                            </span>

                                            @break

                                        @case('For Review')

                                            <span class="px-2 py-0.5 rounded bg-yellow-100 text-yellow-800 text-[10px] font-bold uppercase">
                                                For Review
                                            </span>

                                            @break

                                        @case('Published')

                                            <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px] font-bold uppercase">
                                                Published
                                            </span>

                                            @break

                                        @case('Awarded')

                                            <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[10px] font-bold uppercase">
                                                Awarded
                                            </span>

                                            @break

                                        @case('Cancelled')

                                            <span class="px-2 py-0.5 rounded bg-red-100 text-red-700 text-[10px] font-bold uppercase">
                                                Cancelled
                                            </span>

                                            @break

                                        @case('Completed')

                                            <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[10px] font-bold uppercase">
                                                Completed
                                            </span>

                                            @break

                                    @endswitch

                                </div>

                                <h2 class="mt-2 text-base font-bold text-slate-900">

                                    {{ $project->project_name }}

                                </h2>

                                <p class="mt-1 text-[11px] text-slate-500">

                                    Procuring Entity:
                                    <span class="font-semibold text-slate-700">

                                        {{ $project->procuring_entity }}

                                    </span>

                                </p>

                            </div>

                            {{-- ACTIONS --}}
                            <div class="flex gap-2 shrink-0">

                                <a href="{{ route('bidding.show',$project->id) }}"
                                   class="px-3 py-1.5 text-[11px] font-semibold bg-white border rounded-lg hover:bg-slate-50">

                                    View

                                </a>

                                <a href="{{ route('bidding.edit',$project->id) }}"
                                   class="px-3 py-1.5 text-[11px] font-semibold bg-amber-50 border border-amber-200 text-amber-700 rounded-lg hover:bg-amber-100">

                                    Edit

                                </a>

                                <form method="POST"
                                      action="{{ route('bidding.destroy',$project->id) }}"
                                      onsubmit="return confirm('Delete this bidding project?')">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        class="px-3 py-1.5 text-[11px] font-semibold bg-red-50 border border-red-200 text-red-700 rounded-lg hover:bg-red-100">

                                        Delete

                                    </button>

                                </form>

                            </div>

                        </div>

                        <div class="my-5 border-t border-slate-200"></div>

                        {{-- ================= GRID ================= --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                            {{-- PROJECT --}}
                            <div class="space-y-3">

                                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">

                                    Project Information

                                </div>

                                <div class="flex justify-between">

                                    <span class="text-slate-400">

                                        ABC

                                    </span>

                                    <span class="font-bold">

                                        ₱{{ number_format($project->approved_budget_contract_abc,2) }}

                                    </span>

                                </div>

                                <div class="flex justify-between">

                                    <span class="text-slate-400">

                                        Bid Opening

                                    </span>

                                    <span>

                                        {{ $project->date_of_bid_opening
                                            ? \Carbon\Carbon::parse($project->date_of_bid_opening)->format('M d, Y')
                                            : '-' }}

                                    </span>

                                </div>

                                <div class="flex justify-between">

                                    <span class="text-slate-400">

                                        Delivery Period

                                    </span>

                                    <span>

                                        {{ $project->delivery_period ?: '-' }}

                                    </span>

                                </div>

                            </div>

                            {{-- LOCATION --}}
                            <div class="space-y-3">

                                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">

                                    Delivery Location

                                </div>

                                <div>

                                    <div class="font-semibold">

                                        {{ $project->country }}

                                    </div>

                                    <div class="text-slate-500 text-xs">

                                        {{ $project->region }}

                                    </div>

                                    <div class="text-slate-500 text-xs">

                                        {{ $project->province }}

                                    </div>

                                    <div class="text-slate-500 text-xs">

                                        {{ $project->city_municipality }}

                                    </div>

                                    <div class="text-slate-500 text-xs">

                                        {{ $project->barangay }}

                                    </div>

                                </div>

                            </div>

                            {{-- PREPARATION --}}
                            <div class="space-y-3">

                                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">

                                    Document Control

                                </div>

                                <div class="flex justify-between">

                                    <span class="text-slate-400">

                                        Prepared By

                                    </span>

                                    <span>

                                        {{ $project->prepared_by ?: '-' }}

                                    </span>

                                </div>

                                <div class="flex justify-between">

                                    <span class="text-slate-400">

                                        Prepared Date

                                    </span>

                                    <span>

                                        {{ $project->prepared_date
                                            ? \Carbon\Carbon::parse($project->prepared_date)->format('M d, Y')
                                            : '-' }}

                                    </span>

                                </div>

                                <div class="flex justify-between">

                                    <span class="text-slate-400">

                                        Verified By

                                    </span>

                                    <span>

                                        {{ $project->verified_by ?: '-' }}

                                    </span>

                                </div>

                            </div>

                        </div>

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="2" class="py-20 text-center">

                        <div class="text-slate-300">

                            <svg class="mx-auto h-12 w-12"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="1.5"
                                      d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>

                            </svg>

                        </div>

                        <div class="mt-4 text-lg font-semibold text-slate-500">

                            No Bidding Projects Found

                        </div>

                        <div class="text-sm text-slate-400 mt-1">

                            Click "Create New" to start your first bidding document.

                        </div>

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    {{-- Pagination --}}
    @if($projects->hasPages())

        <div class="px-6 py-4 border-t border-slate-200 bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

            <div class="text-sm text-slate-600">

                Showing

                <span class="font-semibold">

                    {{ $projects->firstItem() }}

                </span>

                to

                <span class="font-semibold">

                    {{ $projects->lastItem() }}

                </span>

                of

                <span class="font-semibold">

                    {{ $projects->total() }}

                </span>

                records

            </div>

            <div>

                {{ $projects->withQueryString()->links('pagination::tailwind') }}

            </div>

        </div>

    @endif

</div>