<x-app-layout>
    <x-slot name="header">API Access / Integrations</x-slot>

    <div class="max-w-3xl mx-auto p-6 flex flex-col gap-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">Back to Administrator dashboard</a>

        <section class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col gap-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">JARVIS Integration</h1>
                <p class="text-sm text-slate-500 mt-2">External automation and AI integration</p>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div><dt class="text-slate-500">Status</dt><dd class="font-semibold {{ $token ? 'text-emerald-700' : 'text-slate-700' }}">{{ $token ? 'Active' : 'Not Configured' }}</dd></div>
                <div><dt class="text-slate-500">Token Name</dt><dd class="font-semibold text-slate-900">JARVIS</dd></div>
                <div><dt class="text-slate-500">Created</dt><dd>{{ $token?->created_at?->format('M j, Y g:i A') ?? 'Not yet generated' }}</dd></div>
                <div><dt class="text-slate-500">Last Used</dt><dd>{{ $token?->last_used_at?->format('M j, Y g:i A') ?? 'Never' }}</dd></div>
            </dl>

            @if ($token)
                <p class="text-sm text-slate-600">Token hidden permanently. Regenerate to obtain a new token.</p>
            @endif
            <p class="text-sm text-slate-500">Permission: <code>jarvis:read</code>. This token provides access to the JARVIS API only.</p>

            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route($token ? 'integrations.jarvis.update' : 'integrations.jarvis.store') }}" data-jarvis-action="{{ $token ? 'regenerate' : 'generate' }}">
                    @csrf
                    @if ($token) @method('PUT') @endif
                    <x-primary-button disabled>{{ $token ? 'Regenerate Token' : 'Generate API Token' }}</x-primary-button>
                </form>
                @if ($token)
                    <form method="POST" action="{{ route('integrations.jarvis.destroy') }}" data-jarvis-action="revoke">
                        @csrf
                        @method('DELETE')
                        <x-danger-button disabled>Revoke Token</x-danger-button>
                    </form>
                @endif
            </div>
            <p id="jarvis-error" role="alert" class="text-sm text-red-700" hidden></p>
            <noscript><p class="text-sm text-slate-600">Enable JavaScript to manage tokens and display the one-time result securely.</p></noscript>
        </section>
    </div>

    <dialog id="jarvis-token-dialog" aria-labelledby="jarvis-token-title" class="rounded-2xl p-6 w-full max-w-lg border border-slate-200 shadow-xl backdrop:bg-slate-900/50">
        <div class="flex flex-col gap-4">
            <h2 id="jarvis-token-title" class="text-lg font-bold text-slate-900">JARVIS API Token Created</h2>
            <p class="text-sm text-slate-600">Copy this token now. For security, this token will not be displayed again.</p>
            <label for="jarvis-plain-token" class="text-sm font-semibold">JARVIS API Token</label>
            <textarea id="jarvis-plain-token" readonly spellcheck="false" autocomplete="off" class="w-full rounded-lg border-slate-300 font-mono text-sm" rows="3"></textarea>
            <p id="jarvis-copy-status" role="status" class="text-sm text-slate-600"></p>
            <div class="flex gap-3">
                <x-primary-button type="button" id="jarvis-copy">Copy Token</x-primary-button>
                <x-secondary-button type="button" id="jarvis-close">Close</x-secondary-button>
            </div>
        </div>
    </dialog>

    <script>
        (() => {
            const dialog = document.getElementById('jarvis-token-dialog');
            const tokenField = document.getElementById('jarvis-plain-token');
            const error = document.getElementById('jarvis-error');
            const copyStatus = document.getElementById('jarvis-copy-status');
            let busy = false;
            document.querySelectorAll('[data-jarvis-action] button').forEach(button => button.disabled = false);

            const clearToken = () => {
                tokenField.value = '';
                copyStatus.textContent = '';
            };

            document.querySelectorAll('[data-jarvis-action]').forEach(form => {
                form.addEventListener('submit', async event => {
                    event.preventDefault();
                    if (busy) return;

                    const action = form.dataset.jarvisAction;
                    if (action === 'regenerate' && !window.confirm('Regenerating this token will immediately invalidate the current JARVIS connection. You will need to update the token in JARVIS.')) return;
                    if (action === 'revoke' && !window.confirm('JARVIS will immediately lose access to MMC Tracker.')) return;

                    busy = true;
                    error.hidden = true;
                    document.querySelectorAll('[data-jarvis-action] button').forEach(button => button.disabled = true);
                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            credentials: 'same-origin',
                            cache: 'no-store',
                            headers: { 'Accept': 'application/json' },
                            body: new FormData(form),
                        });
                        if (!response.ok) throw new Error('Unable to manage the token. Refresh the page and try again.');
                        if (action === 'revoke') {
                            window.location.reload();
                            return;
                        }
                        const result = await response.json();
                        tokenField.value = result.token;
                        delete result.token;
                        dialog.showModal();
                    } catch (failure) {
                        clearToken();
                        error.textContent = failure.message;
                        error.hidden = false;
                        busy = false;
                        document.querySelectorAll('[data-jarvis-action] button').forEach(button => button.disabled = false);
                    }
                });
            });

            document.getElementById('jarvis-copy').addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(tokenField.value);
                    copyStatus.textContent = 'Token copied.';
                } catch {
                    tokenField.select();
                    copyStatus.textContent = 'Select and copy the token manually.';
                }
            });
            document.getElementById('jarvis-close').addEventListener('click', () => dialog.close());
            dialog.addEventListener('close', () => {
                clearToken();
                window.location.reload();
            });
            window.addEventListener('pagehide', clearToken);
            window.addEventListener('pageshow', event => {
                if (event.persisted) {
                    clearToken();
                    window.location.reload();
                }
            });
        })();
    </script>
</x-app-layout>
