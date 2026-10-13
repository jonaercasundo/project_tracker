<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Add New Item</h1>
                <p class="mt-1 text-sm text-slate-500">Create a standalone item record.</p>
            </div>
            <a href="{{ route('catalog-items.index', $listQuery) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">Back to items</a>
        </div>

        @include('items._alerts')

        <form method="POST" action="{{ route('catalog-items.store', $listQuery) }}" x-data="{ submitting: false }" x-on:submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" :aria-busy="submitting" class="space-y-5">
            @csrf
            @include('items._form')
            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('catalog-items.index', $listQuery) }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a>
                <button type="submit" :disabled="submitting" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 disabled:cursor-wait disabled:opacity-60">
                    <span x-show="!submitting">Save item</span>
                    <span x-show="submitting" x-cloak>Saving...</span>
                </button>
            </div>
        </form>
    </div>
</x-project-app-layout>
