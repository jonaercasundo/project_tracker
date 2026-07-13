<x-finance_app-layout>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- Header --}}
        <div class="flex items-center justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Create Item
                </h1>

                <p class="text-sm text-slate-500">
                    Add a new item to the item master.
                </p>
            </div>

            <a href="{{ route('items.index') }}"
               class="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-300 transition">

                ← Back to Items

            </a>

        </div>

        {{-- Form Card --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">

            <form action="{{ route('items.store') }}" method="POST">

                @include('operation.partials._forms')

            </form>

        </div>

    </div>

</x-finance_app-layout>