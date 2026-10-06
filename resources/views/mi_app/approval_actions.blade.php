@can('approve', $record)
    @if($record->status === ($type === 'budget' ? 'budget_requested' : 'noted'))
        <section class="my-4 rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="font-semibold text-slate-900">Executive decision</h2>
            <p class="mt-1 text-sm text-slate-500">Review the request and supporting details above before deciding.</p>
            <form method="POST" action="{{ route('mi.approvals.decide', ['type' => $type, 'recordId' => $record->getKey()]) }}" class="mt-3 space-y-3" onsubmit="return confirm('Record this approval decision? This action will be added to the approval history.');">
                @csrf
                <label class="block text-sm font-semibold text-slate-700">Decision<select name="action" class="mt-1 w-full rounded-lg border-slate-300" onchange="this.form.elements.remarks.required = this.value !== 'approve';"><option value="approve">Approve</option><option value="reject">Reject</option><option value="return">Return for Revision</option></select></label>
                <label class="block text-sm font-semibold text-slate-700">Remarks<textarea name="remarks" maxlength="2000" class="mt-1 w-full rounded-lg border-slate-300" rows="3" placeholder="Required for rejection or return; optional for approval">{{ old('remarks') }}</textarea></label>
                <button class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Confirm decision</button>
            </form>
        </section>
    @endif
@endcan
