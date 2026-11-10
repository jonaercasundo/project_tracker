<section x-cloak x-show="view === 'photo'" class="border-b border-slate-200 bg-slate-50/70 p-4 sm:p-5" aria-label="Product photo grid">
    @if($products->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($products as $product)
                @php
                    $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
                    $photoUrl = null;

                    if ($primaryImage?->image_type === 'upload' && $primaryImage->image_path) {
                        $extension = strtolower(pathinfo($primaryImage->image_path, PATHINFO_EXTENSION));
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                            $photoUrl = asset('storage/'.$primaryImage->image_path);
                        }
                    } elseif ($primaryImage?->image_url) {
                        $photoUrl = trim($primaryImage->image_url);

                        if (preg_match('/drive\.google\.com\/uc\?.*id=([^&]+)/', $photoUrl, $matches)) {
                            $photoUrl = "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
                        } elseif (preg_match('#drive\.google\.com/file/d/([^/]+)#', $photoUrl, $matches)) {
                            $photoUrl = "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
                        } elseif (preg_match('/drive\.google\.com\/open\?id=([^&]+)/', $photoUrl, $matches)) {
                            $photoUrl = "https://drive.google.com/thumbnail?id={$matches[1]}&sz=w1600";
                        }

                        $photoScheme = strtolower((string) parse_url($photoUrl, PHP_URL_SCHEME));
                        if (! in_array($photoScheme, ['http', 'https'], true)) {
                            $photoUrl = null;
                        }
                    }

                    $itemName = $product->item_name ?: 'Unnamed Product';
                    $sku = $product->sku ?: 'No SKU';
                    $productId = $product->product_id;
                    $publicUrl = route('public.product.show', ['product' => $productId]);
                @endphp

                <article class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg">
                    <a href="{{ route('mi_app.show', ['product' => $productId]) }}" class="relative block aspect-square overflow-hidden bg-gradient-to-br from-slate-100 via-stone-50 to-slate-200" aria-label="View {{ $itemName }}">
                        <div data-catalog-photo-placeholder class="absolute inset-0 grid place-items-center text-slate-400" @if($photoUrl) hidden @endif aria-hidden="true">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-12 w-12" viewBox="0 0 48 48" fill="none"><rect x="5" y="8" width="38" height="32" rx="5" stroke="currentColor" stroke-width="1.6"/><circle cx="17" cy="19" r="3.5" stroke="currentColor" stroke-width="1.6"/><path d="m8 35 11-10 7 6 6-5 8 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="text-xs font-semibold">No photo available</span>
                            </div>
                        </div>
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="{{ $itemName }}" loading="lazy" data-catalog-photo class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                            <span class="absolute left-3 top-3 rounded-full border border-white/70 bg-white/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-700 shadow-sm">Primary photo</span>
                        @endif
                    </a>

                    <div class="flex flex-1 flex-col p-4">
                        <p class="w-fit max-w-full truncate rounded-lg bg-blue-50 px-2.5 py-1.5 font-mono text-xs font-bold text-blue-700" title="{{ $sku }}">{{ $sku }}</p>
                        <h2 class="mt-3 min-h-[3.25rem] text-sm font-bold uppercase leading-5 text-slate-800">{{ $itemName }}</h2>

                        <div class="mt-auto flex items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs">
                            <span class="inline-flex min-w-0 items-center gap-1.5 text-slate-500">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4.5" width="14" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M6.5 2.5v4m7-4v4M3 8h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                <span class="truncate">Encoded {{ $product->created_at?->format('d M Y') ?? 'Date unavailable' }}</span>
                            </span>
                            <a href="{{ route('mi_app.show', ['product' => $productId]) }}" class="shrink-0 font-bold text-blue-600 hover:text-blue-800 focus-visible:rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">View <span aria-hidden="true">→</span></a>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3" aria-label="Actions for {{ $itemName }}">
                            <a href="{{ route('mi_app.edit', ['product' => $productId]) }}" class="inline-flex min-h-8 items-center rounded-lg border border-slate-200 px-2.5 text-xs font-semibold text-slate-600 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-600">Edit</a>
                            <button type="button" data-qr-url="{{ $publicUrl }}" data-qr-name="{{ $itemName }}" data-qr-sub="{{ $product->sku ?: $productId }}" onclick="txOpenQrModal(this.dataset.qrUrl, this.dataset.qrName, this.dataset.qrSub)" class="inline-flex min-h-8 items-center rounded-lg border border-slate-200 px-2.5 text-xs font-semibold text-slate-600 hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-violet-600">QR</button>
                            <form action="{{ route('mi_app.destroy', ['product' => $productId]) }}" method="POST" onsubmit="return txConfirmArchive(this)">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex min-h-8 items-center rounded-lg border border-slate-200 px-2.5 text-xs font-semibold text-slate-600 hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-rose-600">Archive</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
            <svg class="mx-auto h-10 w-10 text-slate-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 9 5v8l-9 5-9-5V8l9-5Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="m3.5 8.5 8.5 5 8.5-5M12 13.5V21" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
            <h2 class="mt-3 text-sm font-semibold text-slate-800">No products found</h2>
            <p class="mt-1 text-xs text-slate-500">Products matching your current filters will appear here.</p>
        </div>
    @endif
</section>
