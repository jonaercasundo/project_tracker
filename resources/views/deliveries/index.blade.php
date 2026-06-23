<x-project_app-layout>

<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">
                Deliveries Tracking
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Monitor DR groups and item progression in real-time
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" class="px-4 py-2 text-xs font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                Add Delivery
            </button>
            <button type="button" class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-900 text-white hover:bg-slate-800 transition">
                Batch
            </button>
            <button type="button" class="px-4 py-2 text-xs font-bold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                Import
            </button>
        </div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ url()->current() }}" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm space-y-4">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">

            {{-- YEAR --}}
            <div class="flex flex-col gap-1">
                <select name="year" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    <option value="">Year</option>
                    <option value="2026" @selected(request('year') == '2026')>2026</option>
                    <option value="2025" @selected(request('year') == '2025')>2025</option>
                </select>
            </div>

            {{-- PROJECT --}}
            <div class="flex flex-col gap-1">
                <select name="project" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    <option value="">Project</option>
                    @foreach($projects ?? [] as $project)
                        <option value="{{ $project->project_id }}" @selected(request('project') == $project->project_id)>
                            {{ \Illuminate\Support\Str::limit($project->project_name, 35) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- STATUS --}}
            <div class="flex flex-col gap-1">
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    <option value="">Status</option>
                    <option value="Pending" @selected(request('status') == 'Pending')>Pending</option>
                    <option value="Accepted" @selected(request('status') == 'Accepted')>Accepted</option>
                    <option value="Delivered" @selected(request('status') == 'Delivered')>Delivered</option>
                </select>
            </div>

            {{-- LOT --}}
            <div class="flex flex-col gap-1">
                <select name="lot" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    <option value="">Lot</option>
                    @for($i = 1; $i <= 20; $i++)
                        <option value="{{ $i }}" @selected(request('lot') == $i)>
                            Lot {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>

            {{-- KEYSTAGE --}}
            <div class="flex flex-col gap-1">
                <select name="keystage" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    <option value="">Keystage</option>
                    @for($i = 1; $i <= 6; $i++)
                        <option value="{{ $i }}" @selected(request('keystage') == $i)>
                            KS {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>

            {{-- REGION --}}
            <div class="flex flex-col gap-1">
                <select name="region" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50/50">
                    <option value="">Region</option>
                    <option value="NCR" @selected(request('region') == 'NCR')>NCR</option>
                    <option value="Region III" @selected(request('region') == 'Region III')>Region III</option>
                    <option value="Region IV-A" @selected(request('region') == 'Region IV-A')>Region IV-A</option>
                </select>
            </div>

        </div>

        {{-- ACTION ROW --}}
        <div class="flex justify-end items-center gap-2 pt-2 border-t border-slate-100">
            <a href="{{ url()->current() }}" class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 transition text-center">
                Reset
            </a>
            <button type="submit" class="px-4 py-2 text-xs font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                Apply Filters
            </button>
        </div>

    </form>

    {{-- CONTENT STATE HANDLERS --}}
    @if(empty($grouped_deliveries))
        <div class="text-center py-16 text-slate-400 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <span class="block text-sm font-medium text-slate-600">No deliveries found.</span>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your tracking filters above.</p>
        </div>
    @else

        <div class="space-y-5">
            @foreach($grouped_deliveries as $dr_group)

                {{-- DR CARD --}}
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">

                    {{-- DR HEADER --}}
                    <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/70 border-b border-slate-100">
                        
                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dr-checkbox"
                                   value="{{ $dr_group['dr_no'] }}"
                                   data-school-id="{{ $dr_group['school_id'] }}">

                            <div>
                                <div class="text-sm font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                                    <span>DR #{{ $dr_group['dr_no'] }}</span>
                                </div>
                                <div class="text-[11px] text-slate-500 font-medium mt-0.5">
                                    {{ $dr_group['project_name'] }} <span class="text-slate-300 mx-1">•</span> {{ $dr_group['school_name'] }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 self-end sm:self-auto">
                            <button type="button" onclick="generateARs()" class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-sm transition">
                                QR
                            </button>
                            <button type="button" onclick="generateLabels()" class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-sm transition">
                                Label
                            </button>
                        </div>

                    </div>

                    {{-- ITEMS LIST --}}
                    <div class="divide-y divide-slate-100">
                        @foreach($dr_group['deliveries'] as $d)
                            <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 hover:bg-slate-50/50 transition">

                                {{-- LEFT DETAILS --}}
                                <div class="space-y-1 max-w-3xl">
                                    <div class="text-sm font-semibold text-slate-800 flex items-center flex-wrap gap-1.5">
                                        <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-xs font-bold">LOT {{ $d->lot_name }}</span>
                                        @if($d->keystage_num)
                                            <span class="text-slate-400 font-medium text-xs">
                                                • Keystage {{ $d->keystage_num }} <span class="text-slate-600 font-normal">{{ $d->description }}</span>
                                            </span>
                                        @endif
                                    </div>

                                    <div class="text-xs text-slate-500 leading-relaxed">
                                        {!! $d->items_contents ?? '<span class="text-slate-400 italic">No items available</span>' !!}
                                    </div>
                                </div>

                                {{-- ROW ACTIONS --}}
                                <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                                    @if(auth()->user()->hasAnyRole(['Super Admin','Office Admin','Office Coordinator','Warehouse Admin']))
                                        <button type="button" class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-200/60 hover:bg-amber-100 transition">
                                            Edit
                                        </button>
                                    @endif

                                    <a href="{{ url('deliveries_details/'.$d->dr_no) }}" class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-200/60 hover:bg-blue-100 transition">
                                        View
                                    </a>
                                </div>

                            </div>
                        @endforeach
                    </div>

                </div>
            @endforeach
        </div>

        {{-- PAGINATION --}}
        <div class="flex items-center justify-center mt-8 gap-3">
            @if($page > 1)
                <a href="?{{ http_build_query(array_merge(request()->query(), ['page' => $page - 1])) }}"
                   class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 shadow-sm transition">
                    Prev
                </a>
            @endif

            <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1.5 rounded-xl">
                Page {{ $page }} of {{ $total_pages }}
            </span>

            @if($page < $total_pages)
                <a href="?{{ http_build_query(array_merge(request()->query(), ['page' => $page + 1])) }}"
                   class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold bg-white hover:bg-slate-50 text-slate-700 shadow-sm transition">
                    Next
                </a>
            @endif
        </div>

    @endif

</div>

</x-project_app-layout>