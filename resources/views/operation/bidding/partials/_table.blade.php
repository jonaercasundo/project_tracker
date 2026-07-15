<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">

    <div class="overflow-x-auto">

        <table class="w-full text-sm text-left border-collapse">

            <thead>
                <tr class="border-b border-slate-200 text-xs font-medium text-slate-500 uppercase tracking-wide">
                    <th class="px-5 py-3">Bidding Projects</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">

                @forelse($projects as $project)

                <tr class="hover:bg-slate-50/70 transition-colors">

                    <td colspan="2" class="px-5 py-5">

                        {{-- ================= HEADER ================= --}}
                        <div class="flex justify-between items-start gap-6">

                            <div class="flex-1">

                                <div class="flex items-center gap-2 flex-wrap">

                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600">
                                        {{ $project->project_id }}
                                    </span>

                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-slate-100 text-slate-600">
                                        LOT {{ $project->lot_no ?? '-' }}
                                    </span>

                                    {{-- STATUS --}}
                                    @php
                                        $statusStyles = match($project->status) {
                                            'Draft' => 'bg-slate-100 text-slate-600',
                                            'For Review' => 'bg-amber-50 text-amber-700',
                                            'Published' => 'bg-blue-50 text-blue-700',
                                            'Awarded' => 'bg-emerald-50 text-emerald-700',
                                            'Cancelled' => 'bg-rose-50 text-rose-700',
                                            'Completed' => 'bg-indigo-50 text-indigo-700',
                                            default => 'bg-slate-100 text-slate-600',
                                        };
                                    @endphp

                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium {{ $statusStyles }}">
                                        {{ $project->status }}
                                    </span>

                                </div>

                                <h2 class="mt-2 text-base font-semibold text-slate-900">
                                    {{ $project->project_name }}
                                </h2>

                                <p class="mt-1 text-xs text-slate-500">
                                    Procuring Entity:
                                    <span class="font-medium text-slate-700">
                                        {{ $project->procuring_entity }}
                                    </span>
                                </p>

                            </div>

                            {{-- ACTIONS --}}
                            <div class="flex items-center gap-1 shrink-0">

                                <a href="{{ route('project.bidding.show',$project->id) }}"
                                   title="View"
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </a>

                                <a href="{{ route('project.bidding.edit',$project->id) }}"
                                   title="Edit"
                                   class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>

                                <form method="POST"
                                      action="{{ route('project.bidding.destroy',$project->id) }}"
                                      onsubmit="return confirm('Delete this bidding project?')">

                                    @csrf
                                    @method('DELETE')

                                    <button
                                        title="Delete"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>

                                </form>

                            </div>

                        </div>

                        <div class="my-4 border-t border-slate-100"></div>

                        {{-- ================= GRID ================= --}}
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 text-sm">

                            {{-- PROJECT --}}
                            <div class="space-y-2.5">

                                <div class="text-xs font-medium text-slate-400 uppercase tracking-wide">
                                    Project Information
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-slate-500">ABC</span>
                                    <span class="font-medium text-slate-900">
                                        ₱{{ number_format($project->approved_budget_contract_abc,2) }}
                                    </span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-slate-500">Bid Opening</span>
                                    <span class="text-slate-700">
                                        {{ $project->date_of_bid_opening
                                            ? \Carbon\Carbon::parse($project->date_of_bid_opening)->format('M d, Y')
                                            : '-' }}
                                    </span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-slate-500">Delivery Period</span>
                                    <span class="text-slate-700">
                                        {{ $project->delivery_period ?: '-' }}
                                    </span>
                                </div>

                            </div>

                            {{-- LOCATION --}}
                            <div class="space-y-2.5">

                                <div class="text-xs font-medium text-slate-400 uppercase tracking-wide">
                                    Delivery Location
                                </div>

                                <div class="space-y-0.5">
                                    <div class="font-medium text-slate-900">{{ $project->country }}</div>
                                    <div class="text-slate-500 text-xs">{{ $project->region }}</div>
                                    <div class="text-slate-500 text-xs">{{ $project->province }}</div>
                                    <div class="text-slate-500 text-xs">{{ $project->city_municipality }}</div>
                                    <div class="text-slate-500 text-xs">{{ $project->barangay }}</div>
                                </div>

                            </div>

                            {{-- PREPARATION --}}
                            <div class="space-y-2.5">

                                <div class="text-xs font-medium text-slate-400 uppercase tracking-wide">
                                    Document Control
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-slate-500">Prepared By</span>
                                    <span class="text-slate-700">{{ $project->prepared_by ?: '-' }}</span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-slate-500">Prepared Date</span>
                                    <span class="text-slate-700">
                                        {{ $project->prepared_date
                                            ? \Carbon\Carbon::parse($project->prepared_date)->format('M d, Y')
                                            : '-' }}
                                    </span>
                                </div>

                                <div class="flex justify-between">
                                    <span class="text-slate-500">Verified By</span>
                                    <span class="text-slate-700">{{ $project->verified_by ?: '-' }}</span>
                                </div>

                            </div>

                        </div>

                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="2" class="py-16 text-center">
                        <svg class="mx-auto w-8 h-8 text-slate-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                        </svg>
                        <div class="text-sm font-medium text-slate-700">No Bidding Projects Found</div>
                        <div class="text-xs text-slate-400 mt-1">Click "New Document" to start your first bidding document.</div>
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

    {{-- Pagination --}}
    @if($projects->hasPages())

        <div class="px-5 py-3.5 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm">

            <div class="text-slate-500">
                Showing
                <span class="font-medium text-slate-700">{{ $projects->firstItem() }}</span>
                to
                <span class="font-medium text-slate-700">{{ $projects->lastItem() }}</span>
                of
                <span class="font-medium text-slate-700">{{ $projects->total() }}</span>
                records
            </div>

            <div>
                {{ $projects->withQueryString()->links('pagination::tailwind') }}
            </div>

        </div>

    @endif

</div>