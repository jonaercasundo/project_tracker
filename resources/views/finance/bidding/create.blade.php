<x-finance_app-layout>
    <x-slot name="header"><h1 class="text-lg font-semibold text-slate-900">Create bidding document</h1><p class="mt-1 text-xs font-medium text-slate-500">Procurement &amp; bidding management</p></x-slot>
    <x-slot name="headerActions"><a href="{{ route($biddingRoutePrefix.'.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Back to bidding</a></x-slot>
    <form method="POST" action="{{ route($biddingRoutePrefix.'.store') }}" data-bidding-form data-catalog-url="{{ route($biddingRoutePrefix.'.catalog') }}" data-regions-url="{{ url('/api/regions') }}" data-provinces-url="{{ url('/api/provinces') }}" data-cities-url="{{ url('/api/cities') }}" data-barangays-url="{{ url('/api/barangays') }}">
        @csrf

        @include('operation.bidding.partials._forms')
    </form>
    <p class="mt-6 text-xs text-slate-500">Save this bidding document to create folders and upload files.</p>
</x-finance_app-layout>
