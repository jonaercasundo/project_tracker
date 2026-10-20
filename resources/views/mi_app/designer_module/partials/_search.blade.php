{{-- ============================= --}}
{{-- Search & Filter --}}
{{-- ============================= --}}
<style>
    .tx-search-form { display: flex; flex-direction: column; gap: 0.85rem; }
    @media (min-width: 1024px) { .tx-search-form { flex-direction: row; align-items: center; } }

    .tx-search-input-wrap { position: relative; flex: 1 1 auto; }
    .tx-search-input-wrap svg {
        position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%);
        width: 1.05rem; height: 1.05rem; color: var(--tx-ink-faint); pointer-events: none;
    }
    .tx-search-input-wrap input {
        width: 100%; border: 1px solid var(--tx-line); background: var(--tx-surface); color: var(--tx-ink);
        font-family: var(--tx-font-body); font-size: 0.875rem; padding: 0.72rem 1rem 0.72rem 2.5rem;
        border-radius: 12px; outline: none; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .tx-search-input-wrap input::placeholder { color: var(--tx-ink-faint); }
    .tx-search-input-wrap input:focus { border-color: var(--tx-primary); box-shadow: 0 0 0 4px var(--tx-primary-soft); }

    .tx-filter-wrap { width: 100%; }
    @media (min-width: 1024px) { .tx-filter-wrap { width: 12rem; } .tx-filter-wrap.wide { width: 13.5rem; } }
    .tx-filter-select-outer { position: relative; }
    .tx-filter-select-outer select {
        width: 100%; appearance: none; border: 1px solid var(--tx-line); background: var(--tx-surface); color: var(--tx-ink);
        font-family: var(--tx-font-body); font-size: 0.875rem; padding: 0.72rem 2.4rem 0.72rem 1rem;
        border-radius: 12px; outline: none; transition: border-color .15s ease, box-shadow .15s ease;
    }
    .tx-filter-select-outer select:focus { border-color: var(--tx-primary); box-shadow: 0 0 0 4px var(--tx-primary-soft); }
    .tx-filter-select-outer svg {
        position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%);
        width: 1rem; height: 1rem; color: var(--tx-ink-faint); pointer-events: none;
    }

    .tx-search-actions { display: flex; gap: 0.6rem; flex-shrink: 0; }
    .tx-btn-search {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.45rem;
        border: none; cursor: pointer; background: var(--tx-primary); color: var(--tx-primary-ink);
        font-family: var(--tx-font-body); font-size: 0.85rem; font-weight: 600;
        padding: 0.72rem 1.4rem; border-radius: 12px; transition: all .15s ease; white-space: nowrap;
    }
    .tx-btn-search:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -12px var(--tx-primary); }
    .tx-btn-clear {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
        border: 1px solid var(--tx-line); background: var(--tx-surface); color: var(--tx-ink-soft);
        font-family: var(--tx-font-body); font-size: 0.85rem; font-weight: 600;
        padding: 0.72rem 1.25rem; border-radius: 12px; text-decoration: none; transition: all .15s ease; white-space: nowrap;
    }
    .tx-btn-clear:hover { border-color: var(--tx-danger); color: var(--tx-danger); }
    .tx-cop-summary { display: inline-flex; align-items: center; gap: .45rem; margin-bottom: .75rem; padding: .55rem .8rem; border: 1px solid #fcd34d; border-radius: 10px; background: #fffbeb; color: #92400e; font-size: .8rem; font-weight: 650; }
    .tx-cop-summary svg { width: 1rem; height: 1rem; flex: none; }
</style>

<div class="tx-cop-summary" role="status">
    @if($missingCopCount > 0)
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01"/></svg>
        <span>{{ number_format($missingCopCount) }} {{ \Illuminate\Support\Str::plural('product', $missingCopCount) }} need COP updates</span>
    @else
        <span>All products have a Country of Production.</span>
    @endif
</div>

<form action="{{ route('mi_app.index') }}" method="GET" class="tx-search-form">

    {{-- Search --}}
    <div class="tx-search-input-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.2-5.2m2.2-5.3a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search item name, category, collection, designer...">
    </div>

    {{-- Missing COP --}}
    <div class="tx-filter-wrap">
        <div class="tx-filter-select-outer">
            <select name="missing_cop" aria-label="Filter products missing Country of Production">
                <option value="">All COP Statuses</option>
                <option value="1" @selected(request()->boolean('missing_cop'))>Missing COP ({{ number_format($missingCopCount) }})</option>
            </select>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </div>
    </div>

    {{-- Status --}}
    <div class="tx-filter-wrap">
        <div class="tx-filter-select-outer">
            <select name="status">
                <option value="">All Status</option>
                <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </div>
    </div>

    {{-- Country of Production --}}
    <div class="tx-filter-wrap">
        <div class="tx-filter-select-outer">
            <select name="country_of_production" aria-label="Filter by Country of Production">
                <option value="">All Countries</option>
                <option value="__not_set" @selected(request('country_of_production') === '__not_set')>Not Set</option>
                @foreach(collect(['Vietnam', 'China'])->merge($countriesOfProduction)->unique()->sort() as $country)
                    <option value="{{ $country }}" @selected(request('country_of_production') === $country)>{{ $country }}</option>
                @endforeach
            </select>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </div>
    </div>

    {{-- Buttons --}}
    <div class="tx-search-actions">
        <button type="submit" class="tx-btn-search">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.2-5.2m2.2-5.3a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            Search
        </button>

        @if(request()->filled('search') || request()->filled('status') || request()->filled('classification') || request()->filled('country_of_production') || request()->filled('missing_cop'))
            <a href="{{ route('mi_app.index') }}" class="tx-btn-clear">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                Clear
            </a>
        @endif
    </div>

</form>
