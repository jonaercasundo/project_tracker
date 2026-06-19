<!DOCTYPE html>
<html>
<head>
    <title>Pinterest Home Decor Trends</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    <style>
        .filter-bar {
            background: #fff;
            border-radius: 12px;
            padding: 20px 24px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }

        .pin-card-img {
            height: 240px;
            object-fit: cover;
        }

        .select2-container {
            width: 100% !important;
        }

        .hashtag-badge {
            background: #f0f0f0;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 12px;
            color: #555;
        }

        #loadingOverlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,0.85);
            z-index: 9999;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 5px solid #dee2e6;
            border-top-color: #000;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body class="bg-light pt-5">

{{-- NAVBAR --}}
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">

        <a class="navbar-brand fw-semibold">
            📌 Pinterest Trend Analyzer
        </a>

        <div class="collapse navbar-collapse">

            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('tiktok.trends') }}">TikTok</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('pinterest.trends') }}">Pinterest</a>
                </li>
            </ul>

        </div>
    </div>
</nav>

{{-- LOADING --}}
<div id="loadingOverlay">
    <div class="spinner"></div>
    <div class="fw-semibold fs-5">Fetching Pinterest trends...</div>
    <div class="text-muted" id="loadingKeywords"></div>
    <small class="text-muted">Analyzing high-engagement pins...</small>
</div>

<div class="container py-5">

    <h2 class="mb-1">📌 Pinterest Trends</h2>
    <p class="text-muted mb-4">Search inspiration keywords (home decor, interior design, aesthetic rooms)</p>

    {{-- FILTER --}}
    <div class="filter-bar mb-4">
        <form method="GET" action="{{ route('pinterest.trends') }}">

            <div class="row g-3 align-items-end">

                {{-- KEYWORDS --}}
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Keywords</label>
                    <select name="keywords[]"
                            id="keywordSelect"
                            class="form-select"
                            multiple>
                        @php
                            $active = request('keywords', ['homedecor']);
                        @endphp

                        @foreach($active as $tag)
                            <option value="{{ $tag }}" selected>{{ $tag }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Press Enter to add keywords</small>
                </div>

                {{-- BUTTON --}}
                <div class="col-md-3">
                    <button class="btn btn-dark w-100">
                        🔍 Search Trends
                    </button>
                </div>

                <div class="col-md-3">
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary w-100">
                        Reset
                    </a>
                </div>

            </div>
        </form>
    </div>

    {{-- ACTIVE FILTERS --}}
    @if(!empty($keywords))
        <div class="mb-3 d-flex gap-2 flex-wrap">
            <span class="text-muted small">Active:</span>
            @foreach($keywords as $k)
                <span class="hashtag-badge">#{{ $k }}</span>
            @endforeach
        </div>
    @endif

    {{-- ERROR --}}
    @if(!empty($error))
        <div class="alert alert-danger">
            {{ $error }}
        </div>
    @endif

    {{-- EMPTY --}}
    @if(empty($items))
        <div class="alert alert-warning">
            No Pinterest trends found. Try broader keywords like "interior design" or "home aesthetic".
        </div>
    @else

        <div class="row">

            @foreach($items as $item)

                @php
                    $title   = $item['title'] ?? 'Untitled';
                    $image   = $item['image'] ?? null;
                    $author  = $item['author'] ?? 'unknown';
                    $save    = $item['save'] ?? 0;
                    $repin   = $item['repin'] ?? 0;
                    $like    = $item['like'] ?? 0;
                    $link    = $item['link'] ?? '#';

                    $score = ($save * 2) + ($repin * 1.5) + $like;
                @endphp

                <div class="col-md-3 mb-4">

                    <div class="card border-0 shadow-sm h-100">

                        @if($image)
                            <img src="{{ $image }}" class="card-img-top pin-card-img">
                        @endif

                        <div class="card-body d-flex flex-column">

                            <h6 class="fw-bold" style="font-size:13px;">
                                {{ Str::limit($title, 60) }}
                            </h6>

                            <small class="text-muted mb-2">
                                @ {{ $author }}
                            </small>

                            {{-- SCORE --}}
                            <span class="badge bg-dark mb-2">
                                🔥 Score: {{ number_format($score) }}
                            </span>

                            {{-- STATS --}}
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>💾 {{ number_format($save) }}</span>
                                <span>🔁 {{ number_format($repin) }}</span>
                                <span>❤️ {{ number_format($like) }}</span>
                            </div>

                            <a href="{{ $link }}" target="_blank"
                               class="btn btn-outline-dark btn-sm mt-auto">
                                View Pin
                            </a>

                        </div>
                    </div>

                </div>

            @endforeach

        </div>

    @endif

</div>

{{-- JS --}}
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $('#keywordSelect').select2({
        theme: 'bootstrap-5',
        tags: true,
        tokenSeparators: [',', ' '],
        placeholder: 'e.g. homedecor, aesthetic, interior design'
    });

    document.querySelector('form').addEventListener('submit', function () {
        const tags = [...document.querySelectorAll('#keywordSelect option:checked')]
            .map(o => '#' + o.value).join(', ');

        document.getElementById('loadingKeywords').textContent = tags;
        document.getElementById('loadingOverlay').style.display = 'flex';
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>