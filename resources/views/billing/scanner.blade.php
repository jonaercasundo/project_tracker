<x-project_app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-700">MMC billing station</p>
                <h1 class="mt-1 text-xl font-extrabold tracking-tight text-slate-900">Delivery QR scanner</h1>
            </div>
            <nav aria-label="Billing navigation" class="flex flex-wrap gap-2 text-sm font-semibold">
                <a href="{{ route('billing.prepare') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:border-blue-500">Prepare for Billing</a>
                <a href="{{ route('billing.dashboard') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:border-blue-500">Dashboard</a>
                <a href="{{ route('billing.records.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:border-blue-500">Billing records</a>
                <a href="{{ route('billing.batches.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-700 hover:border-blue-500">Batches</a>
            </nav>
        </div>
    </x-slot>

    <div id="billing-scanner"
         class="mx-auto grid max-w-7xl gap-5 xl:grid-cols-[0.9fr_1.1fr]"
         data-lookup-url="{{ route('billing.scanner.lookup') }}"
         data-mark-url="{{ route('billing.for-billing', ['delivery' => '__delivery__']) }}"
         data-billed-url="{{ route('billing.billed', ['delivery' => '__delivery__']) }}"
         data-hold-url="{{ route('billing.hold', ['delivery' => '__delivery__']) }}"
         data-release-url="{{ route('billing.release-hold', ['delivery' => '__delivery__']) }}"
         data-can-hold="{{ auth()->user()->can('billing.exceptions.manage') ? 'true' : 'false' }}">
        <section class="space-y-4">
            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950 text-white shadow-lg shadow-slate-900/10">
                <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-cyan-300">Camera scan</p>
                        <h2 class="mt-1 font-bold">Read a delivery label</h2>
                    </div>
                    <span class="rounded-full border border-cyan-300/30 px-2.5 py-1 font-mono text-[10px] uppercase tracking-wide text-cyan-200">Scan ≠ update</span>
                </div>
                <div class="space-y-4 p-4 sm:p-5">
                    <div class="relative flex min-h-56 items-center justify-center overflow-hidden rounded-lg border border-white/10 bg-slate-900 sm:min-h-72">
                        <video id="billing-camera" class="hidden h-full max-h-96 w-full object-cover" playsinline muted aria-label="Camera preview for QR scanning"></video>
                        <div id="camera-placeholder" class="max-w-xs px-6 text-center">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl border border-cyan-300/30 bg-cyan-300/10 font-mono text-2xl text-cyan-200" aria-hidden="true">QR</div>
                            <p class="font-semibold text-white">Camera is off</p>
                            <p class="mt-2 text-sm leading-6 text-slate-400">Start the camera to scan a delivery QR. Each scan only displays status; a separate confirmation is always required.</p>
                        </div>
                        <div class="pointer-events-none absolute inset-x-8 top-1/2 h-px bg-cyan-300/80 shadow-[0_0_12px_rgba(103,232,249,0.8)]"></div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button id="start-camera" type="button" class="rounded-lg bg-cyan-300 px-4 py-2.5 text-sm font-bold text-slate-950 transition hover:bg-cyan-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300">Start camera</button>
                        <button id="stop-camera" type="button" class="hidden rounded-lg border border-white/20 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cyan-300">Stop camera</button>
                        <span class="self-center text-xs text-slate-400">Camera access requires a secure connection (HTTPS or localhost).</span>
                    </div>
                    <p id="camera-support" class="hidden rounded-lg border border-amber-300/30 bg-amber-300/10 px-3 py-2 text-sm text-amber-100" role="status"></p>
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-white">Handheld scanner</p>
                            <p id="hardware-scanner-status" class="mt-1 text-xs text-slate-400" role="status">Ready — scan labels without clicking an input. Results are added once each.</p>
                        </div>
                    </div>
                </div>
            </div>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-blue-700">Keyboard / handheld scanner</p>
                    <h2 class="mt-1 font-bold text-slate-900">Manual DR number search</h2>
                    <p class="mt-1 text-sm text-slate-500">Use this fallback if you cannot scan a label.</p>
                </div>
                <form id="manual-search" class="mt-4 flex flex-col gap-2 sm:flex-row">
                    <label for="dr-search" class="sr-only">DR number</label>
                    <input id="dr-search" name="q" autocomplete="off" maxlength="2048" required
                           class="min-w-0 flex-1 rounded-lg border-slate-300 font-mono text-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Enter a DR number">
                    <button class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Search</button>
                </form>
                <p class="mt-3 text-xs leading-5 text-slate-500">If a DR number has multiple delivery rows, select the matching school and project below. Legacy lot-only QRs are not treated as delivery IDs.</p>
            </section>
        </section>

        <section class="space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-blue-700">Current status</p>
                    <h2 class="mt-1 text-lg font-extrabold text-slate-900">Scan results</h2>
                </div>
                <p class="text-right text-xs text-slate-500">No change happens until you confirm the action.</p>
            </div>
            <div id="billing-feedback" class="hidden rounded-lg border px-4 py-3 text-sm" role="status" aria-live="polite"></div>
            <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input id="billing-select-all" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-600" disabled>
                        Select all scanned records
                    </label>
                    <button id="billing-clear-records" type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-rose-400 hover:text-rose-700" disabled>Clear scanned list</button>
                </div>
                <p id="billing-selection-summary" class="text-sm text-slate-500">No records selected.</p>
                <div id="billing-bulk-buttons" class="flex flex-wrap gap-2"></div>
                <form id="billing-bulk-billed-form" class="hidden grid gap-3 rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 sm:grid-cols-2">
                    <p id="billing-bulk-billed-label" class="sm:col-span-2 text-sm font-bold text-slate-900">Invoice details are required before confirming selected records as Billed.</p>
                    <label class="space-y-1 text-xs font-semibold text-slate-600">Invoice number
                        <input name="invoice_no" maxlength="100" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-slate-600">Invoice date
                        <input name="invoice_date" type="date" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-slate-600">Invoice total
                        <input name="amount" type="number" min="0" step="0.01" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-slate-600">Delivery billing amount (optional)
                        <input name="billing_amount" type="number" min="0" step="0.01" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-slate-600 sm:col-span-2">Billing reference
                        <input name="billing_reference" maxlength="255" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>
                    <label class="space-y-1 text-xs font-semibold text-slate-600 sm:col-span-2">Remarks
                        <textarea name="billing_remarks" rows="2" maxlength="5000" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </label>
                    <div class="sm:col-span-2">
                        <button class="rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">Review and confirm selected as Billed</button>
                    </div>
                </form>
            </section>
            <div id="billing-results" class="space-y-4">
                <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
                    <p class="font-semibold text-slate-800">Waiting for a delivery</p>
                    <p class="mt-1 text-sm text-slate-500">Scan a label or search for a DR number to view its billing status.</p>
                </div>
            </div>
        </section>
    </div>
</x-project_app-layout>
