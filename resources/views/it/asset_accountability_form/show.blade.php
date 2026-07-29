    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Asset Details - {{ $asset->asset_code ?? 'N/A' }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <div class="bg-white rounded-xl border border-slate-200 p-8 shadow-sm">
        <!-- 2-Column Grid Layout -->
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-y-8 gap-x-12">
            
            <!-- Asset Info -->
            <div>
                <dt class="text-sm font-medium text-slate-500">Asset Name</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->asset_name ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Asset Code</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->asset_code ?? 'N/A' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-slate-500">Status</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->status ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Category</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->category ?? 'N/A' }}</dd>
            </div>

            <!-- Hardware Details -->
            <div>
                <dt class="text-sm font-medium text-slate-500">Brand</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->brand ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Model</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->model ?? 'N/A' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-slate-500">Serial Number</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->serial_number ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Specifications</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->specification ?? 'N/A' }}</dd>
            </div>

            <!-- Purchase & Warranty -->
            <div>
                <dt class="text-sm font-medium text-slate-500">Purchase Date</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->purchase_date ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Purchase Cost</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->purchase_cost ?? 'N/A' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-slate-500">Warranty Expiry</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->warranty_expiry ?? 'N/A' }}</dd>
            </div>
            <!-- Empty column to keep the grid aligned -->
            <div class="hidden md:block"></div>

            <!-- Horizontal Divider -->
            <div class="col-span-1 md:col-span-2 border-t border-slate-100 my-2"></div>

            <!-- Assignment Info -->
            <div>
                <dt class="text-sm font-medium text-slate-500">Assigned To</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->assigned_to ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Department</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ str_replace('_', ' ', $asset->department) ?? 'N/A' }}</dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-slate-500">Location</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->location ?? 'N/A' }}</dd>
            </div>
            <div class="hidden md:block"></div>

            <!-- Horizontal Divider -->
            <div class="col-span-1 md:col-span-2 border-t border-slate-100 my-2"></div>

            <!-- Metadata -->
            <div class="col-span-1 md:col-span-2">
                <dt class="text-sm font-medium text-slate-500">Asset Accountability Form Created at</dt>
                <dd class="mt-1 text-base font-bold text-slate-900">{{ $asset->created_at ?? 'N/A' }}</dd>
            </div>

        </dl>
    </div>
    </html>