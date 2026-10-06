<x-mi_app :mobile-navigation="true">
    <x-slot name="header">MI Approvals</x-slot>
    <div class="space-y-6">
        <div><h1 class="text-2xl font-bold text-slate-900">Requests for approval</h1><p class="mt-2 text-sm text-slate-500">Review each transaction before deciding. Travel requests reach this queue after accounting review.</p></div>
        @if(session('status'))<p class="rounded-xl bg-green-50 p-4 text-green-800">{{ session('status') }}</p>@endif
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
            @foreach(['pending' => 'Pending Approval', 'approved' => 'Approved', 'rejected' => 'Rejected', 'returned' => 'Returned / Needs Revision', 'total' => 'Total Requests'] as $key => $label)
                <a href="{{ route('mi.approvals', ['status' => $key === 'total' ? 'all' : $key]) }}" class="rounded-2xl border border-slate-200 bg-white p-4"><p class="text-xs font-semibold text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-bold text-slate-900">{{ $summary[$key] }}</p></a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('mi.approvals') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4">
            <label class="text-xs font-semibold text-slate-600">Status<select name="status" class="mt-1 w-full rounded-lg border-slate-300 text-sm">@foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'returned' => 'Returned', 'all' => 'All'] as $value => $label)<option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-slate-600">Request type<select name="type" class="mt-1 w-full rounded-lg border-slate-300 text-sm">@foreach(['all' => 'All modules', 'budget' => 'Budget request', 'travel' => 'Travel liquidation'] as $value => $label)<option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-slate-600">Requester<select name="requester" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">All requesters</option>@foreach($requesters as $requester)<option value="{{ $requester->getKey() }}" @selected(($filters['requester'] ?? '') == $requester->getKey())>{{ $requester->name }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-slate-600">Department<input name="department" value="{{ $filters['department'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">From date<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">To date<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-600">Search<input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Reference, department or requester" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <div class="flex items-end gap-3"><button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Apply filters</button><a href="{{ route('mi.approvals') }}" class="py-2 text-sm text-slate-600">Reset</a></div>
        </form>
        @if($errors->any())<p class="text-sm text-red-700">{{ $errors->first() }}</p>@endif
        @foreach(['budgets' => 'Budget requests', 'travel' => 'Travel liquidations'] as $type => $heading)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white"><h2 class="p-5 text-lg font-bold text-slate-900">{{ $heading }}</h2><div class="overflow-x-auto"><table class="w-full min-w-[1000px] text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-600"><tr>@foreach(['Reference / Type', 'Requester / Department', 'Submitted', 'Amount', 'Stage / Status', 'Previous action / Remarks', 'Actions'] as $heading)<th class="p-3">{{ $heading }}</th>@endforeach</tr></thead><tbody>
                @forelse($$type as $record)
                    @php
                        $budget = $type === 'budgets' ? $record : $record->budgetRequest;
                        $last = $record->activities->last();
                        $pending = $record->status === ($type === 'budgets' ? 'budget_requested' : 'noted');
                        $statusLabel = $pending ? 'Pending' : ucfirst(str_replace('_', ' ', $record->status));
                    @endphp
                    <tr class="border-t border-slate-100 align-top"><td class="p-3 font-semibold">{{ $budget->control_id }}<p class="mt-1 text-xs font-normal text-slate-500">{{ $type === 'budgets' ? 'Budget request' : 'Travel liquidation #'.$record->id }}</p></td><td class="p-3">{{ ($type === 'budgets' ? $budget->employee : $record->liquidatedBy)?->name }}<p class="text-xs text-slate-500">{{ $budget->department }}</p></td><td class="p-3 text-xs">{{ ($type === 'budgets' ? $record->created_at : ($record->submitted_at ?? $record->created_at))->format('M d, Y H:i') }}</td><td class="p-3 font-mono">{{ $type === 'budgets' ? $record->budget_total : $record->actual_total }}<p class="text-xs text-slate-500">Currency unspecified</p></td><td class="p-3"><span @class(['inline-flex rounded-full px-2 py-1 text-xs font-semibold', 'bg-amber-50 text-amber-800' => $pending, 'bg-red-50 text-red-700' => $record->status === 'rejected', 'bg-blue-50 text-blue-700' => $record->status === 'returned_for_revision', 'bg-green-50 text-green-700' => ! $pending && ! in_array($record->status, ['rejected', 'returned_for_revision'])])>{{ $statusLabel }}</span><p class="mt-1 text-xs text-slate-500">{{ $pending ? 'Executive approval' : 'Decision recorded' }}</p></td><td class="max-w-xs p-3 text-xs"><p>{{ $last?->actor_name_snapshot ?? 'No previous action recorded' }}</p><p class="text-slate-500">{{ $last ? str_replace('_', ' ', $last->event) : '' }}</p><p class="mt-1 break-words">{{ $last?->note ?? $record->remarks }}</p></td><td class="p-3"><a class="font-semibold text-blue-700" href="{{ route($type === 'budgets' ? 'budget_requests.processing' : 'travel_liquidation.processing', $record) }}">View details{{ $pending ? ' / Decide' : '' }}</a></td></tr>
                @empty<tr><td class="p-6 text-slate-500" colspan="7">No eligible requests awaiting approval.</td></tr>@endforelse
            </tbody></table></div><div class="p-4">{{ $$type->links() }}</div></section>
        @endforeach
    </div>
</x-mi_app>
