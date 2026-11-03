@php($isExistingProduct = isset($product) && $product->exists)
<section class="tx-card lvl-3" id="price_comparison" aria-labelledby="price_comparison_heading">
    <div class="tx-card-head"><span class="tx-card-icon">06</span><div><h2 id="price_comparison_heading">AI-Assisted Price Comparison</h2><p>Find market offers, review them, then choose which comparisons to save.</p></div></div>
    <div class="tx-card-body space-y-5">
        <div data-name-fields class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <label class="tx-label">Product name<input id="comparison_product_name" class="tx-field mt-2" type="text" value="{{ old('item_name', $product->item_name ?? '') }}" maxlength="255" placeholder="Uses the product name above"></label>
            <label class="tx-label">Optional search refinement<input id="comparison_query" class="tx-field mt-2" type="text" maxlength="300" placeholder="Short description or keywords"></label>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(180px,260px)_auto] md:items-end">
            <label class="tx-label">Maximum results<input id="comparison_limit" class="tx-field mt-2" type="number" min="1" max="20" value="8"></label>
            <button type="button" id="comparison_search" class="tx-btn-small w-fit">Find Prices</button>
        </div>
        <p id="comparison_status" role="status" aria-live="polite" class="tx-hint">Search uses OpenAI web search. Offers without a cited product page are excluded.</p>
        @if($isExistingProduct)
            <div class="flex flex-wrap gap-2"><button type="button" id="comparison_select_all" class="tx-btn-small">Select all</button><button type="button" id="comparison_clear_selection" class="tx-btn-small">Clear selection</button><button type="button" id="comparison_save" class="tx-btn-small" disabled>Save selected</button></div>
        @else
            <input type="hidden" name="price_comparisons_json" id="price_comparisons_json" value="{{ old('price_comparisons_json') }}">
            <div class="flex flex-wrap gap-2"><button type="button" id="comparison_select_all" class="tx-btn-small">Select all</button><button type="button" id="comparison_clear_selection" class="tx-btn-small">Clear selection</button><button type="button" id="comparison_save" class="tx-btn-small" disabled>Save selected</button></div>
            <p class="tx-hint">On a new product, selected comparisons are staged and saved with the product after you click Save Product.</p>
        @endif
        <div id="comparison_results" class="grid grid-cols-1 gap-3 lg:grid-cols-2" aria-live="polite"></div>
        <div id="saved_comparisons" class="grid grid-cols-1 gap-3 lg:grid-cols-2">
            @if($isExistingProduct)
                @foreach($product->priceComparisons as $comparison)
                    <article class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex gap-4">
                            @if($comparison->photo_path)<img src="{{ Storage::disk('public')->url($comparison->photo_path) }}" alt="{{ $comparison->product_name }}" class="h-20 w-20 rounded-lg object-cover">@elseif($comparison->photo_url)<img src="{{ $comparison->photo_url }}" alt="{{ $comparison->product_name }}" loading="lazy" referrerpolicy="no-referrer" class="h-20 w-20 rounded-lg object-cover">@endif
                            <div class="min-w-0"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $comparison->store_name }}</p><h3 class="mt-1 font-semibold text-slate-900">{{ $comparison->product_name }}</h3><p class="mt-1">{{ $comparison->currency }} {{ number_format((float) $comparison->price, 2) }}</p><a href="{{ $comparison->product_link }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block break-all text-sm text-blue-700 underline">View product</a></div>
                        </div>
                    </article>
                @endforeach
            @endif
        </div>
    </div>
</section>
<script>
(() => {
    const searchUrl = @json(route('mi_app.price-comparisons.search'));
    const saveUrl = @json($isExistingProduct ? route('mi_app.price-comparisons.store', $product) : null);
    const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
    const resultsNode = document.getElementById('comparison_results');
    const statusNode = document.getElementById('comparison_status');
    const saveButton = document.getElementById('comparison_save');
    document.getElementById('item_name')?.addEventListener('input', (event) => {
        document.getElementById('comparison_product_name').value = event.currentTarget.value;
    });
    const selectedResults = () => [...resultsNode.querySelectorAll('input[type="checkbox"]:checked')].map((checkbox) => JSON.parse(checkbox.dataset.result));
    const safeHttpUrl = (value) => {
        try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) ? url.href : null; }
        catch { return null; }
    };
    const renderResults = (results) => {
        resultsNode.replaceChildren();
        const safeResults = results.filter((result) => result && typeof result.store_name === 'string' && result.store_name.trim() && typeof result.product_name === 'string' && result.product_name.trim() && Number.isFinite(Number(result.price)) && Number(result.price) >= 0 && typeof result.currency === 'string' && /^[a-z]{3}$/i.test(result.currency) && safeHttpUrl(result.product_link) && (!result.photo_url || safeHttpUrl(result.photo_url))).map((result) => ({ ...result, price: Number(result.price).toFixed(2), currency: result.currency.toUpperCase(), product_link: safeHttpUrl(result.product_link), photo_url: result.photo_url ? safeHttpUrl(result.photo_url) : null }));
        safeResults.forEach((result) => {
            const card = document.createElement('article'); card.className = 'rounded-xl border border-slate-200 bg-slate-50 p-4';
            const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.dataset.result = JSON.stringify(result); checkbox.setAttribute('aria-label', `Select ${result.product_name}`); checkbox.addEventListener('change', () => saveButton.disabled = selectedResults().length === 0);
            const row = document.createElement('div'); row.className = 'flex items-start gap-3'; row.append(checkbox);
            if (result.photo_url) { const image = document.createElement('img'); image.src = result.photo_url; image.alt = result.product_name; image.referrerPolicy = 'no-referrer'; image.className = 'h-20 w-20 rounded object-cover'; row.append(image); }
            const details = document.createElement('div'); details.className = 'min-w-0 flex-1';
            const store = document.createElement('p'); store.className = 'text-xs font-semibold uppercase text-slate-500'; store.textContent = result.store_name;
            const title = document.createElement('h3'); title.className = 'font-semibold'; title.textContent = result.product_name;
            const price = document.createElement('p'); price.textContent = `${result.currency} ${result.price}`;
            const link = document.createElement('a'); link.href = result.product_link; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.className = 'break-all text-sm text-blue-700 underline'; link.textContent = 'View product';
            details.append(store, title, price, link); row.append(details); card.append(row); resultsNode.append(card);
        });
        saveButton.disabled = safeResults.length === 0;
    };
    document.getElementById('comparison_search').addEventListener('click', async (event) => {
        const button = event.currentTarget; button.disabled = true; button.textContent = 'Searching…'; statusNode.textContent = 'Searching for current product offers…';
        const data = new FormData(); data.append('product_name', document.getElementById('comparison_product_name').value); data.append('query', document.getElementById('comparison_query').value); data.append('max_results', document.getElementById('comparison_limit').value);
        try { const response = await fetch(searchUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: data }); const payload = await response.json(); renderResults(payload.results || []); statusNode.textContent = (payload.message || (response.ok ? 'Review the results before saving.' : 'Search failed.')) + (payload.request_id ? ` (Request ID: ${payload.request_id})` : ''); }
        catch { statusNode.textContent = 'Could not reach the search service. Try again.'; }
        finally { button.disabled = false; button.textContent = 'Find Prices'; }
    });
    document.getElementById('comparison_select_all').addEventListener('click', () => { resultsNode.querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = true); saveButton.disabled = selectedResults().length === 0; });
    document.getElementById('comparison_clear_selection').addEventListener('click', () => { resultsNode.querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = false); saveButton.disabled = true; });
    saveButton.addEventListener('click', async () => {
        const selected = selectedResults(); if (!selected.length) return;
        if (!saveUrl) { document.getElementById('price_comparisons_json').value = JSON.stringify(selected); statusNode.textContent = `${selected.length} selected comparison(s) staged. They will be saved with the product.`; return; }
        saveButton.disabled = true; statusNode.textContent = 'Saving selected comparisons…';
        try { const response = await fetch(saveUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ results: selected }) }); const payload = await response.json(); statusNode.textContent = payload.message || 'Could not save selected comparisons.'; if (response.ok) window.location.reload(); }
        catch { statusNode.textContent = 'Could not save selected comparisons. Try again.'; saveButton.disabled = false; }
    });
})();
</script>
