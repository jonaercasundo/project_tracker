<x-accounting_app>
    <x-slot name="header">MI Approval Queue</x-slot>
    <div class="space-y-6">
        <div><h1 class="text-2xl font-bold text-slate-900">Requests awaiting your approval</h1><p class="mt-2 text-sm text-slate-500">Review the full request before approving. Accounting review and release are separate actions.</p></div>
        @foreach(['budgets' => 'Budget approval', 'travel' => 'Final travel approval'] as $type => $heading)
            <section class="overflow-hidden rounded-2xl border bg-white"><h2 class="p-5 text-lg font-bold">{{ $heading }}</h2><div class="overflow-x-auto"><table class="w-full min-w-[650px] text-left text-sm"><thead class="bg-slate-50"><tr><th class="p-3">Reference</th><th class="p-3">Requester</th><th class="p-3">Department</th><th class="p-3">Amount</th><th class="p-3">Action</th></tr></thead><tbody>
                @forelse($$type as $record)
                    @php($budget = $type === 'budgets' ? $record : $record->budgetRequest)
                    <tr class="border-t"><td class="p-3">{{ $budget->control_id }}@if($type === 'travel') · Travel #{{ $record->id }}@endif</td><td class="p-3">{{ $budget->employee?->name }}</td><td class="p-3">{{ $budget->department }}</td><td class="p-3 font-mono">{{ $type === 'budgets' ? $record->budget_total : $record->actual_total }}<p class="text-xs text-slate-500">Currency unspecified</p></td><td class="p-3"><a class="font-semibold text-blue-700" href="{{ route($type === 'budgets' ? 'budget_requests.processing' : 'travel_liquidation.processing', $record) }}">Review request →</a></td></tr>
                @empty<tr><td class="p-6 text-slate-500" colspan="5">No eligible requests awaiting approval.</td></tr>@endforelse
            </tbody></table></div><div class="p-4">{{ $$type->links() }}</div></section>
        @endforeach
    </div>
</x-accounting_app>
