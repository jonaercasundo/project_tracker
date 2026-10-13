<x-project-app-layout>
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Add New School</h1>
                <p class="mt-1 text-sm text-slate-500">Create a school record for the shared directory.</p>
            </div>
            <a href="{{ route('schools.index', $listQuery) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">Back to schools</a>
        </div>

        @include('schools._alerts')

        <form method="POST" action="{{ route('schools.store', $listQuery) }}" x-data="{ submitting: false }" x-on:submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }" :aria-busy="submitting" class="space-y-5">
            @csrf
            @include('schools._form', ['school' => $school ?? null])
            <div class="flex flex-wrap items-center justify-end gap-3">
                <a href="{{ route('schools.index', $listQuery) }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500">Cancel</a>
                <button type="submit" :disabled="submitting" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-60">
                    <span x-show="!submitting">Save school</span>
                    <span x-show="submitting" x-cloak>Saving...</span>
                </button>
            </div>
        </form>
    </div>
</x-project-app-layout>
