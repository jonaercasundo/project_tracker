<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset Details - {{ $asset->asset_code ?? 'N/A' }}</title>
    
    <!-- This ensures your Tailwind CSS still works without the layout component -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 antialiased">
    
    <div class="p-4 sm:p-8 min-h-screen">
        {{-- Main Content Card --}}
        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 sm:p-10">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-12">
                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Asset Name</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->asset_name ?? 'N/A' }}</span>
                    </div>

                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Asset Code</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->asset_code ?? 'N/A' }}</span>
                    </div>

                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Status</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->status ?? 'N/A' }}</span>
                    </div>

                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Category</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->category ?? 'N/A' }}</span>
                    </div>

                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Brand</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->brand ?? 'N/A' }}</span>
                    </div>

                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Model</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->model ?? 'N/A' }}</span>
                    </div>

                    <div class="mb-8 sm:mb-0">
                        <span class="block text-sm font-medium text-slate-500">Serial Number</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->serial_number ?? 'N/A' }}</span>
                    </div>

                    <div>
                        <span class="block text-sm font-medium text-slate-500">Specifications</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->specification ?? 'N/A' }}</span>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-12">
                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Purchase Date</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">
                            {{ $asset->purchase_date ? \Carbon\Carbon::parse($asset->purchase_date)->format('M d, Y') : 'N/A' }}
                        </span>
                    </div>

                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Purchase Cost</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">
                            {{ $asset->purchase_cost ? '₱' . number_format($asset->purchase_cost, 2) : 'N/A' }}
                        </span>
                    </div>

                    <div class="mb-8 sm:mb-0">
                        <span class="block text-sm font-medium text-slate-500">Warranty Expiry</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">
                            {{ $asset->warranty_expiry ? \Carbon\Carbon::parse($asset->warranty_expiry)->format('M d, Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-12">
                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Assigned To</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->assigned_to ?? 'Unassigned' }}</span>
                    </div>

                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Department</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">
                            {{ $asset->department ? str_replace('_', ' ', $asset->department) : 'N/A' }}
                        </span>
                    </div>

                    <div class="mb-8 sm:mb-0">
                        <span class="block text-sm font-medium text-slate-500">Location</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->location ?? 'N/A' }}</span>
                    </div>
                </div>

                <hr class="border-slate-100 my-12">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8">
                    <div class="mb-8">
                        <span class="block text-sm font-medium text-slate-500">Asset Accountability Form Created at</span>
                        <span class="block mt-1 text-base text-slate-900 font-bold">{{ $asset->created_at ?? 'Unassigned' }}</span>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</body>
</html>