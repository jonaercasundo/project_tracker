const config = window.trackerLiveUpdates;
const badge = document.querySelector('[data-live-update-status]');
const fallbackIntervalMs = 60_000;
const seenEventIds = new Set();
let fallbackTimer;
let refreshPrompt;
let reloadTimer;
let hasUnsavedChanges = false;

if (config && badge) {
    const modulesForPath = () => {
        const path = window.location.pathname.toLowerCase();

        if (path.includes('return')) return ['deliveries', 'returns'];
        if (path.startsWith('/billing')) return ['billing'];
        if (path.includes('inventory') || path.includes('warehouse') || path.includes('asset') || path.includes('it_dashboard')) return ['inventory'];
        if (path.includes('assistant')) return ['assistant'];
        if (path.includes('taxonomy') || path.includes('mi')) return ['mi', 'accounting'];
        if (path.includes('budget') || path.includes('liquidation') || path.includes('accounting')) return ['mi', 'accounting'];
        if (path.includes('finance') || path.includes('ppl')) return path.includes('project') || path.includes('bidding') ? ['finance', 'projects'] : ['finance'];
        if (path.includes('delivery') || path.includes('logistics') || path.includes('tracking')) return ['deliveries'];
        if (path.includes('school') || path.includes('project') || path.includes('lifecycle') || path.includes('bidding')) return ['projects', 'deliveries', 'billing'];
        if (path.includes('operations') || path.includes('entry')) return ['operations', 'projects', 'deliveries'];
        if (path.includes('admin') || path.includes('user') || path.includes('role') || path.includes('company') || path.includes('profile') || path.includes('password') || path.includes('integration')) return ['administration', 'projects', 'deliveries', 'billing', 'inventory', 'returns', 'finance', 'mi', 'accounting'];
        if (path.includes('item')) return ['inventory'];

        return [];
    };

    const setStatus = (message, connected = false) => {
        badge.textContent = message;
        badge.dataset.connected = connected ? 'true' : 'false';
        badge.hidden = connected && !refreshPrompt;
    };

    const hasPendingEdits = () => hasUnsavedChanges || [...document.querySelectorAll('form')].some((form) => form.dataset.liveDirty === 'true');

    const promptForRefresh = () => {
        if (!refreshPrompt) {
            refreshPrompt = document.createElement('button');
            refreshPrompt.type = 'button';
            refreshPrompt.className = 'ml-2 rounded-md bg-blue-600 px-2 py-1 text-xs font-semibold text-white hover:bg-blue-700';
            refreshPrompt.textContent = 'Refresh';
            refreshPrompt.addEventListener('click', () => window.location.reload());
            badge.append(refreshPrompt);
        }
        badge.hidden = false;
        badge.firstChild.textContent = 'New updates are available. ';
    };

    const refreshWhenSafe = () => {
        if (document.hidden) return;
        if (hasPendingEdits()) {
            promptForRefresh();
            return;
        }
        window.location.reload();
    };

    document.addEventListener('input', (event) => {
        const form = event.target.closest('form');
        if (form) form.dataset.liveDirty = 'true';
    }, true);

    document.addEventListener('change', (event) => {
        const form = event.target.closest('form');
        if (form) form.dataset.liveDirty = 'true';
    }, true);

    if (!window.Echo || !config.companyId) {
        setStatus('Live updates unavailable - checking periodically');
        fallbackTimer = window.setInterval(refreshWhenSafe, fallbackIntervalMs);
    } else {
        const connection = window.Echo.connector.pusher.connection;
        connection.bind('state_change', ({ current }) => {
            if (current === 'connected') {
                window.clearInterval(fallbackTimer);
                fallbackTimer = undefined;
                setStatus('Live updates connected', true);
            } else {
                setStatus('Live updates reconnecting - checking periodically');
                if (!fallbackTimer) fallbackTimer = window.setInterval(refreshWhenSafe, fallbackIntervalMs);
            }
        });

        window.Echo.private(`tracker.company.${config.companyId}`)
            .listen('.tracker.modules.updated', (event) => {
                if (!event.eventId || seenEventIds.has(event.eventId)) return;
                seenEventIds.add(event.eventId);
                if (seenEventIds.size > 100) seenEventIds.delete(seenEventIds.values().next().value);
                if (!event.modules?.some((module) => modulesForPath().includes(module))) return;
                window.clearTimeout(reloadTimer);
                reloadTimer = window.setTimeout(refreshWhenSafe, 800);
            })
            .error(() => {
                setStatus('Live updates unavailable - checking periodically');
                if (!fallbackTimer) fallbackTimer = window.setInterval(refreshWhenSafe, fallbackIntervalMs);
            });
    }
}
