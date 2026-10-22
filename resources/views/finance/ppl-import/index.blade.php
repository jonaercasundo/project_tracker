<x-finance_app-layout>
    <main class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">MMC · Project finance</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">PPL workbook import</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-600">Upload a PPL Monitoring .xlsx file to stage a row-by-row preview. Existing PPL Forms are matched read-only and are never created or changed by this import.</p>
            </div>
            <a href="{{ route('finance.plans.index') }}" class="text-sm font-semibold text-emerald-800 hover:underline">Financial plans</a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
            <h2 class="text-lg font-semibold text-slate-900">Stage a workbook</h2>
            <p class="mt-1 text-sm text-slate-600">Required worksheets: 1. FORM, 2. FACTORY COST, 3. MUNICIPAL TAX &amp; INSURANCE, and PPL. Formula results are read from the workbook’s saved values for reconciliation.</p>
            <form class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-end" method="POST" action="{{ route('finance.ppl-import.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="min-w-0 flex-1">
                    <label for="file" class="mb-2 block text-sm font-medium text-slate-700">Excel workbook (.xlsx, up to 20 MB)</label>
                    <input id="file" name="file" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required class="block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700">
                    @error('file')<p class="mt-1 text-sm text-rose-700">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Upload and preview</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h2 class="font-semibold text-slate-900">Import history</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Workbook</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Previewed</th><th class="px-5 py-3 text-right">Rows</th><th class="px-5 py-3 text-right">Action</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($batches as $batch)
                            <tr><td class="px-5 py-4 font-medium text-slate-900">{{ $batch->original_file_name }}</td><td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($batch->status) }}</span></td><td class="px-5 py-4 text-slate-600">{{ $batch->previewed_at?->format('M j, Y g:i A') }}</td><td class="px-5 py-4 text-right tabular-nums text-slate-700">{{ $batch->summary['form_rows'] ?? 0 }}</td><td class="px-5 py-4 text-right"><a href="{{ route('finance.ppl-import.show', $batch) }}" class="font-semibold text-emerald-800 hover:underline">Review</a></td></tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-500">No staged workbooks yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">{{ $batches->links() }}</div>
        </section>
    </main>
</x-finance_app-layout>
