<x-project-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Historical project link review</h1>
            <p class="mt-1 text-xs text-slate-500">Confirm exact identifiers between existing Bidding and Project List records. This does not change either status.</p>
        </div>
    </x-slot>

    <div class="space-y-4">
        @if(session('success'))<p role="status" class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900">{{ session('success') }}</p>@endif
        @if($errors->any())<div role="alert" class="rounded-lg bg-rose-50 p-3 text-sm text-rose-900">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <a href="{{ route('operations.lifecycle.index') }}" class="text-sm font-semibold text-blue-700 hover:underline">Back to lifecycle monitoring</a>
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600"><tr><th class="px-4 py-3">Bidding record</th><th class="px-4 py-3">Existing lots</th><th class="px-4 py-3">Exact Project List candidates</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($biddings as $bidding)
                        @php($matches = $candidates->get($bidding->getKey(), collect()))
                        <tr class="align-top">
                            <td class="px-4 py-4"><p class="font-semibold text-slate-900">#{{ $bidding->id }} · {{ $bidding->project_name }}</p><p class="mt-1 text-xs text-slate-500">Project ID {{ $bidding->project_id ?: '—' }} · Code {{ $bidding->project_code ?: '—' }} · Reference {{ $bidding->reference_no ?: '—' }}</p></td>
                            <td class="px-4 py-4 text-xs text-slate-600">{{ $bidding->lots->pluck('lot_no')->join(', ') ?: 'No bidding lots' }}</td>
                            <td class="px-4 py-4">
                                @if($matches->isEmpty())<p class="text-xs font-semibold text-amber-800">Unmatched — no exact identifier</p>
                                @else
                                    <p class="mb-2 text-xs font-semibold {{ $matches->count() > 1 ? 'text-amber-800' : 'text-emerald-800' }}">{{ $matches->count() > 1 ? 'Needs review — multiple candidates' : 'One exact candidate — confirm before linking' }}</p>
                                    @foreach($matches as $candidate)
                                        <form method="POST" action="{{ route('operations.lifecycle.reconcile.confirm') }}" class="mb-2 flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 p-2">
                                            @csrf
                                            <input type="hidden" name="bidding_id" value="{{ $bidding->getKey() }}">
                                            <input type="hidden" name="project_id" value="{{ $candidate['project']->project_id }}">
                                            <span class="min-w-0 flex-1 text-xs text-slate-800">Project #{{ $candidate['project']->project_id }} · {{ $candidate['project']->project_name }} · {{ $candidate['project']->status }}<span class="block text-slate-500">{{ implode(', ', $candidate['identifiers']) }} · Reference {{ $candidate['project']->ref_no ?: '—' }}</span></span>
                                            <button class="rounded-md bg-blue-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-800">Confirm link</button>
                                        </form>
                                    @endforeach
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-8 text-center text-slate-600">No unlinked Bidding records remain.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $biddings->links() }}
    </div>
</x-project-app-layout>
