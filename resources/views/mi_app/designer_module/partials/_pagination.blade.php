@if ($paginator->hasPages())
    <div class="flex flex-col gap-3 border-t border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">

        {{-- Results information --}}
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5">
                <i class="ti ti-list-details text-blue-500"></i>

                @if ($paginator->total() > 0)
                    Showing
                    <span class="font-semibold text-slate-700">
                        {{ $paginator->firstItem() }}
                    </span>

                    to

                    <span class="font-semibold text-slate-700">
                        {{ $paginator->lastItem() }}
                    </span>

                    of

                    <span class="font-semibold text-slate-700">
                        {{ number_format($paginator->total()) }}
                    </span>

                    results
                @else
                    No results found
                @endif
            </span>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center">
            {{ $paginator->onEachSide(1)->links('pagination::tailwind') }}
        </div>

    </div>
@endif
