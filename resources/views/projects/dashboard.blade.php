<x-project_app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

    {{-- HEADER BLOCK --}}
    <div class="bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900 tracking-tight">Project Dashboard</h1>
            <p class="text-xs text-slate-400 font-medium mt-0.5">
                Monitor projects, deliveries, warehouse inventory, and logistics from one centralized dashboard.
            </p>
        </div>
        <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-50 border border-slate-200 rounded-xl text-[11px] font-bold text-slate-600 select-none">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            Operational
        </div>
    </div>

    {{-- STATS MATRIX CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex justify-between items-start">
            <div class="space-y-1">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Projects</p>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">{{ $totalProjects ?? 0 }}</h2>
            </div>
            <div class="w-10 h-10 bg-blue-50 border border-blue-100 text-blue-600 rounded-xl flex items-center justify-center shadow-sm shadow-blue-500/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex justify-between items-start">
            <div class="space-y-1">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pending Phase</p>
                <h2 class="text-2xl font-black text-amber-600 tracking-tight">{{ $pendingProjects ?? 0 }}</h2>
            </div>
            <div class="w-10 h-10 bg-amber-50 border border-amber-100 text-amber-600 rounded-xl flex items-center justify-center shadow-sm shadow-amber-500/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex justify-between items-start">
            <div class="space-y-1">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Delivered Assets</p>
                <h2 class="text-2xl font-black text-green-600 tracking-tight">{{ $deliveredProjects ?? 0 }}</h2>
            </div>
            <div class="w-10 h-10 bg-green-50 border border-green-100 text-green-600 rounded-xl flex items-center justify-center shadow-sm shadow-green-500/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm shadow-slate-100/50 flex justify-between items-start">
            <div class="space-y-1">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">System Cluster</p>
                <h2 class="text-2xl font-black text-emerald-600 tracking-tight">Operational</h2>
            </div>
            <div class="w-10 h-10 bg-emerald-50 border border-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center shadow-sm shadow-emerald-500/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 .621-.504 1.125-1.125 1.125H4.875c-.621 0-1.125-.504-1.125-1.125v-1.5c0-.621.504-1.125 1.125-1.125h14.25c.621 0 1.125.504 1.125 1.125v1.5zM6 12h.008v.008H6V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25H6v.008h.008v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM11.25 12h.008v.008h-.008V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.008v.008h-.008v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.5 12h.008v.008H16.5V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.008v.008h-.008v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"></path>
                </svg>
            </div>
        </div>

    </div>

    {{-- INTERACTIVE NAVIGATION ENGINE DRIVERS --}}
    <div>
        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 px-1 select-none">Application Core Control Gateways</h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <a href="{{ route('projects.index') }}"
               class="group p-5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:border-blue-500 hover:shadow-md hover:shadow-blue-500/5 transition-all duration-200 flex flex-col justify-between h-full min-h-[150px] relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-blue-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></div>
                <div class="flex items-start justify-between">
                    <div class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center transition-colors group-hover:bg-blue-600 group-hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9s2.015-9 4.5-9m0 0a9.015 9.015 0 018.447 5.7M12 3a9.015 9.015 0 00-8.447 5.7m16.894 0A9.04 9.04 0 0112 12.75c-2.32 0-4.43-.649-6.22-1.766M21 12.25a9.04 9.04 0 01-9 6.25c-2.32 0-4.43-.649-6.22-1.766"></path>
                        </svg>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 transform group-hover:translate-x-1 group-hover:text-blue-500 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                    </svg>
                </div>
                <div class="mt-4">
                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-blue-600 transition-colors">Projects</h4>
                    <p class="text-[11px] text-slate-400 font-normal mt-0.5">Initialize, monitor development, and review master timelines.</p>
                </div>
            </a>

            <a href="{{ route('deliveries.tracking') }}"
               class="group p-5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:border-indigo-500 hover:shadow-md hover:shadow-indigo-500/5 transition-all duration-200 flex flex-col justify-between h-full min-h-[150px] relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-indigo-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></div>
                <div class="flex items-start justify-between">
                    <div class="w-9 h-9 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.375M9 18h3.375m7.5-13.5v13.5c0 .621-.504 1.125-1.125 1.125H3.75A1.125 1.125 0 012.625 18V4.5c0-.621.504-1.125 1.125-1.125h5.25M16.5 5.25h.008v.008H16.5V5.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.5 10.5h.008v.008H16.5V10.5zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM16.5 15h.008v.008H16.5V15zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM12 3v3.375c0 .621.504 1.125 1.125 1.125h3.375m-.151-5.114a48.412 48.412 0 012.198 2.198m-2.198-2.198A48.214 48.214 0 0116.5 3"></path>
                        </svg>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 transform group-hover:translate-x-1 group-hover:text-indigo-500 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                    </svg>
                </div>
                <div class="mt-4">
                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-indigo-600 transition-colors">Fulfillment Deliveries</h4>
                    <p class="text-[11px] text-slate-400 font-normal mt-0.5">Track drop-off milestones and pipeline handshakes.</p>
                </div>
            </a>

            <a href="{{ route('inventory.index') }}"
               class="group p-5 bg-white border border-slate-200/60 rounded-2xl shadow-sm hover:border-emerald-500 hover:shadow-md hover:shadow-emerald-500/5 transition-all duration-200 flex flex-col justify-between h-full min-h-[150px] relative overflow-hidden">
                <div class="absolute top-0 left-0 right-0 h-[3px] bg-emerald-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-200 origin-left"></div>
                <div class="flex items-start justify-between">
                    <div class="w-9 h-9 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h12M6 10h12m-12 4h12m-12 4h12M4 4h16a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V5a1 1 0 011-1z"></path>
                        </svg>
                    </div>
                    <svg class="w-4 h-4 text-slate-300 transform group-hover:translate-x-1 group-hover:text-emerald-500 transition-all" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"></path>
                    </svg>
                </div>
                <div class="mt-4">
                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-emerald-600 transition-colors">Warehouse Inventory</h4>
                    <p class="text-[11px] text-slate-400 font-normal mt-0.5">Audit item stock counts, lot tracking, and material distribution.</p>
                </div>
            </a>

            {{-- Fixed: this was a dead "#" link styled as fully interactive/clickable.
                 Now rendered as a clearly disabled card with a "Coming soon" badge
                 instead of misleading hover affordances. --}}
            <div class="p-5 bg-slate-50 border border-slate-200/60 rounded-2xl flex flex-col justify-between h-full min-h-[150px] relative overflow-hidden cursor-not-allowed"
                 aria-disabled="true">
                <div class="flex items-start justify-between">
                    <div class="w-9 h-9 bg-slate-200 text-slate-400 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125a1.125 1.125 0 001.125-1.125V9.75M16.5 18.75a1.5 1.5 0 000-3 1.5 1.5 0 000 3zm0 0h1.5m-13.5-4.5h16.5M5.625 4.5h11.114a1.13 1.13 0 01.8.326l2.091 2.092a1.13 1.13 0 01.326.8v3.207a1.13 1.13 0 01-.326.8l-2.092 2.091a1.13 1.13 0 01-.8.326H5.625a1.125 1.125 0 01-1.125-1.125V5.625A1.125 1.125 0 015.625 4.5z"></path>
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold text-slate-400 bg-slate-200/70 px-2 py-0.5 rounded-full">Coming soon</span>
                </div>
                <div class="mt-4">
                    <h4 class="font-bold text-slate-500 text-sm">Logistics & Fleet</h4>
                    <p class="text-[11px] text-slate-400 font-normal mt-0.5">Coordinate freight dispatching, route chains, and transit telemetry.</p>
                </div>
            </div>

        </div>
    </div>
    {{-- DELIVERIES MAP --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="p-5 border-b">
            <h2 class="text-lg font-bold text-slate-800">
                Delivery Coverage - Philippines
            </h2>

            <p class="text-sm text-slate-500">
                Green markers indicate completed deliveries.
            </p>
        </div>

        <div class="grid lg:grid-cols-3">

            {{-- MAP --}}
            <div class="lg:col-span-2">
                <div id="deliveryMap" style="height:500px;"></div>
            </div>

            {{-- LIST --}}
            <div class="border-l bg-white">

                <div class="overflow-y-auto max-h-[500px] divide-y divide-slate-100">

                    @forelse($deliveries as $delivery)

                        <div class="group px-3 py-2 hover:bg-slate-50/80 transition-colors duration-150">

                            <div class="flex items-center justify-between gap-2">

                                <div class="min-w-0 flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                                    <div class="min-w-0">
                                        <h4 class="text-[12px] font-medium text-slate-700 truncate leading-tight">
                                            {{ $delivery->region }}
                                        </h4>
                                        <p class="text-[10px] text-slate-400 leading-tight">
                                            {{ number_format($delivery->total_projects) }} projects
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-1.5 py-[1px] text-[10px] font-semibold text-emerald-600 ring-1 ring-inset ring-emerald-100">
                                        {{ number_format($delivery->total_deliveries) }}
                                    </span>
                                    <span class="text-[9px] uppercase tracking-wide text-slate-300">
                                        done
                                    </span>
                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="flex items-center justify-center h-32">

                            <div class="text-center">
                                <svg class="mx-auto h-6 w-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M20 13V7a2 2 0 00-2-2h-3V3H9v2H6a2 2 0 00-2 2v6m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4"/>
                                </svg>

                                <p class="mt-1.5 text-xs font-medium text-slate-500">
                                    No deliveries found
                                </p>

                                <p class="text-[10px] text-slate-400">
                                    Delivered regions will appear here.
                                </p>
                            </div>

                        </div>

                    @endforelse

                </div>

            </div>
        </div>

    </div>

</div>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin="anonymous"
/>

<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin="anonymous"
></script>

<script>
    (function () {
        const mapEl = document.getElementById('deliveryMap');

        // Guard against double-initialization if this partial is ever
        // re-rendered without a full page reload (Livewire/Turbo/AJAX nav).
        if (!mapEl || mapEl._leaflet_id) {
            return;
        }

        const deliveries = @json($deliveries);

        const map = L.map(mapEl).setView([12.8797, 121.7740], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 18,
        }).addTo(map);

        const markers = [];

        deliveries.forEach(function (item) {
            if (item.latitude && item.longitude) {
                const marker = L.marker([item.latitude, item.longitude]).addTo(map);

                // Escape user-controlled fields before injecting into popup HTML
                // to avoid XSS via project_name/municipality/region.
                const escapeHtml = (str) =>
                    String(str ?? '').replace(/[&<>"']/g, (c) => ({
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;',
                    }[c]));

                marker.bindPopup(`
                    <b>${escapeHtml(item.region)}</b><br>
                    ${escapeHtml(item.total_projects)}<br>
                    ${escapeHtml(item.total_deliveries)}<br><br>
                    <span style="color:green;font-weight:bold;">
                    &#10003; Successfully Delivered
                    </span>
                `);

                markers.push(marker);
            }
        });

        if (markers.length) {
            const group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.2));
        }
    })();
</script>
</x-project_app-layout>