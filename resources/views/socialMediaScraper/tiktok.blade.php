<!DOCTYPE html>
<html>
<head>
    <title>TikTok Home Decor Trends</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    <style>
        .filter-bar { background: #fff; border-radius: 12px; padding: 20px 24px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        .hashtag-badge { background: #f0f0f0; border-radius: 20px; padding: 2px 10px; font-size: 12px; color: #555; }
        .card-img-top { height: 220px; object-fit: cover; }
        .select2-container { width: 100% !important; }
    </style>
    {{-- Add inside <head> --}}
<style>
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
        width: 48px; height: 48px;
        border: 5px solid #dee2e6;
        border-top-color: #000;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

{{-- Add just before </body> --}}
<div id="loadingOverlay">
    <div class="spinner"></div>
    <div class="fw-semibold fs-5">Scraping TikTok trends...</div>
    <div class="text-muted" id="loadingHashtags"></div>
    <small class="text-muted">This usually takes 10–30 seconds</small>
</div>

<script>
    document.querySelector('form').addEventListener('submit', function () {
        const tags = [...document.querySelectorAll('#hashtagSelect option:checked')]
            .map(o => '#' + o.value).join(', ');

        document.getElementById('loadingHashtags').textContent = tags;
        document.getElementById('loadingOverlay').style.display = 'flex';
    });
</script>
</head>
<body class="bg-light pt-5">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">

        {{-- BRAND --}}
        <a class="navbar-brand fw-semibold" href="#">
            🔥 Social Trend Analyzer
        </a>

        {{-- MOBILE TOGGLER --}}
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        {{-- NAV LINKS --}}
        <div class="collapse navbar-collapse" id="topNav">

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('tiktok.trends') }}">TikTok Trends</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('pinterest.trends') }}">Pinterest Trends</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="{{ route('google.trends') }}">Google Trends</a>
                </li>

            </ul>

            {{-- RIGHT SIDE --}}
            <div class="d-flex align-items-center gap-2">

                <span class="text-light small d-none d-md-inline">
                    📊 Real-time Trend Scraper
                </span>

                <a href="#" class="btn btn-outline-light btn-sm">
                    ⚙️ Settings
                </a>

            </div>

        </div>
    </div>
</nav>
<div class="container py-5">

    <h2 class="mb-1">🔥 TikTok Trends</h2>
    <p class="text-muted mb-4">Search by hashtags and filter by country</p>

    {{-- FILTER BAR --}}
    <div class="filter-bar mb-4">
        <form method="GET" action="{{ route('tiktok.trends') }}">
            <div class="row g-3 align-items-end">

                {{-- HASHTAGS (multiple) --}}
                <div class="col-md-5">
                    <label class="form-label fw-semibold mb-1">Hashtags</label>
                    <select name="hashtags[]"
                            id="hashtagSelect"
                            class="form-select"
                            multiple>
                        @php
                            $activeHashtags = request('hashtags', ['homedecor']);
                        @endphp
                        @foreach($activeHashtags as $tag)
                            <option value="{{ $tag }}" selected>{{ $tag }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Type a hashtag and press Enter to add</small>
                </div>

                {{-- COUNTRY --}}
                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-1">Country</label>
                    <select name="country" id="countrySelect" class="form-select">
                        <option value="">🌍 All Countries</option>

                        {{-- Asia Pacific --}}
                        <optgroup label="🌏 Asia Pacific">
                            <option value="AU" {{ ($country ?? '') == 'AU' ? 'selected' : '' }}>🇦🇺 Australia</option>
                            <option value="BD" {{ ($country ?? '') == 'BD' ? 'selected' : '' }}>🇧🇩 Bangladesh</option>
                            <option value="KH" {{ ($country ?? '') == 'KH' ? 'selected' : '' }}>🇰🇭 Cambodia</option>
                            <option value="CN" {{ ($country ?? '') == 'CN' ? 'selected' : '' }}>🇨🇳 China</option>
                            <option value="HK" {{ ($country ?? '') == 'HK' ? 'selected' : '' }}>🇭🇰 Hong Kong</option>
                            <option value="IN" {{ ($country ?? '') == 'IN' ? 'selected' : '' }}>🇮🇳 India</option>
                            <option value="ID" {{ ($country ?? '') == 'ID' ? 'selected' : '' }}>🇮🇩 Indonesia</option>
                            <option value="JP" {{ ($country ?? '') == 'JP' ? 'selected' : '' }}>🇯🇵 Japan</option>
                            <option value="KZ" {{ ($country ?? '') == 'KZ' ? 'selected' : '' }}>🇰🇿 Kazakhstan</option>
                            <option value="KR" {{ ($country ?? '') == 'KR' ? 'selected' : '' }}>🇰🇷 South Korea</option>
                            <option value="LA" {{ ($country ?? '') == 'LA' ? 'selected' : '' }}>🇱🇦 Laos</option>
                            <option value="MY" {{ ($country ?? '') == 'MY' ? 'selected' : '' }}>🇲🇾 Malaysia</option>
                            <option value="MV" {{ ($country ?? '') == 'MV' ? 'selected' : '' }}>🇲🇻 Maldives</option>
                            <option value="MM" {{ ($country ?? '') == 'MM' ? 'selected' : '' }}>🇲🇲 Myanmar</option>
                            <option value="NZ" {{ ($country ?? '') == 'NZ' ? 'selected' : '' }}>🇳🇿 New Zealand</option>
                            <option value="PK" {{ ($country ?? '') == 'PK' ? 'selected' : '' }}>🇵🇰 Pakistan</option>
                            <option value="PH" {{ ($country ?? '') == 'PH' ? 'selected' : '' }}>🇵🇭 Philippines</option>
                            <option value="SG" {{ ($country ?? '') == 'SG' ? 'selected' : '' }}>🇸🇬 Singapore</option>
                            <option value="LK" {{ ($country ?? '') == 'LK' ? 'selected' : '' }}>🇱🇰 Sri Lanka</option>
                            <option value="TW" {{ ($country ?? '') == 'TW' ? 'selected' : '' }}>🇹🇼 Taiwan</option>
                            <option value="TH" {{ ($country ?? '') == 'TH' ? 'selected' : '' }}>🇹🇭 Thailand</option>
                            <option value="VN" {{ ($country ?? '') == 'VN' ? 'selected' : '' }}>🇻🇳 Vietnam</option>
                        </optgroup>

                        {{-- Middle East --}}
                        <optgroup label="🌍 Middle East">
                            <option value="BH" {{ ($country ?? '') == 'BH' ? 'selected' : '' }}>🇧🇭 Bahrain</option>
                            <option value="EG" {{ ($country ?? '') == 'EG' ? 'selected' : '' }}>🇪🇬 Egypt</option>
                            <option value="IQ" {{ ($country ?? '') == 'IQ' ? 'selected' : '' }}>🇮🇶 Iraq</option>
                            <option value="JO" {{ ($country ?? '') == 'JO' ? 'selected' : '' }}>🇯🇴 Jordan</option>
                            <option value="KW" {{ ($country ?? '') == 'KW' ? 'selected' : '' }}>🇰🇼 Kuwait</option>
                            <option value="LB" {{ ($country ?? '') == 'LB' ? 'selected' : '' }}>🇱🇧 Lebanon</option>
                            <option value="OM" {{ ($country ?? '') == 'OM' ? 'selected' : '' }}>🇴🇲 Oman</option>
                            <option value="QA" {{ ($country ?? '') == 'QA' ? 'selected' : '' }}>🇶🇦 Qatar</option>
                            <option value="SA" {{ ($country ?? '') == 'SA' ? 'selected' : '' }}>🇸🇦 Saudi Arabia</option>
                            <option value="TR" {{ ($country ?? '') == 'TR' ? 'selected' : '' }}>🇹🇷 Turkey</option>
                            <option value="AE" {{ ($country ?? '') == 'AE' ? 'selected' : '' }}>🇦🇪 UAE</option>
                            <option value="YE" {{ ($country ?? '') == 'YE' ? 'selected' : '' }}>🇾🇪 Yemen</option>
                        </optgroup>

                        {{-- Europe --}}
                        <optgroup label="🌍 Europe">
                            <option value="AT" {{ ($country ?? '') == 'AT' ? 'selected' : '' }}>🇦🇹 Austria</option>
                            <option value="BE" {{ ($country ?? '') == 'BE' ? 'selected' : '' }}>🇧🇪 Belgium</option>
                            <option value="HR" {{ ($country ?? '') == 'HR' ? 'selected' : '' }}>🇭🇷 Croatia</option>
                            <option value="CZ" {{ ($country ?? '') == 'CZ' ? 'selected' : '' }}>🇨🇿 Czech Republic</option>
                            <option value="DK" {{ ($country ?? '') == 'DK' ? 'selected' : '' }}>🇩🇰 Denmark</option>
                            <option value="FI" {{ ($country ?? '') == 'FI' ? 'selected' : '' }}>🇫🇮 Finland</option>
                            <option value="FR" {{ ($country ?? '') == 'FR' ? 'selected' : '' }}>🇫🇷 France</option>
                            <option value="DE" {{ ($country ?? '') == 'DE' ? 'selected' : '' }}>🇩🇪 Germany</option>
                            <option value="GR" {{ ($country ?? '') == 'GR' ? 'selected' : '' }}>🇬🇷 Greece</option>
                            <option value="HU" {{ ($country ?? '') == 'HU' ? 'selected' : '' }}>🇭🇺 Hungary</option>
                            <option value="IE" {{ ($country ?? '') == 'IE' ? 'selected' : '' }}>🇮🇪 Ireland</option>
                            <option value="IL" {{ ($country ?? '') == 'IL' ? 'selected' : '' }}>🇮🇱 Israel</option>
                            <option value="IT" {{ ($country ?? '') == 'IT' ? 'selected' : '' }}>🇮🇹 Italy</option>
                            <option value="NL" {{ ($country ?? '') == 'NL' ? 'selected' : '' }}>🇳🇱 Netherlands</option>
                            <option value="NO" {{ ($country ?? '') == 'NO' ? 'selected' : '' }}>🇳🇴 Norway</option>
                            <option value="PL" {{ ($country ?? '') == 'PL' ? 'selected' : '' }}>🇵🇱 Poland</option>
                            <option value="PT" {{ ($country ?? '') == 'PT' ? 'selected' : '' }}>🇵🇹 Portugal</option>
                            <option value="RO" {{ ($country ?? '') == 'RO' ? 'selected' : '' }}>🇷🇴 Romania</option>
                            <option value="RU" {{ ($country ?? '') == 'RU' ? 'selected' : '' }}>🇷🇺 Russia</option>
                            <option value="ES" {{ ($country ?? '') == 'ES' ? 'selected' : '' }}>🇪🇸 Spain</option>
                            <option value="SE" {{ ($country ?? '') == 'SE' ? 'selected' : '' }}>🇸🇪 Sweden</option>
                            <option value="CH" {{ ($country ?? '') == 'CH' ? 'selected' : '' }}>🇨🇭 Switzerland</option>
                            <option value="UA" {{ ($country ?? '') == 'UA' ? 'selected' : '' }}>🇺🇦 Ukraine</option>
                            <option value="GB" {{ ($country ?? '') == 'GB' ? 'selected' : '' }}>🇬🇧 United Kingdom</option>
                        </optgroup>

                        {{-- Americas --}}
                        <optgroup label="🌎 Americas">
                            <option value="AR" {{ ($country ?? '') == 'AR' ? 'selected' : '' }}>🇦🇷 Argentina</option>
                            <option value="BO" {{ ($country ?? '') == 'BO' ? 'selected' : '' }}>🇧🇴 Bolivia</option>
                            <option value="BR" {{ ($country ?? '') == 'BR' ? 'selected' : '' }}>🇧🇷 Brazil</option>
                            <option value="CA" {{ ($country ?? '') == 'CA' ? 'selected' : '' }}>🇨🇦 Canada</option>
                            <option value="CL" {{ ($country ?? '') == 'CL' ? 'selected' : '' }}>🇨🇱 Chile</option>
                            <option value="CO" {{ ($country ?? '') == 'CO' ? 'selected' : '' }}>🇨🇴 Colombia</option>
                            <option value="CR" {{ ($country ?? '') == 'CR' ? 'selected' : '' }}>🇨🇷 Costa Rica</option>
                            <option value="DO" {{ ($country ?? '') == 'DO' ? 'selected' : '' }}>🇩🇴 Dominican Republic</option>
                            <option value="EC" {{ ($country ?? '') == 'EC' ? 'selected' : '' }}>🇪🇨 Ecuador</option>
                            <option value="SV" {{ ($country ?? '') == 'SV' ? 'selected' : '' }}>🇸🇻 El Salvador</option>
                            <option value="GT" {{ ($country ?? '') == 'GT' ? 'selected' : '' }}>🇬🇹 Guatemala</option>
                            <option value="MX" {{ ($country ?? '') == 'MX' ? 'selected' : '' }}>🇲🇽 Mexico</option>
                            <option value="PA" {{ ($country ?? '') == 'PA' ? 'selected' : '' }}>🇵🇦 Panama</option>
                            <option value="PE" {{ ($country ?? '') == 'PE' ? 'selected' : '' }}>🇵🇪 Peru</option>
                            <option value="US" {{ ($country ?? '') == 'US' ? 'selected' : '' }}>🇺🇸 United States</option>
                            <option value="UY" {{ ($country ?? '') == 'UY' ? 'selected' : '' }}>🇺🇾 Uruguay</option>
                        </optgroup>

                        {{-- Africa --}}
                        <optgroup label="🌍 Africa">
                            <option value="DZ" {{ ($country ?? '') == 'DZ' ? 'selected' : '' }}>🇩🇿 Algeria</option>
                            <option value="CM" {{ ($country ?? '') == 'CM' ? 'selected' : '' }}>🇨🇲 Cameroon</option>
                            <option value="CI" {{ ($country ?? '') == 'CI' ? 'selected' : '' }}>🇨🇮 Côte d'Ivoire</option>
                            <option value="GH" {{ ($country ?? '') == 'GH' ? 'selected' : '' }}>🇬🇭 Ghana</option>
                            <option value="KE" {{ ($country ?? '') == 'KE' ? 'selected' : '' }}>🇰🇪 Kenya</option>
                            <option value="MA" {{ ($country ?? '') == 'MA' ? 'selected' : '' }}>🇲🇦 Morocco</option>
                            <option value="NG" {{ ($country ?? '') == 'NG' ? 'selected' : '' }}>🇳🇬 Nigeria</option>
                            <option value="SN" {{ ($country ?? '') == 'SN' ? 'selected' : '' }}>🇸🇳 Senegal</option>
                            <option value="ZA" {{ ($country ?? '') == 'ZA' ? 'selected' : '' }}>🇿🇦 South Africa</option>
                            <option value="TZ" {{ ($country ?? '') == 'TZ' ? 'selected' : '' }}>🇹🇿 Tanzania</option>
                            <option value="TN" {{ ($country ?? '') == 'TN' ? 'selected' : '' }}>🇹🇳 Tunisia</option>
                            <option value="UG" {{ ($country ?? '') == 'UG' ? 'selected' : '' }}>🇺🇬 Uganda</option>
                        </optgroup>

                    </select>
                </div>

                {{-- SUBMIT --}}
                <div class="col-md-3">
                    <button type="submit" class="btn btn-dark w-100">
                        🔍 Search
                    </button>
                </div>

            </div>
        </form>
    </div>

    {{-- ACTIVE FILTERS --}}
    @php $activeHashtags = request('hashtags', ['homedecor']); @endphp
    @if(!empty($activeHashtags) || !empty($country))
        <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
            <span class="text-muted small">Showing results for:</span>
            @foreach($activeHashtags as $tag)
                <span class="hashtag-badge">#{{ $tag }}</span>
            @endforeach
            @if(!empty($country))
                <span class="hashtag-badge">📍 {{ $country }}</span>
            @endif
        </div>
    @endif

    {{-- ERROR --}}
    @if(!empty($error))
        <div class="alert alert-danger"><strong>Error:</strong> {{ $error }}</div>
    @endif

    {{-- EMPTY --}}
    @if(empty($items))
        <div class="alert alert-warning">No results found. Try a different hashtag or country.</div>
    @else

        {{-- DEBUG: Remove once confirmed working --}}
        <details class="mb-4">
            <summary class="text-muted" style="cursor:pointer;">🛠 Debug: Raw first item</summary>
            <pre class="bg-white p-3 rounded border" style="font-size:12px;">{{ json_encode($items[0] ?? [], JSON_PRETTY_PRINT) }}</pre>
        </details>

        <div class="row">
            @foreach($items as $item)
                @php
                    $desc        = data_get($item, 'text', 'No description');
                    $likes       = data_get($item, 'diggCount', 0);
                    $views       = data_get($item, 'playCount', 0);
                    $shares      = data_get($item, 'shareCount', 0);
                    $comments    = data_get($item, 'commentCount', 0);
                    $author      = data_get($item, 'authorMeta.nickName') ?? data_get($item, 'authorMeta.name', 'Unknown');
                    $authorId    = data_get($item, 'authorMeta.name', '');
                    $authorUrl   = data_get($item, 'authorMeta.profileUrl', '#');
                    $avatar      = data_get($item, 'authorMeta.avatar', null);
                    $fans        = data_get($item, 'authorMeta.fans', 0);
                    $thumb       = data_get($item, 'videoMeta.coverUrl', null);
                    $postUrl     = data_get($item, 'webVideoUrl', '#');
                    $duration    = data_get($item, 'videoMeta.duration', 0);
                    $hashtags    = collect(data_get($item, 'hashtags', []))->pluck('name')->filter()->values();
                    $itemCountry = data_get($item, 'authorMeta.region') ?? data_get($item, 'locationCreated') ?? null;
                @endphp

                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm border-0">

                        {{-- THUMBNAIL --}}
                        <a href="{{ $postUrl }}" target="_blank" class="position-relative d-block">
                            @if($thumb)
                                <img src="{{ $thumb }}" class="card-img-top" alt="thumbnail">
                            @else
                                <div class="bg-dark d-flex align-items-center justify-content-center text-white"
                                     style="height:220px; border-radius:8px 8px 0 0;">
                                    ▶ Watch on TikTok
                                </div>
                            @endif
                            @if($duration)
                                <span class="position-absolute bottom-0 end-0 bg-dark text-white"
                                      style="font-size:11px; padding:2px 6px; margin:6px; border-radius:4px;">
                                    {{ $duration }}s
                                </span>
                            @endif
                        </a>

                        <div class="card-body d-flex flex-column">

                            {{-- AUTHOR --}}
                            <div class="d-flex align-items-center gap-2 mb-2">
                                @if($avatar)
                                    <img src="{{ $avatar }}"
                                         style="width:32px;height:32px;border-radius:50%;object-fit:cover;"
                                         alt="{{ $author }}">
                                @endif
                                <div>
                                    <a href="{{ $authorUrl }}" target="_blank"
                                       class="text-decoration-none fw-semibold text-dark"
                                       style="font-size:13px;">{{ $author }}</a>
                                    <div class="text-muted" style="font-size:11px;">
                                        {{ number_format($fans) }} followers
                                        @if($itemCountry) · 📍 {{ $itemCountry }} @endif
                                    </div>
                                </div>
                            </div>

                            {{-- DESCRIPTION --}}
                            <p class="card-text mb-2" style="font-size:12px;line-height:1.5;color:#333;">
                                {{ Str::limit($desc, 120) }}
                            </p>

                            {{-- HASHTAGS --}}
                            @if($hashtags->count())
                                <div class="mb-2 d-flex flex-wrap gap-1">
                                    @foreach($hashtags->take(4) as $tag)
                                        <span class="badge"
                                              style="background:#f0f0f0;color:#555;font-weight:400;font-size:11px;">
                                            #{{ $tag }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- STATS --}}
                            <div class="mt-auto pt-2 border-top d-flex justify-content-between text-muted"
                                 style="font-size:12px;">
                                <span title="Likes">❤️ {{ number_format($likes) }}</span>
                                <span title="Views">▶️ {{ number_format($views) }}</span>
                                <span title="Comments">💬 {{ number_format($comments) }}</span>
                                <span title="Shares">↗️ {{ number_format($shares) }}</span>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- Select2 JS --}}
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Hashtag select — allows typing new tags and pressing Enter
    $('#hashtagSelect').select2({
        theme: 'bootstrap-5',
        tags: true,                  // allows free-text input
        tokenSeparators: [',', ' '], // press comma or space to add
        placeholder: 'e.g. homedecor, interiordesign',
        allowClear: true,
    });

    // Country select — searchable dropdown
    $('#countrySelect').select2({
        theme: 'bootstrap-5',
        placeholder: '🌍 All Countries',
        allowClear: true,
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>