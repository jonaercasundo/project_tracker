<x-project_app-layout>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900">
                Deliveries Tracking
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Monitor DR groups and item progression in real-time
            </p>
        </div>

        <div class="flex gap-2">
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

                            {{-- LEFT CONTENT --}}
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

                            {{-- RIGHT ACTIONS --}}
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

</div>

</x-project_app-layout>