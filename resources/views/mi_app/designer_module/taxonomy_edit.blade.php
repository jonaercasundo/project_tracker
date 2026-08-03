<x-mi_app>

    {{-- Copy ALL your CSS from taxonomy.blade.php --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    <style>
        .tx-console {
            --tx-bg: #F5F6F3;
            --tx-surface: #FFFFFF;
            --tx-ink: #171B1A;
            --tx-ink-soft: #616B66;
            --tx-ink-faint: #9AA39C;
            --tx-line: #E2E5DF;
            --tx-primary: #2F5D50;
            --tx-primary-ink: #FFFFFF;
            --tx-primary-soft: #E5EEE9;
            --tx-accent: #C7703C;
            --tx-accent-soft: #F5E7DB;
            --tx-danger: #B3432E;
            --tx-danger-soft: #F3E4E0;
            --tx-lvl-1: #2F5D50;
            --tx-lvl-1-soft: #E5EEE9;
            --tx-lvl-2: #35618C;
            --tx-lvl-2-soft: #E3EBF2;
            --tx-lvl-3: #7A4F98;
            --tx-lvl-3-soft: #ECE4F1;
            --tx-lvl-4: #C7703C;
            --tx-lvl-4-soft: #F5E7DB;
            --tx-font-display: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
            --tx-font-body: 'Inter', ui-sans-serif, system-ui, sans-serif;
            --tx-font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
            font-family: var(--tx-font-body);
            background: var(--tx-bg);
            color: var(--tx-ink);
        }
        .tx-console.dark {
            --tx-bg: #12151A;
            --tx-surface: #191D22;
            --tx-ink: #EDEFEA;
            --tx-ink-soft: #9AA39C;
            --tx-ink-faint: #6B746E;
            --tx-line: #262B31;
            --tx-primary-soft: #1C2723;
            --tx-lvl-1-soft: #1C2723;
            --tx-lvl-2-soft: #1A222B;
            --tx-lvl-3-soft: #221C29;
            --tx-lvl-4-soft: #2A2019;
            --tx-danger-soft: #2A1D1A;
        }

        .tx-display { font-family: var(--tx-font-display); letter-spacing: -0.01em; }
        .tx-mono { font-family: var(--tx-font-mono); letter-spacing: 0.02em; }

        .tx-shell {
            max-width: 78rem;
            margin: 0 auto;
            padding: 2.5rem 1.5rem 5rem;
        }

        /* Header */
        .tx-header {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
            justify-content: space-between;
            padding-bottom: 1.75rem;
            border-bottom: 1px solid var(--tx-line);
            margin-bottom: 2rem;
        }
        .tx-eyebrow {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--tx-ink-faint);
            margin-bottom: 0.6rem;
        }
        .tx-eyebrow a { color: var(--tx-ink-soft); text-decoration: none; }
        .tx-eyebrow a:hover { color: var(--tx-primary); }
        .tx-title { font-size: 2rem; font-weight: 700; line-height: 1.1; }
        .tx-subtitle { color: var(--tx-ink-soft); font-size: 0.925rem; margin-top: 0.5rem; max-width: 38rem; }
        .tx-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--tx-line);
            background: var(--tx-surface);
            color: var(--tx-ink);
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 0.6rem 1.1rem;
            border-radius: 999px;
            text-decoration: none;
            transition: all .15s ease;
        }
        .tx-back:hover { border-color: var(--tx-primary); color: var(--tx-primary); transform: translateX(-2px); }

        /* Ladder / signature nav */
        .tx-ladder {
            display: flex;
            align-items: stretch;
            gap: 0;
            margin-bottom: 2.5rem;
            border: 1px solid var(--tx-line);
            border-radius: 18px;
            overflow: hidden;
            background: var(--tx-surface);
        }
        .tx-rung {
            flex: 1 1 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.1rem;
            text-decoration: none;
            color: var(--tx-ink);
            position: relative;
            border-right: 1px solid var(--tx-line);
            transition: background .15s ease;
        }
        .tx-rung:last-child { border-right: none; }
        .tx-rung:hover { background: var(--tx-primary-soft); }
        .tx-rung-dot {
            width: 0.65rem; height: 0.65rem; border-radius: 999px; flex-shrink: 0;
            box-shadow: 0 0 0 4px var(--dot-soft, transparent);
        }
        .tx-rung-label { font-size: 0.8rem; font-weight: 600; }
        .tx-rung-sub { font-size: 0.7rem; color: var(--tx-ink-faint); }
        .tx-rung::after {
            content: '';
            position: absolute;
            right: -1px; top: 50%;
            width: 6px; height: 6px;
            transform: translateY(-50%) rotate(45deg);
            border-top: 1px solid var(--tx-line);
            border-right: 1px solid var(--tx-line);
            background: var(--tx-surface);
            z-index: 1;
        }
        .tx-rung:last-child::after { display: none; }

        /* Cards */
        .tx-card {
            background: var(--tx-surface);
            border: 1px solid var(--tx-line);
            border-radius: 20px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            scroll-margin-top: 1.5rem;
        }
        .tx-card-head {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 1.35rem 1.75rem;
            border-bottom: 1px solid var(--tx-line);
        }
        .tx-card-icon {
            width: 2.25rem; height: 2.25rem;
            display: flex; align-items: center; justify-content: center;
            border-radius: 10px;
            flex-shrink: 0;
            font-family: var(--tx-font-mono);
            font-weight: 600;
            font-size: 0.8rem;
        }
        .tx-card-head h2 { font-family: var(--tx-font-display); font-size: 1.02rem; font-weight: 600; }
        .tx-card-head p { font-size: 0.78rem; color: var(--tx-ink-soft); margin-top: 0.15rem; }
        .tx-card-body {
            padding: 1.75rem;
            display: grid;
            grid-template-columns: repeat(1, minmax(0,1fr));
            gap: 1.35rem;
        }
        @media (min-width: 768px) { .tx-card-body.cols-2 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        @media (min-width: 1024px) {
            .tx-card-body.cols-4 { grid-template-columns: repeat(4, minmax(0,1fr)); }
            .tx-card-body.cols-4-btn { grid-template-columns: repeat(4, minmax(0,1fr)); }
        }

        /* Fields */
        .tx-label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--tx-ink-soft);
            margin-bottom: 0.55rem;
        }
        .tx-required { color: var(--tx-danger); }
        .tx-field {
            width: 100%;
            border: 1px solid var(--tx-line);
            background: var(--tx-bg);
            color: var(--tx-ink);
            font-size: 0.875rem;
            padding: 0.72rem 1rem;
            border-radius: 12px;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .tx-field::placeholder { color: var(--tx-ink-faint); }
        .tx-field:focus {
            border-color: var(--tx-primary);
            background: var(--tx-surface);
            box-shadow: 0 0 0 4px var(--tx-primary-soft);
        }
        .tx-select-wrap { position: relative; }
        .tx-select-wrap select { appearance: none; padding-right: 2.5rem; }
        .tx-select-wrap svg {
            position: absolute; right: 0.9rem; top: 50%; transform: translateY(-50%);
            width: 1rem; height: 1rem; color: var(--tx-ink-faint); pointer-events: none;
        }
        .tx-error {
            color: var(--tx-danger);
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.4rem;
        }

        /* Buttons */
        .tx-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            background: var(--tx-primary);
            color: var(--tx-primary-ink);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.75rem 1.1rem;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }
        .tx-btn:hover { opacity: 0.92; box-shadow: 0 8px 20px -8px var(--tx-primary); transform: translateY(-1px); }
        .tx-btn-static {
            width: auto;
        }

        /* Row-level action buttons (Edit / Archive) inside the table */
        .tx-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        .tx-actions .tx-btn,
        .tx-inline-form {
            width: auto;
        }
        .tx-actions .tx-btn {
            padding: 0.4rem 0.85rem;
            font-size: 0.75rem;
            border-radius: 8px;
            text-decoration: none;
        }
        .tx-btn-edit {
            background: var(--tx-primary-soft);
            color: var(--tx-primary);
        }
        .tx-btn-edit:hover { opacity: 1; background: var(--tx-primary); color: var(--tx-primary-ink); box-shadow: none; transform: none; }
        .tx-btn-archive {
            background: var(--tx-danger-soft);
            color: var(--tx-danger);
        }
        .tx-btn-archive:hover { opacity: 1; background: var(--tx-danger); color: #fff; box-shadow: none; transform: none; }

        /* Level accents per section */
        .lvl-1 .tx-card-icon { background: var(--tx-lvl-1-soft); color: var(--tx-lvl-1); }
        .lvl-1 .tx-rung-dot { background: var(--tx-lvl-1); --dot-soft: var(--tx-lvl-1-soft); }
        .lvl-2 .tx-card-icon { background: var(--tx-lvl-2-soft); color: var(--tx-lvl-2); }
        .lvl-2 .tx-rung-dot { background: var(--tx-lvl-2); --dot-soft: var(--tx-lvl-2-soft); }
        .lvl-3 .tx-card-icon { background: var(--tx-lvl-3-soft); color: var(--tx-lvl-3); }
        .lvl-3 .tx-rung-dot { background: var(--tx-lvl-3); --dot-soft: var(--tx-lvl-3-soft); }
        .lvl-4 .tx-card-icon { background: var(--tx-lvl-4-soft); color: var(--tx-lvl-4); }
        .lvl-4 .tx-rung-dot { background: var(--tx-lvl-4); --dot-soft: var(--tx-lvl-4-soft); }
        .lvl-m .tx-card-icon { background: var(--tx-accent-soft); color: var(--tx-accent); }

        /* Search / filter toolbar */
        .tx-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 1.25rem 1.75rem;
            border-bottom: 1px solid var(--tx-line);
            align-items: center;
        }
        .tx-search { position: relative; flex: 1 1 16rem; }
        .tx-search svg {
            position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%);
            width: 1rem; height: 1rem; color: var(--tx-ink-faint); pointer-events: none;
        }
        .tx-search input.tx-field { padding-left: 2.5rem; }
        .tx-toolbar-select { flex: 0 1 13rem; }
        .tx-toolbar-select select.tx-field { padding-top: 0.72rem; padding-bottom: 0.72rem; }
        .tx-toolbar-reset {
            border: 1px solid var(--tx-line);
            background: transparent;
            color: var(--tx-ink-soft);
            font-size: 0.78rem;
            font-weight: 600;
            padding: 0.68rem 1rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all .15s ease;
        }
        .tx-toolbar-reset:hover { border-color: var(--tx-danger); color: var(--tx-danger); }
        .tx-result-count {
            font-size: 0.75rem;
            color: var(--tx-ink-faint);
            padding: 0.85rem 1.75rem 0;
        }
        .tx-empty-state {
            padding: 3rem 1.75rem;
            text-align: center;
            color: var(--tx-ink-faint);
            font-size: 0.875rem;
        }

        /* Pagination */
        .tx-pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 1.75rem;
            border-top: 1px solid var(--tx-line);
        }
        .tx-pagination-info { font-size: 0.78rem; color: var(--tx-ink-soft); }
        .tx-pagination-controls { display: flex; align-items: center; gap: 0.6rem; }
        .tx-page-size.tx-field { width: auto; padding: 0.5rem 0.85rem; font-size: 0.78rem; }
        .tx-page-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.1rem; height: 2.1rem;
            border: 1px solid var(--tx-line);
            background: var(--tx-surface);
            border-radius: 10px;
            cursor: pointer;
            color: var(--tx-ink);
            transition: all .15s ease;
        }
        .tx-page-btn svg { width: 1rem; height: 1rem; }
        .tx-page-btn:hover:not(:disabled) { border-color: var(--tx-primary); color: var(--tx-primary); }
        .tx-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
        .tx-page-current { font-size: 0.8rem; font-weight: 600; color: var(--tx-ink-soft); min-width: 3.5rem; text-align: center; }

        /* Table / tree */
        .tx-table-wrap { overflow-x: auto; }
        table.tx-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        table.tx-table thead th {
            text-align: left;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--tx-ink-faint);
            padding: 0.9rem 1.2rem;
            border-bottom: 1px solid var(--tx-line);
            background: var(--tx-bg);
        }
        table.tx-table tbody td {
            padding: 0.85rem 1.2rem;
            border-bottom: 1px solid var(--tx-line);
            vertical-align: middle;
        }
        table.tx-table tbody tr:last-child td { border-bottom: none; }
        table.tx-table tbody tr:hover { background: var(--tx-bg); }
        .tx-code-badge {
            font-family: var(--tx-font-mono);
            font-size: 0.72rem;
            font-weight: 600;
            padding: 0.3rem 0.6rem;
            border-radius: 7px;
            background: var(--tx-ink);
            color: var(--tx-bg);
            display: inline-block;
            white-space: nowrap;
        }
        .tx-dash { color: var(--tx-ink-faint); }
        .tx-crumb { display: flex; align-items: center; flex-wrap: wrap; gap: 0.35rem; font-size: 0.8rem; }
        .tx-crumb span.node { color: var(--tx-ink); }
        .tx-crumb span.arrow { color: var(--tx-ink-faint); }
        .tx-swatch {
            width: 0.5rem; height: 0.5rem; border-radius: 999px; display: inline-block; margin-right: 0.5rem; flex-shrink: 0;
        }
        .tx-cell-primary { display:flex; align-items:center; }
    </style>
    <div class="tx-console">
        <div class="tx-shell">

            {{-- Header --}}
            <div class="tx-header">
                <div>
                    <div class="tx-eyebrow">
                        <a href="{{ route('mi_app.settings') }}">Taxonomy Console</a>
                        <span>/</span>
                        <span>Edit</span>
                    </div>

                    <h1 class="tx-title tx-display">
                        Edit {{ ucwords(str_replace('_',' ', $entityType)) }}
                    </h1>

                    <p class="tx-subtitle">
                        Update taxonomy information.
                    </p>
                </div>

                <a href="{{ route('mi_app.settings') }}" class="tx-back">
                    ← Back
                </a>
            </div>

            <div class="tx-card">

                <form method="POST"
                      action="{{ route('taxonomy.update',[
                            'type'=>$entityType,
                            'product'=>$item->id
                      ]) }}">

                    @csrf
                    @method('PUT')

                    <input type="hidden"
                           name="entity_type"
                           value="{{ $entityType }}">

                    <div class="tx-card-head">
                        <span class="tx-card-icon">✎</span>

                        <div>
                            <h2>Edit {{ ucwords(str_replace('_',' ',$entityType)) }}</h2>
                        </div>
                    </div>

                    <div class="tx-card-body cols-2">
                        @if($entityType=='category')

<div>
    <label class="tx-label">Code</label>
    <input type="text"
           class="tx-field"
           value="{{ $item->code }}"
           readonly>
</div>

<div>
    <label class="tx-label">
        Name
    </label>

    <input type="text"
           name="name"
           value="{{ old('name',$item->name) }}"
           class="tx-field">
</div>

@endif
@if($entityType=='sub_category')

<div>
<label class="tx-label">Category</label>

<select name="category_id"
        class="tx-field">

@foreach($categories as $category)

<option value="{{ $category->id }}"
@if($item->category_id==$category->id) selected @endif>

{{ $category->name }}

</option>

@endforeach

</select>

</div>

<div>

<label class="tx-label">Name</label>

<input type="text"
name="name"
class="tx-field"
value="{{ old('name',$item->name) }}">

</div>

@endif
@if($entityType=='product_type')

<div>

<label class="tx-label">Sub Category</label>

<select name="sub_category_id"
class="tx-field">

@foreach($subCategories as $sub)

<option value="{{ $sub->id }}"
@if($sub->id==$item->sub_category_id) selected @endif>

{{ $sub->name }}

</option>

@endforeach

</select>

</div>

<div>

<label class="tx-label">Name</label>

<input
type="text"
name="name"
class="tx-field"
value="{{ old('name',$item->name) }}">

</div>

@endif
@if($entityType=='collection')

<div>

<label class="tx-label">
Product Type
</label>

<select
name="product_type_id"
class="tx-field">

@foreach($productTypes as $type)

<option value="{{ $type->id }}"
@if($type->id==$item->product_type_id) selected @endif>

{{ $type->name }}

</option>

@endforeach

</select>

</div>

<div>

<label class="tx-label">
Collection Name
</label>

<input
type="text"
name="name"
class="tx-field"
value="{{ old('name',$item->name) }}">

</div>

@endif
</div>

<div style="padding:0 1.75rem 1.75rem; display:flex; gap:1rem;">

<button
class="tx-btn"
type="submit">

Save Changes

</button>

<a href="{{ route('mi_app.settings') }}"
class="tx-back">

Cancel

</a>

</div>

</form>

</div>

</div>

</div>

</x-mi_app>