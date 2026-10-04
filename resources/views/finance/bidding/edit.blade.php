<x-finance_app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">Edit bidding document</h1><p class="mt-1 text-xs font-medium text-slate-500">Procurement &amp; bidding management</p></x-slot>
    <x-slot name="headerActions"><a href="{{ route($biddingRoutePrefix.'.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Back to bidding</a></x-slot>
    <form method="POST" action="{{ route($biddingRoutePrefix.'.update', $project) }}" data-bidding-form data-catalog-url="{{ route($biddingRoutePrefix.'.catalog') }}" data-regions-url="{{ url('/api/regions') }}" data-provinces-url="{{ url('/api/provinces') }}" data-cities-url="{{ url('/api/cities') }}" data-barangays-url="{{ url('/api/barangays') }}">
        @csrf
        @method("PUT")
        @include('operation.bidding.partials._forms')
    </form>
    @include('operation.bidding.partials._documents')
</x-finance_app-layout>
