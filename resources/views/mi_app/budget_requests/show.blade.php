<x-dynamic-component :component="auth()->user()->hasRole('accounting') ? 'accounting_app' : 'mi_app'">
    <div class="max-w-4xl mx-auto py-6">

        @if (session('status'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('status') }}</div>
        @endif

        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-semibold">{{ $budgetRequest->control_id }}</h1>
            <span class="px-3 py-1 rounded text-sm bg-gray-200">{{ str_replace('_', ' ', ucfirst($budgetRequest->status)) }}</span>
        </div>

        @include('mi_app.financial_history', ['activities' => $budgetRequest->activities])
        @php
            $next = match($budgetRequest->status) {
                'budget_requested' => 'Designated approver: budget approval',
                'approved' => $budgetRequest->noted_at ? 'Accounting: release confirmation' : 'Accounting: note request',
                'released' => 'Employee: confirm receipt',
                'in_progress' => 'Employee: file travel liquidation',
                default => 'Review recorded history',
            };
        @endphp
        <p class="mb-4 text-sm">Next expected action / responsible party: {{ $next }}</p>
        <p class="mb-4 text-sm">Company: {{ $budgetRequest->company?->name }}</p>
        @if($errors->any())<div class="mb-4 text-red-700">{{ $errors->first() }}</div>@endif
        <h2 class="font-semibold">Recorded releases</h2>
        @forelse($budgetRequest->releases as $release)
            <p>{{ $release->currency }} {{ $release->amount }} ? {{ $release->reference_no }} ? {{ $release->released_at }}</p>
        @empty
            <p class="mb-4 text-sm text-gray-600">No quantified release evidence recorded.</p>
        @endforelse

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-sm">
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

        @if($budgetRequest->status === 'returned_for_revision')
            @can('update', $budgetRequest)
                <div class="my-4 flex flex-wrap gap-3"><a href="{{ route('budget_requests.edit', $budgetRequest) }}" class="text-blue-700">Edit returned request</a><form method="POST" action="{{ route('budget_requests.resubmit', $budgetRequest) }}">@csrf<button class="font-semibold text-blue-700" onclick="return confirm('Resubmit this budget for approval?');">Resubmit for approval</button></form></div>
            @endcan
        @endif
        {{-- Workflow action buttons - visibility should also be gated by role/policy on the backend --}}
        <div class="flex gap-2 mb-6">
            @if($budgetRequest->status === 'budget_requested')
                @include('mi_app.approval_actions', ['record' => $budgetRequest, 'type' => 'budget'])
            @endif

            @if($budgetRequest->status === 'approved')
                @if($budgetRequest->noted_at === null)
                @can('noteByAccounting', $budgetRequest)
                <form method="POST" action="{{ route('budget_requests.note', $budgetRequest) }}">
                    @csrf
                    <button class="bg-yellow-600 text-white px-4 py-2 rounded text-sm">Note (Accounting)</button>
                </form>
                @endcan
                @endif
                @if($budgetRequest->noted_at !== null)
                @can('release', $budgetRequest)
                <form method="POST" action="{{ route('budget_requests.release', $budgetRequest) }}">
                    @csrf
                    @if(config('mi_financial.release_recording_enabled'))
                        <label>Released amount <input name="amount" required inputmode="decimal" class="border p-2"></label>
                        <label>Currency <select name="currency" required>@foreach(config('mi_financial.currencies') as $currency)<option>{{ $currency }}</option>@endforeach</select></label>
                        <label>Payment method <input name="payment_method" required class="border p-2"></label>
                        <label>Payment reference <input name="reference_no" required class="border p-2"></label>
                    @endif
                    <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm">Release Budget</button>
                </form>
                @endcan
                @endif
            @endif

            @if($budgetRequest->status === 'released')
                @can('markReceived', $budgetRequest)
                <form method="POST" action="{{ route('budget_requests.received', $budgetRequest) }}">
                    @csrf
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded text-sm">Mark Budget Received</button>
                </form>
            @endcan
            @endif

            @if($budgetRequest->status === 'in_progress' && ! $budgetRequest->liquidation && auth()->user()->can('view', $budgetRequest))
                <a href="{{ route('travel_liquidation.create', ['budget_request' => $budgetRequest->id]) }}"
                class="bg-purple-600 text-white px-4 py-2 rounded text-sm">File Liquidation</a>
            @endif

            @if($budgetRequest->liquidation && auth()->user()->can('view', $budgetRequest->liquidation))
                <a href="{{ route('travel_liquidation.show', $budgetRequest->liquidation) }}"
                class="bg-gray-700 text-white px-4 py-2 rounded text-sm">View Liquidation Report</a>
            @endif
        </div>
    </div>
</x-dynamic-component>
