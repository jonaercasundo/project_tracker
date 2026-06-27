@if ($paginator->hasPages())
    <div class="flex items-center justify-between px-4 py-3 border-t">

        <div class="text-xs text-slate-500">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }}
            of {{ $paginator->total() }}
        </div>

        <div>
            {{ $paginator->links('pagination::tailwind') }}
        </div>

    </div>
@endif