@auth
    <script>
        window.trackerLiveUpdates = { companyId: @json(request()->user()?->currentCompany()?->getKey()) };
    </script>
    <div data-live-update-status hidden role="status" aria-live="polite" class="fixed bottom-4 right-4 z-[100] rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-lg"></div>
@endauth
