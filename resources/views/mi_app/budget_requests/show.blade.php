<x-mi_app>
    <div class="max-w-4xl mx-auto py-6">

        @if (session('status'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('status') }}</div>
        @endif

        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-semibold">{{ $budgetRequest->control_id }}</h1>
            <span class="px-3 py-1 rounded text-sm bg-gray-200">{{ str_replace('_', ' ', ucfirst($budgetRequest->status)) }}</span>
        </div>

        {{-- Status timeline, mirrors: budget request > approved > accounting/release > in progress > liquidated --}}
        <div class="flex text-xs mb-6">
            @foreach(['budget_requested' => 'Requested', 'approved' => 'Approved', 'released' => 'Released', 'in_progress' => 'Received', 'liquidated' => 'Liquidated'] as $key => $label)
                @php $reached = array_search($budgetRequest->status, ['budget_requested','approved','released','in_progress','liquidated','closed']) >= array_search($key, ['budget_requested','approved','released','in_progress','liquidated']); @endphp
                <div class="flex-1 text-center {{ $reached ? 'text-green-700 font-semibold' : 'text-gray-400' }}">
                    {{ $label }}
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div><span class="text-gray-500">Employee:</span> {{ $budgetRequest->employee->name }}</div>
            <div><span class="text-gray-500">Department:</span> {{ $budgetRequest->department }}</div>
            <div><span class="text-gray-500">Place:</span> {{ $budgetRequest->place }}, {{ $budgetRequest->country }}</div>
            <div><span class="text-gray-500">Travel Dates:</span> {{ optional($budgetRequest->travel_date_from)->format('M d, Y') }} - {{ optional($budgetRequest->travel_date_to)->format('M d, Y') }}</div>
        </div>

        <h2 class="font-semibold mb-2">Budget Breakdown</h2>
        <table class="w-full text-sm border mb-4">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2 border text-left">Category</th>
                    <th class="p-2 border text-left">Particular</th>
                    <th class="p-2 border text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($budgetRequest->items as $item)
                    <tr>
                        <td class="border p-2">{{ $item->expense_category }}</td>
                        <td class="border p-2">{{ $item->particular }}</td>
                        <td class="border p-2 text-right">{{ number_format($item->budget_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-semibold">
                    <td class="border p-2" colspan="2">Total Budget</td>
                    <td class="border p-2 text-right">{{ number_format($budgetRequest->budget_total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Workflow action buttons - visibility should also be gated by role/policy on the backend --}}
        <div class="flex gap-2 mb-6">
            @if($budgetRequest->status === 'budget_requested')
                <form method="POST" action="{{ route('budget_requests.approve', $budgetRequest) }}">
                    @csrf
                    <button class="bg-green-600 text-white px-4 py-2 rounded text-sm">Approve</button>
                </form>
            @endif

            @if($budgetRequest->status === 'approved')
                <form method="POST" action="{{ route('budget_requests.note', $budgetRequest) }}">
                    @csrf
                    <button class="bg-yellow-600 text-white px-4 py-2 rounded text-sm">Note (Accounting)</button>
                </form>
                <form method="POST" action="{{ route('budget_requests.release', $budgetRequest) }}">
                    @csrf
                    <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Release Budget</button>
                </form>
            @endif

            @if($budgetRequest->status === 'released' && $budgetRequest->employee_id === auth()->id())
                <form method="POST" action="{{ route('budget_requests.received', $budgetRequest) }}">
                    @csrf
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded text-sm">Mark Budget Received</button>
                </form>
            @endif

            @if($budgetRequest->status === 'in_progress' && ! $budgetRequest->liquidation)
                <a href="{{ route('liquidation.create', ['budget_request' => $budgetRequest->id]) }}"
                class="bg-purple-600 text-white px-4 py-2 rounded text-sm">File Liquidation</a>
            @endif

            @if($budgetRequest->liquidation)
                <a href="{{ route('liquidation.show', $budgetRequest->liquidation) }}"
                class="bg-gray-700 text-white px-4 py-2 rounded text-sm">View Liquidation Report</a>
            @endif
        </div>
    </div>
</x-mi_app>
