@php
    $isExistingProduct = isset($product) && $product->exists;
@endphp
<style>
    .tx-price-comparison-reference { display: flex; flex-direction: column; align-items: stretch; gap: 8px; min-width: 0; }
    .tx-price-comparison-reference .tx-label { display: block; margin: 0; }
    .tx-price-comparison-reference input { min-width: 0; }
    .tx-price-comparison-reference a { max-width: 100%; overflow-wrap: anywhere; text-transform: none; }
    .tx-comparison-image { position: relative; width: 100%; height: 220px; overflow: hidden; border: 1px solid var(--tx-line, #e2e8f0); border-radius: 10px; background: var(--tx-surface, #fff); }
    .tx-comparison-image img { display: block; width: 100%; height: 100%; padding: 12px; object-fit: contain; }
    .tx-comparison-image img.is-loading { opacity: 0; }
    .tx-comparison-image [data-image-status] { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; padding: 20px; text-align: center; color: var(--tx-ink-faint, #64748b); font-size: 12px; }
    .tx-comparison-image [data-image-status][hidden] { display: none; }
</style>
<section class="tx-card lvl-3" id="price_comparison" aria-labelledby="price_comparison_heading">
    <div class="tx-card-head"><span class="tx-card-icon">06</span><div><h2 id="price_comparison_heading">AI-Assisted Product Research</h2><p>Research comparable products in USD, check the source listings, then choose which comparisons to save.</p></div></div>
    <div class="tx-card-body space-y-5">
        <div data-name-fields class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <label class="tx-label">Optional keywords<input id="comparison_query" class="tx-field mt-2" type="text" maxlength="300" placeholder="Materials, style, dimensions, or other refinements"></label>
            <div class="tx-price-comparison-reference">
                <label class="tx-label" for="comparison_reference_url">Reference link (optional)</label>
                <input id="comparison_reference_url" class="tx-field" type="url" maxlength="2048" placeholder="https://example.com/product">
                <a id="comparison_reference_preview" href="#" target="_blank" rel="noopener noreferrer" class="hidden text-xs text-blue-700 underline">Open reference link</a>
            </div>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(180px,260px)_auto] md:items-end">
            <label class="tx-label">Maximum results<input id="comparison_limit" class="tx-field mt-2" type="number" min="1" max="20" value="8"></label>
            <button type="button" id="comparison_search" class="tx-btn-small w-fit">Find Prices</button>
        </div>
        <p id="comparison_status" role="status" aria-live="polite" class="tx-hint">Click Find Prices to research the product name and description. The agent searches for comparable products and checks listing sources. Review results before saving; nothing is saved automatically.</p>
        <div class="space-y-2 rounded-xl border border-slate-200 p-4">
            <label for="comparison_product_url" class="tx-label">Add product by link</label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <input id="comparison_product_url" type="text" inputmode="url" maxlength="2048" placeholder="https://store.example.com/product" class="tx-field min-w-0 flex-1" aria-describedby="comparison_link_status">
                <button type="button" id="comparison_add_link" class="tx-btn-small shrink-0">Add product by link</button>
            </div>
            <p id="comparison_link_status" role="status" aria-live="polite" class="tx-hint">Add a public USD product listing. Details are read from the page and added for review; nothing is saved automatically.</p>
            <div id="comparison_manual_fields" hidden class="space-y-3 border-t border-slate-200 pt-4" aria-labelledby="comparison_manual_heading">
                <h3 id="comparison_manual_heading" class="font-semibold">Enter listing details</h3>
                <p class="tx-hint">Review any prefilled values against the listing. Missing details must be entered by you. Adding a comparison does not save it.</p>
                <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="min-w-0"><label for="comparison_manual_product_name" class="tx-label">Product name</label><input id="comparison_manual_product_name" type="text" maxlength="255" class="tx-field mt-2 min-w-0 w-full"></div>
                    <div class="min-w-0"><label for="comparison_manual_store_name" class="tx-label">Store name</label><input id="comparison_manual_store_name" type="text" maxlength="255" class="tx-field mt-2 min-w-0 w-full"></div>
                    <div class="min-w-0"><label for="comparison_manual_price" class="tx-label">Price</label><input id="comparison_manual_price" type="text" maxlength="30" class="tx-field mt-2 min-w-0 w-full" inputmode="decimal"></div>
                    <div class="min-w-0"><label for="comparison_manual_currency" class="tx-label">Currency (USD only)</label><input id="comparison_manual_currency" type="text" maxlength="3" class="tx-field mt-2 min-w-0 w-full"></div>
                    <div class="min-w-0"><label for="comparison_manual_photo_url" class="tx-label">Product image URL (optional)</label><input id="comparison_manual_photo_url" type="text" maxlength="2048" class="tx-field mt-2 min-w-0 w-full" inputmode="url"></div>
                    <div class="min-w-0"><label for="comparison_manual_product_link" class="tx-label">Product page URL</label><input id="comparison_manual_product_link" type="text" maxlength="2048" class="tx-field mt-2 min-w-0 w-full" inputmode="url"></div>
                </div>
                <button type="button" id="comparison_confirm_manual" class="tx-btn-small">Add comparison</button>
            </div>
        </div>
        <div id="comparison_setup_error" hidden role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-semibold">AI research setup required</p>
            <p id="comparison_setup_message" class="mt-1"></p>
            <p class="mt-2">Ask an administrator to check the selected product research provider in AI Provider Settings, fund that provider’s API account, and run Test research web search with its model and key.</p>
        </div>
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
                    @php
                        $comparisonImage = \App\Helpers\PublicImageUrl::normalize($comparison->photo_url);
                        if ($comparison->photo_path && ! str_starts_with($comparison->photo_path, '/') && ! preg_match('#(^|/)\.\.?(/|$)|[\\\\:]#', $comparison->photo_path)) {
                            $comparisonImage = Storage::disk('public')->url($comparison->photo_path);
                        }
                    @endphp
                    <article class="min-w-0 rounded-xl border border-slate-200 bg-white p-4">
                        <div class="tx-comparison-image" data-comparison-image>
                            @if($comparisonImage)<img src="{{ $comparisonImage }}" alt="{{ $comparison->product_name }}" width="640" height="440" loading="lazy" decoding="async" referrerpolicy="no-referrer" class="is-loading">@endif
                            <span data-image-status>{{ $comparisonImage ? 'Loading product image…' : 'No product image available' }}</span>
                        </div>
                        <div class="mt-4 min-w-0"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $comparison->store_name }}</p><h3 class="mt-1 font-semibold text-slate-900">{{ $comparison->product_name }}</h3><p class="mt-1">{{ $comparison->currency }} {{ number_format((float) $comparison->price, 2) }}</p><a href="{{ $comparison->product_link }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block break-all text-sm text-blue-700 underline">View product</a></div>
                    </article>
                @endforeach
            @endif
        </div>
    </div>
</section>
<script>
(() => {
    const searchUrl = @json(route('mi_app.price-comparisons.search'));
    const linkUrl = @json(route('mi_app.price-comparisons.from-link'));
    const manualUrl = @json(route('mi_app.price-comparisons.manual'));
    const saveUrl = @json($isExistingProduct ? route('mi_app.price-comparisons.store', $product) : null);
    const token = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
    const resultsNode = document.getElementById('comparison_results');
    const statusNode = document.getElementById('comparison_status');
    const saveButton = document.getElementById('comparison_save');
    const productNameInput = document.getElementById('item_name');
    const setupError = document.getElementById('comparison_setup_error');
    const displayedUrls = new Set();
    let pendingRequests = 0;
    let savingComparisons = false;
    const setResultsBusy = (change) => {
        pendingRequests += change;
        if (pendingRequests > 0) resultsNode.setAttribute('aria-busy', 'true');
        else resultsNode.removeAttribute('aria-busy');
    };
    const productKey = (value) => {
        try {
            const url = new URL(value.trim());
            if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) return null;
            if (!url.hash.startsWith('#/') && !url.hash.startsWith('#!')) url.hash = '';
            for (const key of [...url.searchParams.keys()]) {
                const lower = key.toLowerCase();
                if (lower.startsWith('utm_') || ['gclid', 'fbclid', 'msclkid', 'dclid', 'mc_cid', 'mc_eid', '_ga', '_gl'].includes(lower)) url.searchParams.delete(key);
            }
            url.searchParams.sort();
            return url.href;
        } catch { return null; }
    };
    const hasLoadedImage = (checkbox) => {
        const card = checkbox.closest('article');
        const image = card?.querySelector('img');
        return card?.dataset.imageReady === 'true' && image?.complete && image.naturalWidth > 0 && image.naturalHeight > 0;
    };
    const selectedResults = () => [...resultsNode.querySelectorAll('input[type="checkbox"]:checked')].filter(hasLoadedImage).map((checkbox) => {
        const { store_name, product_name, photo_url, price, currency, product_link } = JSON.parse(checkbox.dataset.result);
        return { store_name, product_name, photo_url, price, currency, product_link };
    });
    const updateSaveButton = () => { saveButton.disabled = savingComparisons || selectedResults().length === 0; };
    const safeHttpUrl = (value) => {
        try { const url = new URL(value); return ['http:', 'https:'].includes(url.protocol) ? url.href : null; }
        catch { return null; }
    };
    const safeImageUrl = (value) => {
        if (typeof value !== 'string' || value.trim().length > 2048 || /[\x00-\x20\x7f\\]/.test(value.trim())) return null;
        try {
            const url = new URL(value.trim());
            if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) return null;
            const host = url.hostname.toLowerCase().replace(/^\[|\]$/g, '').replace(/\.$/, '');
            if (host.includes(':')) {
                if (!/^[23][0-9a-f]{3}:/.test(host) || /^2001:db8:/.test(host)) return null;
            } else if (/^\d+\.\d+\.\d+\.\d+$/.test(host)) {
                const [a, b, c] = host.split('.').map(Number);
                if ([0, 10, 127].includes(a) || a >= 224 || (a === 100 && b >= 64 && b <= 127) || (a === 169 && b === 254) || (a === 172 && b >= 16 && b <= 31) || (a === 192 && (b === 168 || (b === 0 && [0, 2].includes(c)))) || (a === 198 && ([18, 19].includes(b) || (b === 51 && c === 100))) || (a === 203 && b === 0 && c === 113)) return null;
            } else if (!host.includes('.') || /(?:^|\.)(?:localhost|local|internal|lan|home|localdomain|test|invalid|onion)$/.test(host) || host.endsWith('.home.arpa')) return null;
            return value.trim();
        } catch { return null; }
    };
    const initializeImage = (container) => {
        const image = container.querySelector('img');
        if (!image) return;
        const status = container.querySelector('[data-image-status]');
        const loaded = () => { image.classList.remove('is-loading'); status.hidden = true; };
        const unavailable = () => { image.remove(); status.hidden = false; status.textContent = 'Product image unavailable'; };
        image.addEventListener('load', loaded, { once: true });
        image.addEventListener('error', unavailable, { once: true });
        if (image.complete && image.currentSrc) {
            if (image.naturalWidth > 0) loaded();
            else unavailable();
        }
    };
    document.querySelectorAll('#saved_comparisons [data-comparison-image]').forEach(initializeImage);
    const referenceInput = document.getElementById('comparison_reference_url');
    const referencePreview = document.getElementById('comparison_reference_preview');
    referenceInput.addEventListener('input', () => {
        const safeUrl = safeHttpUrl(referenceInput.value.trim());
        if (safeUrl) { referencePreview.href = safeUrl; }
        else { referencePreview.href = '#'; }
        referencePreview.classList.toggle('hidden', !safeUrl);
    });
    const loadResultImage = (result) => new Promise((resolve) => {
        if (!result.photo_url) { resolve(null); return; }
        const image = new Image(640, 440);
        image.alt = result.product_name;
        image.decoding = 'async';
        image.referrerPolicy = 'no-referrer';
        // Detached lazy images do not load reliably; preload eagerly and reuse this exact element.
        image.loading = 'eager';
        const finish = (loaded) => {
            clearTimeout(timeout);
            image.onload = null; image.onerror = null;
            if (!loaded) image.removeAttribute('src');
            resolve(loaded ? image : null);
        };
        const timeout = setTimeout(() => finish(false), 12000);
        image.onload = () => finish(image.naturalWidth > 0 && image.naturalHeight > 0);
        image.onerror = () => finish(false);
        image.src = result.photo_url;
    });
    let appendQueue = Promise.resolve();
    const appendResults = (results) => {
        const pending = appendQueue.then(() => loadAndAppendResults(results));
        appendQueue = pending.catch(() => {});
        return pending;
    };
    const loadAndAppendResults = async (results) => {
        let added = 0;
        let duplicates = 0;
        const safeResults = results.filter((result) => result && typeof result.store_name === 'string' && result.store_name.trim() && typeof result.product_name === 'string' && result.product_name.trim() && ['number', 'string'].includes(typeof result.price) && String(result.price).trim() !== '' && Number.isFinite(Number(result.price)) && Number(result.price) >= 0 && Number(result.price) <= 999999999999.99 && result.currency === 'USD' && safeHttpUrl(result.product_link)).map((result) => {
            const photoUrl = safeImageUrl(result.photo_url);
            return { ...result, price: Number(result.price).toFixed(2), currency: 'USD', product_link: safeHttpUrl(result.product_link), photo_url: photoUrl, image_status: photoUrl ? 'available' : (result.image_status || (result.photo_url ? 'unresolved' : 'missing')) };
        });
        const images = await Promise.all(safeResults.map(loadResultImage));
        safeResults.forEach((result, index) => {
            const sourceKey = productKey(result.product_link);
            if (!sourceKey) return;
            const canonicalKey = productKey(result.canonical_url || result.product_link);
            const keys = [sourceKey];
            if (canonicalKey && sourceKey && new URL(canonicalKey).search === new URL(sourceKey).search) keys.push(canonicalKey);
            if (keys.some(key => displayedUrls.has(key))) { duplicates++; return; }
            const image = images[index];
            if (!image) return;
            keys.forEach(key => displayedUrls.add(key));
            added++;
            const card = document.createElement('article'); card.className = 'min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-4';
            const media = document.createElement('div'); media.className = 'tx-comparison-image'; media.dataset.comparisonImage = '';
            card.dataset.imageReady = 'true';
            image.loading = 'lazy';
            image.addEventListener('error', () => {
                card.dataset.imageReady = 'false';
                card.remove();
                keys.forEach(key => displayedUrls.delete(key));
                const staged = document.getElementById('price_comparisons_json');
                if (staged?.value) {
                    try { staged.value = JSON.stringify(JSON.parse(staged.value).filter(offer => !keys.includes(productKey(offer.product_link)))); }
                    catch { staged.value = ''; }
                }
                updateSaveButton();
            }, { once: true });
            media.append(image);
            card.append(media);
            const checkbox = document.createElement('input'); checkbox.type = 'checkbox'; checkbox.dataset.result = JSON.stringify(result); checkbox.setAttribute('aria-label', `Select ${result.product_name}`); checkbox.addEventListener('change', updateSaveButton);
            const row = document.createElement('div'); row.className = 'mt-4 flex items-start gap-3'; row.append(checkbox);
            const details = document.createElement('div'); details.className = 'min-w-0 flex-1';
            const store = document.createElement('p'); store.className = 'text-xs font-semibold uppercase text-slate-500'; store.textContent = result.store_name;
            const title = document.createElement('h3'); title.className = 'font-semibold'; title.textContent = result.product_name;
            const price = document.createElement('p'); price.textContent = `${result.currency} ${result.price}`;
            const link = document.createElement('a'); link.href = result.product_link; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.className = 'break-all text-sm text-blue-700 underline'; link.textContent = 'View product';
            details.append(store, title, price, link); row.append(details); card.append(row); resultsNode.append(card);
            if (result.source === 'manual') {
                const source = document.createElement('p'); source.className = 'mt-2 text-xs text-slate-500'; source.textContent = 'Added by product link'; details.append(source);
            }
            if (typeof result.match_reason === 'string' && result.match_reason.trim()) {
                const reason = document.createElement('p'); reason.className = 'mt-2 text-sm text-slate-600'; reason.textContent = `Why comparable: ${result.match_reason}`; details.append(reason);
            }
            if (typeof result.price_evidence === 'string' && result.price_evidence.trim()) {
                const evidence = document.createElement('p'); evidence.className = 'mt-2 text-xs text-slate-500'; evidence.textContent = `Listing details: ${result.price_evidence}`; details.append(evidence);
            }
        });
        updateSaveButton();
        return { added, duplicates };
    };
    document.getElementById('comparison_search').addEventListener('click', async (event) => {
        const limit = document.getElementById('comparison_limit');
        if (!productNameInput?.value.trim()) { statusNode.textContent = 'Enter a product name before searching.'; productNameInput?.focus(); return; }
        if (!limit.reportValidity() || !referenceInput.reportValidity()) return;
        const button = event.currentTarget; button.disabled = true; button.textContent = 'Researching…'; statusNode.textContent = 'Researching comparable products and checking source listings…'; setupError.hidden = true;
        setResultsBusy(1);
        const data = new FormData(); data.append('product_name', productNameInput.value); data.append('description', document.getElementById('description')?.value || ''); data.append('query', document.getElementById('comparison_query').value); data.append('reference_url', referenceInput.value); data.append('max_results', limit.value);
        try {
            const response = await fetch(searchUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: data });
            if (!response.headers.get('content-type')?.includes('application/json')) { throw new Error(response.status === 419 ? 'Your session expired. Reload the page before searching again.' : 'The search service returned an unexpected response. Try again.'); }
            const payload = await response.json();
            if (!response.ok) {
                const message = (Object.values(payload.errors || {}).flat().join(' ') || payload.message || 'Search failed.') + (payload.request_id ? ` (Request ID: ${payload.request_id})` : '');
                if (payload.setup_required) {
                    document.getElementById('comparison_setup_message').textContent = message; setupError.hidden = false; statusNode.textContent = 'Research could not start. Resolve the provider setup issue below, then try again.';
                } else { statusNode.textContent = message; }
                return;
            }
            if (!Array.isArray(payload.results)) throw new Error('The search service returned an unreadable result. Your current comparisons were kept.');
            statusNode.textContent = 'Checking product images…';
            const { added, duplicates } = await appendResults(payload.results.slice(0, Number(limit.value)));
            statusNode.textContent = added ? `${added} new comparison(s) added.${duplicates ? ` ${duplicates} already added.` : ''} Review and select offers before saving.` : (duplicates ? 'These products are already added. Your existing cards and selections were kept.' : (payload.results.length ? 'No matching products with available images were found.' : 'No matching product offers were found. Your existing comparisons were kept.'));
        }
        catch (error) { statusNode.textContent = error instanceof TypeError ? 'Could not reach the search service. Try again.' : error.message; }
        finally { button.disabled = false; button.textContent = 'Find Prices'; setResultsBusy(-1); }
    });
    const productUrlInput = document.getElementById('comparison_product_url');
    const linkStatus = document.getElementById('comparison_link_status');
    const manualFields = document.getElementById('comparison_manual_fields');
    const manualKeys = ['product_name', 'store_name', 'price', 'currency', 'photo_url', 'product_link'];
    const showManualFields = (values = {}) => {
        for (const key of manualKeys) document.getElementById(`comparison_manual_${key}`).value = values[key] ?? '';
        manualFields.hidden = false;
    };
    manualFields.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); document.getElementById('comparison_confirm_manual').click(); }
    });
    document.getElementById('comparison_confirm_manual').addEventListener('click', async (event) => {
        const details = Object.fromEntries(manualKeys.map((key) => [key, document.getElementById(`comparison_manual_${key}`).value.trim()]));
        if (displayedUrls.has(productKey(details.product_link))) { linkStatus.textContent = 'This product is already added.'; return; }
        const button = event.currentTarget;
        button.disabled = true; button.textContent = 'Adding comparison…'; setResultsBusy(1);
        try {
            const response = await fetch(manualUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(details) });
            const payload = await response.json();
            if (!response.ok) { linkStatus.textContent = Object.values(payload.errors || {}).flat().join(' ') || payload.message || 'Check the listing details and try again.'; return; }
            if (!Array.isArray(payload.results) || payload.results.length !== 1) throw new Error('The comparison service returned unreadable details. Your current comparisons were kept.');
            const { added, duplicates } = await appendResults(payload.results);
            linkStatus.textContent = added ? 'Comparison added for review. Select it and click Save selected when ready.' : (duplicates ? 'This product is already added.' : 'This product has no loadable image. Enter a working product image URL to add it.');
            if (added || duplicates) manualFields.hidden = true;
        } catch { linkStatus.textContent = 'Could not reach the comparison service. Your entered details and current comparisons were kept.'; }
        finally { button.disabled = false; button.textContent = 'Add comparison'; setResultsBusy(-1); }
    });
    document.getElementById('comparison_add_link').addEventListener('click', async (event) => {
        const url = safeImageUrl(productUrlInput.value.trim());
        if (!url) { linkStatus.textContent = 'Enter a public HTTP or HTTPS product page URL without credentials.'; productUrlInput.focus(); return; }
        if (displayedUrls.has(productKey(url))) { linkStatus.textContent = 'This product is already added.'; return; }
        const button = event.currentTarget;
        button.disabled = true; button.textContent = 'Reading listing…'; linkStatus.textContent = 'Reading product details from the listing…'; setResultsBusy(1);
        try {
            const response = await fetch(linkUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ url }) });
            if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('The listing service returned an unexpected response. Your current comparisons were kept.');
            const payload = await response.json();
            if (!response.ok) { linkStatus.textContent = Object.values(payload.errors || {}).flat().join(' ') || payload.message || 'Could not retrieve the product listing.'; if (payload.fallback) showManualFields(payload.fallback); return; }
            if (!Array.isArray(payload.results) || payload.results.length !== 1) throw new Error('The listing did not return complete product details. No comparison was added.');
            const { added, duplicates } = await appendResults(payload.results);
            if (!added && !duplicates) showManualFields(payload.results[0]);
            linkStatus.textContent = added ? 'Product added for review. Select its checkbox and click Save selected when ready.' : (duplicates ? 'This product is already added.' : 'This product has no loadable image. Enter a working product image URL to add it.');
        } catch (error) { linkStatus.textContent = error instanceof TypeError ? 'Could not reach the listing service. Enter the listing details manually.' : error.message; showManualFields({ product_link: url }); }
        finally { button.disabled = false; button.textContent = 'Add product by link'; setResultsBusy(-1); }
    });
    productUrlInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') { event.preventDefault(); document.getElementById('comparison_add_link').click(); }
    });
    document.getElementById('comparison_select_all').addEventListener('click', () => { resultsNode.querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = hasLoadedImage(input)); updateSaveButton(); });
    document.getElementById('comparison_clear_selection').addEventListener('click', () => { resultsNode.querySelectorAll('input[type="checkbox"]').forEach((input) => input.checked = false); updateSaveButton(); });
    saveButton.addEventListener('click', async () => {
        if (savingComparisons) return;
        const checked = [...resultsNode.querySelectorAll('input[type="checkbox"]:checked')].filter(hasLoadedImage);
        const selected = selectedResults(); if (!selected.length) return;
        if (selected.length > 20) { statusNode.textContent = 'Select up to 20 comparisons to save at a time.'; return; }
        if (!saveUrl) { document.getElementById('price_comparisons_json').value = JSON.stringify(selected); statusNode.textContent = `${selected.length} selected comparison(s) staged. They will be saved with the product.`; return; }
        savingComparisons = true; updateSaveButton(); statusNode.textContent = 'Saving selected comparisons…';
        try {
            const response = await fetch(saveUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ results: selected }) });
            const payload = await response.json(); statusNode.textContent = Object.values(payload.errors || {}).flat().join(' ') || payload.message || 'Could not save selected comparisons.';
            if (response.ok) {
                checked.forEach((checkbox) => {
                    const card = checkbox.closest('article');
                    if (!card.querySelector('[data-comparison-saved]')) {
                        const saved = document.createElement('p'); saved.dataset.comparisonSaved = ''; saved.className = 'mt-2 text-xs font-semibold text-slate-500'; saved.textContent = 'Saved comparison'; card.append(saved);
                    }
                });
            }
        }
        catch { statusNode.textContent = 'Could not save selected comparisons. Try again.'; }
        finally { savingComparisons = false; updateSaveButton(); }
    });
})();
</script>
