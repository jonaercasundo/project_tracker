<x-project_app-layout>
    <main class="mx-auto max-w-3xl space-y-6" id="credential-created">
        <div class="rounded-3xl bg-slate-950 p-7 text-white shadow-xl sm:p-9">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-300">Credential issued · shown once</p>
            <h1 class="mt-3 text-2xl font-black">{{ $integration->name }}</h1>
            <p class="mt-2 text-sm leading-6 text-slate-300">{{ $message }}</p>
        </div>
        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500" for="key-id">X-Integration-Key-Id</label><div class="flex gap-2"><input id="key-id" readonly value="{{ $keyId }}" class="w-full rounded-xl border-slate-200 bg-slate-50 font-mono text-sm"><button type="button" data-copy="key-id" class="rounded-xl border border-slate-200 px-4 text-xs font-bold text-slate-700">Copy</button></div></div>
            <div><label class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-500" for="secret">HMAC-SHA256 signing secret</label><div class="flex gap-2"><textarea id="secret" readonly rows="3" class="w-full resize-none rounded-xl border-slate-200 bg-amber-50 font-mono text-sm text-slate-950">{{ $secret }}</textarea><button type="button" data-copy="secret" class="rounded-xl border border-slate-200 px-4 text-xs font-bold text-slate-700">Copy</button></div></div>
            @if($overlapDays)<p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Previous active signing keys expire in {{ $overlapDays }} days. Configure the new key with Logistics, then revoke old keys early if the transition is complete.</p>@endif
            <div class="rounded-xl bg-slate-50 p-4 text-xs leading-5 text-slate-600"><strong>Keep it secure.</strong> Save the secret in the Logistics App's secret manager. It is encrypted in MMC and cannot be retrieved from this page later.</div>
            <div class="flex flex-wrap gap-3"><a href="{{ route('logistics-channel.index', ['tab' => 'integrations']) }}" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-bold text-white">Return to integrations</a><a href="{{ route('logistics-channel.index', ['tab' => 'api']) }}" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700">View signing instructions</a></div>
        </section>
    </main>
    <script>
        document.querySelectorAll('[data-copy]').forEach(button => button.addEventListener('click', async () => {
            const input = document.getElementById(button.dataset.copy);
            try {
                await navigator.clipboard.writeText(input.value);
                button.textContent = 'Copied';
                window.setTimeout(() => { button.textContent = 'Copy'; }, 1500);
            } catch {
                input.focus();
                input.select();
            }
        }));
    </script>
</x-project_app-layout>
