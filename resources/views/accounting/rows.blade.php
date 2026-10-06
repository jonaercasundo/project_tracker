<div class="overflow-x-auto">
    <table class="w-full min-w-[1050px] text-left text-xs">
        <thead class="bg-slate-50 text-slate-500"><tr>@foreach(['Type / Reference', 'Employee / Department', 'Purpose', 'Amount', 'Status', 'Waiting Since', 'Required Action', 'Actions'] as $heading)<th class="px-4 py-3 font-semibold">{{ $heading }}</th>@endforeach</tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($rows as $row)
                <tr class="hover:bg-slate-50/60"><td class="px-4 py-4"><p class="font-bold text-slate-900">{{ $row->reference }}</p><p class="mt-1 text-slate-500">{{ ucfirst($row->type) }} @if($row->type === 'travel') · #{{ $row->id }}@endif</p></td><td class="px-4 py-4">{{ $row->employee ?? 'Historical actor unavailable' }}<p class="mt-1 text-slate-500">{{ $row->department ?? 'Not recorded' }}</p></td><td class="max-w-xs px-4 py-4">{{ \Illuminate\Support\Str::limit($row->purpose ?? 'Not recorded', 90) }}</td><td class="whitespace-nowrap px-4 py-4 font-mono tabular-nums">{{ $row->currency }}<br>{{ \Brick\Math\BigDecimal::of($row->amount)->toScale(2) }}</td><td class="px-4 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 font-semibold">{{ ucfirst(str_replace('_', ' ', $row->status)) }}</span>@if($row->missing)<p class="mt-2 text-amber-700">Receipt information missing</p>@endif</td><td class="whitespace-nowrap px-4 py-4 text-slate-500">{{ \Illuminate\Support\Carbon::parse($row->waiting_since)->format('M d, Y H:i') }}<p>{{ \Illuminate\Support\Carbon::parse($row->waiting_since)->diffForHumans() }}</p></td><td class="px-4 py-4">{{ match($row->stage) { 'budget-review' => 'Accounting note', 'releases' => 'Release funds', 'acknowledgment' => 'Employee acknowledgment', 'liquidation-review' => 'Accounting review', 'settlement' => 'Inspect settlement', default => 'View recorded history' } }}@if(!$row->actionable)<p class="mt-1 text-slate-400">Visibility / follow-up</p>@endif</td><td class="px-4 py-4"><a href="{{ \App\Services\AccountingWorkspace::recordUrl($row) }}" class="font-semibold text-blue-700 hover:underline">Review →</a></td></tr>
            @empty
                <tr><td colspan="8" class="px-6 py-14 text-center"><p class="text-sm font-semibold text-slate-700">No matching records</p><p class="mt-2 text-slate-500">Your authorized queue is clear, or no records match these filters.</p></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="border-t border-slate-100 p-4">{{ $rows->links() }}</div>
