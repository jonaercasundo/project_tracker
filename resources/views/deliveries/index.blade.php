<x-project_app-layout>
<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Deliveries Tracking</h1>
            <p class="text-sm text-slate-500">Monitor delivery groups and item progress</p>
        </div>

        <div class="flex gap-2">
            <button class="px-4 py-2 text-xs font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                Add Delivery
            </button>
            <button class="px-4 py-2 text-xs font-bold rounded-lg bg-slate-800 text-white hover:bg-slate-900">
                Batch
            </button>
            <button class="px-4 py-2 text-xs font-bold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200">
                Import
            </button>
        </div>
    </div>

    {{-- LIST --}}
    <div class="space-y-4">

        @foreach($grouped_deliveries as $dr_group)

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">

                {{-- DR HEADER --}}
                <div class="flex items-center justify-between px-5 py-4 bg-slate-50 border-b border-slate-200">

                    <div class="flex items-center gap-3">
                        <input type="checkbox"
                               class="w-4 h-4 dr-checkbox"
                               value="{{ $dr_group['dr_no'] }}"
                               data-school-id="{{ $dr_group['school_id'] }}">

                        <div>
                            <div class="text-sm font-bold text-slate-900">
                                DR #{{ $dr_group['dr_no'] }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $dr_group['project_name'] }} • {{ $dr_group['school_name'] }}
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button onclick="generateARs()"
                                class="px-3 py-1.5 text-xs rounded-lg bg-slate-200 hover:bg-slate-300 font-bold">
                            QR
                        </button>

                        <button onclick="generateLabels()"
                                class="px-3 py-1.5 text-xs rounded-lg bg-slate-200 hover:bg-slate-300 font-bold">
                            Label
                        </button>
                    </div>

                </div>

                {{-- ITEMS --}}
                <div class="divide-y divide-slate-100">

                    @foreach($dr_group['deliveries'] as $d)

                        <div class="px-5 py-3 flex items-center justify-between hover:bg-slate-50">

                            {{-- LEFT --}}
                            <div class="space-y-1">
                                <div class="text-sm font-semibold text-slate-800">
                                    LOT {{ $d->lot_name }}
                                    @if($d->keystage_num)
                                        <span class="text-slate-400 font-normal">
                                            • Keystage {{ $d->keystage_num }} {{ $d->description }}
                                        </span>
                                    @endif
                                </div>

                                <div class="text-xs text-slate-500">
                                    {!! $d->items_contents ?? '<em>No items</em>' !!}
                                </div>
                            </div>

                            {{-- RIGHT ACTIONS --}}
                            <div class="flex items-center gap-2">

                                @if(auth()->user()->hasAnyRole(['Super Admin','Office Admin','Office Coordinator','Warehouse Admin']))
                                    <button class="px-3 py-1 text-xs rounded-lg bg-amber-100 text-amber-700 hover:bg-amber-200 font-semibold">
                                        Edit
                                    </button>
                                @endif

                                <a href="{{ url('deliveries_details/'.$d->dr_no) }}"
                                   class="px-3 py-1 text-xs rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200 font-semibold">
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