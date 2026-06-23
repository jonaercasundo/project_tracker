<x-project_app-layout>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">

        <div>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900">
                Deliveries Tracking
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Monitor DR groups and item progression in real-time
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
            <button class="px-4 py-2 text-xs font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 shadow-sm">
                Add Delivery
            </button>
            <button class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-900 text-white hover:bg-slate-800">
                Batch
            </button>
            <button class="px-4 py-2 text-xs font-bold rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50">
                Import
            </button>
        </div>

    </div>

{{-- FILTERS --}}
<div class="bg-white/70 backdrop-blur-sm border border-slate-200/70 rounded-2xl p-4 shadow-sm">

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3">

        {{-- YEAR --}}
        <select name="year"
                class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Year</option>
            <option value="2026" @selected(request('year')=='2026')>2026</option>
            <option value="2025" @selected(request('year')=='2025')>2025</option>
        </select>

        {{-- PROJECT --}}
        <select name="project"
                class="px-3 py-2 rounded-xl border border-slate-200 text-sm focus:ring-2 focus:ring-blue-500">
            <option value="">Project</option>
            @foreach($projects ?? [] as $project)
                <option value="{{ $project->project_id }}"
                    @selected(request('project') == $project->project_id)>
                    {{ \Illuminate\Support\Str::limit($project->project_name, 35) }}
                </option>
            @endforeach
        </select>

        {{-- STATUS --}}
        <select name="status"
                class="px-3 py-2 rounded-xl border border-slate-200 text-sm">
            <option value="">Status</option>
            <option value="Pending" @selected(request('status')=='Pending')>Pending</option>
            <option value="Accepted" @selected(request('status')=='Accepted')>Accepted</option>
            <option value="Delivered" @selected(request('status')=='Delivered')>Delivered</option>
        </select>

        {{-- LOT --}}
        <select name="lot"
                class="px-3 py-2 rounded-xl border border-slate-200 text-sm">
            <option value="">Lot</option>
            @for($i=1;$i<=20;$i++)
                <option value="{{ $i }}" @selected(request('lot')==$i)>
                    {{ $i }}
                </option>
            @endfor
        </select>

        {{-- KEYSTAGE --}}
        <select name="keystage"
                class="px-3 py-2 rounded-xl border border-slate-200 text-sm">
            <option value="">Keystage</option>
            @for($i=1;$i<=6;$i++)
                <option value="{{ $i }}" @selected(request('keystage')==$i)>
                    KS {{ $i }}
                </option>
            @endfor
        </select>

        {{-- REGION (placeholder like your old system) --}}
        <select name="region"
                class="px-3 py-2 rounded-xl border border-slate-200 text-sm">
            <option value="">Region</option>
            <option value="NCR">NCR</option>
            <option value="Region III">Region III</option>
            <option value="Region IV-A">Region IV-A</option>
        </select>

    </div>

    {{-- ACTION ROW --}}
    <div class="flex justify-end mt-3 gap-2">

        <a href="{{ url()->current() }}"
           class="px-4 py-2 text-xs font-bold rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200">
            Reset
        </a>

        <button type="submit"
                class="px-4 py-2 text-xs font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700">
            Apply Filters
        </button>

    </div>

</div>

    {{-- EMPTY STATE (MOVED CORRECTLY) --}}
    @if(empty($grouped_deliveries))
        <div class="text-center py-14 text-slate-400 bg-white rounded-2xl border border-slate-100">
            No deliveries found.
        </div>
    @else

    {{-- CONTENT --}}
    <div class="space-y-5">

        @foreach($grouped_deliveries as $dr_group)

            {{-- DR CARD --}}
            <div class="bg-white/80 backdrop-blur-sm border border-slate-200/70 rounded-2xl shadow-sm overflow-hidden">

                {{-- DR HEADER --}}
                <div class="px-5 py-4 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white border-b border-slate-100">

                    <div class="flex items-center gap-3">

                        <input type="checkbox"
                               class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dr-checkbox"
                               value="{{ $dr_group['dr_no'] }}"
                               data-school-id="{{ $dr_group['school_id'] }}">

                        <div>
                            <div class="text-sm font-extrabold text-slate-900 tracking-tight">
                                DR #{{ $dr_group['dr_no'] }}
                            </div>

                            <div class="text-[11px] text-slate-500 mt-0.5">
                                {{ $dr_group['project_name'] }} • {{ $dr_group['school_name'] }}
                            </div>
                        </div>

                    </div>

                    <div class="flex items-center gap-2">

                        <button onclick="generateARs()"
                                class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">
                            QR
                        </button>

                        <button onclick="generateLabels()"
                                class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700">
                            Label
                        </button>

                    </div>

                </div>

                {{-- ITEMS --}}
                <div class="divide-y divide-slate-100">

                    @foreach($dr_group['deliveries'] as $d)

                        <div class="px-5 py-4 flex items-start justify-between hover:bg-slate-50 transition">

                            {{-- LEFT --}}
                            <div class="space-y-1">

                                <div class="text-sm font-semibold text-slate-800">
                                    LOT {{ $d->lot_name }}

                                    @if($d->keystage_num)
                                        <span class="text-slate-400 font-medium">
                                            • Keystage {{ $d->keystage_num }} {{ $d->description }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-[11px] text-slate-500 leading-relaxed">
                                    {!! $d->items_contents ?? '<em>No items available</em>' !!}
                                </div>

                            </div>

                            {{-- ACTIONS --}}
                            <div class="flex items-center gap-2 shrink-0">

                                @if(auth()->user()->hasAnyRole(['Super Admin','Office Admin','Office Coordinator','Warehouse Admin']))
                                    <button class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200">
                                        Edit
                                    </button>
                                @endif

                                <a href="{{ url('deliveries_details/'.$d->dr_no) }}"
                                   class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200">
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
    <div class="flex justify-center mt-8 gap-2">

        @if($page > 1)
            <a href="?page={{ $page - 1 }}&search={{ request('search') }}&status={{ request('status') }}"
               class="px-3 py-1 rounded-lg border text-sm bg-white hover:bg-slate-50">
                Prev
            </a>
        @endif

        <span class="px-3 py-1 text-sm text-slate-500">
            Page {{ $page }} / {{ $total_pages }}
        </span>

        @if($page < $total_pages)
            <a href="?page={{ $page + 1 }}&search={{ request('search') }}&status={{ request('status') }}"
               class="px-3 py-1 rounded-lg border text-sm bg-white hover:bg-slate-50">
                Next
            </a>
        @endif

    </div>

    @endif

</div>

</x-project_app-layout>