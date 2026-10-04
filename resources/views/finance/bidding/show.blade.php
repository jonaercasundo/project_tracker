<x-finance_app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">Bidding document</h1><p class="mt-1 text-xs font-medium text-slate-500">{{ $project->project_id }}</p></x-slot>
    <x-slot name="headerActions"><div class="flex flex-wrap items-center gap-2"><a href="{{ route($biddingRoutePrefix.'.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Back</a>@can('update', $project)<a href="{{ route($biddingRoutePrefix.'.edit', $project) }}" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Edit bidding</a>@endcan</div></x-slot>
    @include('operation.bidding.partials._show')
    @include('operation.bidding.partials._documents')
</x-finance_app-layout>
