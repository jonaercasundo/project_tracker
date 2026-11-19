<?php

namespace App\Services;

use App\Helpers\PublicImageUrl;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class VerifiedListingImageResolver
{
    private ?float $deadline = null;

    /** @var array{reason?: string, http_status?: ?int, exception?: class-string} */
    private array $failure = [];

    public function __construct(private PublicDnsResolver $dns) {}

    /** @return array{url: ?string, status: string} */
    public function resolve(string $listingUrl): array
    {
        $page = $this->fetch($listingUrl);

        return $page ? $this->extract($page['html'], $page['url']) : ['url' => null, 'status' => 'lookup_failed'];
    }

    /** @return array{result: ?array<string, mixed>, status: string, partial?: array<string, mixed>, diagnostics?: array<string, mixed>} */
    public function comparison(string $listingUrl): array
    {
        $page = $this->fetch($listingUrl);
        if (! $page) {
            return ['result' => null, 'status' => $this->failure['reason'] ?? 'lookup_failed', 'diagnostics' => $this->failure];
        }
        if (trim($page['html']) === '') {
            return ['result' => null, 'status' => 'incomplete', 'partial' => $partial ?? []];
        }
        $partial = $this->partialDetails($page['html'], $page['url']);
        $document = new DOMDocument;
        @$document->loadHTML($page['html'], LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $products = [];
        $organizations = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
            $data = json_decode($script->textContent, true);
            $this->collectStructuredNodes($data, $products, ['Product', 'ProductGroup']);
            $this->collectStructuredNodes($data, $organizations, ['Organization', 'Store', 'OnlineStore', 'WebSite']);
        }
        $meta = [];
        foreach ($xpath->query('//meta[@property or @name]') as $node) {
            $key = strtolower($node->getAttribute('property') ?: $node->getAttribute('name'));
            $meta[$key] ??= trim($node->getAttribute('content'));
        }
        $product = null;
        foreach ($products as $candidate) {
            $identity = $candidate['url'] ?? $candidate['@id'] ?? null;
            if (is_string($identity) && $this->sameOffer($identity, $page['url'])) {
                $product = $candidate;
                break;
            }
        }
        foreach ($products as $candidate) {
            if ($product !== null) {
                break;
            }
            $identity = $candidate['url'] ?? $candidate['@id'] ?? null;
            if ((is_string($identity) && $this->samePage($identity, $page['url'])) || ($identity === null && count($products) === 1)) {
                $product = $candidate;
                break;
            }
        }
        if ($product === null && ! str_starts_with($meta['og:type'] ?? '', 'product')) {
            return ['result' => null, 'status' => 'incomplete', 'partial' => $partial ?? []];
        }
        $offers = $product['offers'] ?? [];
        $offers = is_array($offers) && array_is_list($offers) ? $offers : [$offers];
        $exactOffers = array_filter($offers, fn (mixed $offer): bool => is_array($offer) && is_string($offer['url'] ?? null) && $this->sameOffer($offer['url'], $page['url']));
        if ($exactOffers !== []) {
            $offers = $exactOffers;
        }
        $prices = [];
        foreach ($offers as $offer) {
            if (! is_array($offer)) {
                continue;
            }
            $price = $offer['price'] ?? $offer['priceSpecification']['price'] ?? null;
            $currency = $offer['priceCurrency'] ?? $offer['priceSpecification']['priceCurrency'] ?? null;
            if ($price === null && isset($offer['lowPrice'], $offer['highPrice']) && is_numeric($offer['lowPrice']) && is_numeric($offer['highPrice']) && (float) $offer['lowPrice'] === (float) $offer['highPrice']) {
                $price = $offer['lowPrice'];
            }
            if (is_numeric($price) && is_string($currency) && trim($currency) !== '') {
                $prices[(string) (float) $price.'|'.strtoupper(trim($currency))] = ['price' => (float) $price, 'currency' => strtoupper(trim($currency)), 'seller' => $offer['seller']['name'] ?? null];
            }
        }
        if (count($prices) > 1) {
            $declaredPrice = $meta['product:price:amount'] ?? $meta['og:price:amount'] ?? null;
            $declaredCurrency = $meta['product:price:currency'] ?? $meta['og:price:currency'] ?? null;
            $baseUrl = (string) (new Uri($page['url']))->withQuery('')->withFragment('');
            $declaredKey = is_numeric($declaredPrice) && is_string($declaredCurrency) ? (string) (float) $declaredPrice.'|'.strtoupper($declaredCurrency) : null;
            if ($exactOffers === [] && $this->sameOffer($baseUrl, $page['url']) && $declaredKey !== null && isset($prices[$declaredKey])) {
                $prices = [$declaredKey => $prices[$declaredKey]];
            } else {
                return ['result' => null, 'status' => 'ambiguous_price', 'partial' => array_diff_key($partial, array_flip(['price', 'currency']))];
            }
        }
        $price = $prices === [] ? null : reset($prices);
        if ($price === null && $offers !== [[]] && ! empty($product['offers'])) {
            return ['result' => null, 'status' => 'incomplete', 'partial' => $partial ?? []];
        }
        if ($price === null && is_numeric($meta['product:price:amount'] ?? $meta['og:price:amount'] ?? null)) {
            $price = ['price' => (float) ($meta['product:price:amount'] ?? $meta['og:price:amount']), 'currency' => strtoupper($meta['product:price:currency'] ?? $meta['og:price:currency'] ?? ''), 'seller' => null];
        }
        $store = $price['seller'] ?? $product['seller']['name'] ?? $meta['og:site_name'] ?? null;
        if (! is_string($store) || trim($store) === '') {
            foreach ($organizations as $organization) {
                if (is_string($organization['url'] ?? null) && parse_url($organization['url'], PHP_URL_HOST) === parse_url($page['url'], PHP_URL_HOST) && is_string($organization['name'] ?? null)) {
                    $store = $organization['name'];
                    break;
                }
            }
        }
        $name = $product['name'] ?? $meta['og:title'] ?? null;
        if (! is_string($name) || trim($name) === '' || ! is_string($store) || trim($store) === '' || $price === null) {
            return ['result' => null, 'status' => 'incomplete', 'partial' => $partial ?? []];
        }
        $image = $this->extract($page['html'], $page['url']);
        $canonical = $page['url'];
        foreach ($xpath->query('//link[@rel="canonical"]') as $link) {
            $reference = PublicImageUrl::fromReference($link->getAttribute('href'), $page['url']);
            if ($reference && $this->samePage($reference, $page['url'])) {
                $canonical = $reference;
                break;
            }
        }

        return ['status' => 'available', 'result' => [
            'store_name' => trim(strip_tags($store)),
            'product_name' => trim(strip_tags($name)),
            'price' => $price['price'],
            'currency' => $price['currency'],
            'photo_url' => $image['url'],
            'image_status' => $image['status'],
            'product_link' => $listingUrl,
            'canonical_url' => $canonical,
            'source' => 'manual',
            'price_evidence' => 'Price and currency were read from the product listing.',
        ]];
    }

    private function sameOffer(string $offerUrl, string $pageUrl): bool
    {
        if (! $this->samePage($offerUrl, $pageUrl)) {
            return false;
        }
        $queries = [];
        foreach ([$offerUrl, $pageUrl] as $url) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
            foreach (array_keys($query) as $key) {
                if (str_starts_with(strtolower($key), 'utm_') || in_array(strtolower($key), ['gclid', 'fbclid', 'msclkid', 'dclid', 'mc_cid', 'mc_eid'], true)) {
                    unset($query[$key]);
                }
            }
            ksort($query);
            $queries[] = $query;
        }

        return $queries[0] === $queries[1];
    }

    /** @return array{html: string, url: string}|null */
    private function fetch(string $listingUrl): ?array
    {
        $url = $listingUrl;
        $this->failure = ['reason' => 'redirect_limit'];
        $this->deadline ??= microtime(true) + 15;
        try {
            for ($redirect = 0; $redirect < 4; $redirect++) {
                $remaining = $this->deadline - microtime(true);
                if ($remaining <= 0) {
                    return $this->failed('timeout');
                }
                if (! PublicImageUrl::normalize($url)) {
                    return $this->failed('unsafe_url');
                }
                $host = parse_url($url, PHP_URL_HOST);
                $port = parse_url($url, PHP_URL_PORT) ?? (str_starts_with(strtolower($url), 'https:') ? 443 : 80);
                if (! in_array($port, [80, 443], true)) {
                    return $this->failed('unsafe_url');
                }
                $addresses = $this->dns->addresses($host);
                if ($addresses === [] || array_filter($addresses, fn (string $address): bool => ! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE))) {
                    return $this->failed('unsafe_or_unresolved_host');
                }
                $pinned = array_map(fn (string $address): string => str_contains($address, ':') ? '['.$address.']' : $address, $addresses);
                $response = Http::accept('text/html,application/xhtml+xml')->withUserAgent('ProjectTracker-ProductResearch/1.0')
                    ->connectTimeout(min(3, $remaining))->timeout(min(8, $remaining))->withOptions([
                        'allow_redirects' => false,
                        'stream' => true,
                        'read_timeout' => min(8, $remaining),
                        'proxy' => '',
                        'curl' => [CURLOPT_RESOLVE => [$host.':'.$port.':'.implode(',', $pinned)]],
                    ])->get($url);
                $stream = $response->toPsrResponse()->getBody();
                try {
                    if ($response->redirect()) {
                        $location = $response->header('Location');
                        if (! $location) {
                            return $this->failed('invalid_redirect', $response->status());
                        }
                        $url = (string) UriResolver::resolve(new Uri($url), new Uri($location));

                        continue;
                    }
                    if (! $response->successful()) {
                        $reason = match ($response->status()) {
                            401, 403 => 'access_blocked',
                            429 => 'rate_limited',
                            404, 410 => 'not_found',
                            default => $response->serverError() ? 'store_unavailable' : 'http_error',
                        };

                        return $this->failed($reason, $response->status());
                    }
                    if (! preg_match('~^(?:text/html|application/xhtml\+xml)~i', $response->header('Content-Type') ?? '')) {
                        return $this->failed('unsupported_content', $response->status());
                    }
                    $html = '';
                    while (! $stream->eof() && strlen($html) <= 2097152) {
                        $chunk = $stream->read(min(65536, 2097153 - strlen($html)));
                        if ($chunk === '') {
                            break;
                        }
                        $html .= $chunk;
                    }
                    if (strlen($html) > 2097152) {
                        return $this->failed('page_too_large');
                    }

                    return ['html' => $html, 'url' => $url];
                } finally {
                    $stream->close();
                }
            }
        } catch (Throwable $exception) {
            $previous = $exception->getPrevious();
            $context = $previous && method_exists($previous, 'getHandlerContext') ? $previous->getHandlerContext() : [];
            $reason = ($context['errno'] ?? null) === 28 ? 'timeout' : ($exception instanceof ConnectionException ? 'network_error' : 'read_error');
            $this->failed($reason);
            $this->failure['exception'] = $exception::class;

            return null;
        }

        return null;
    }

    private function failed(string $reason, ?int $httpStatus = null): null
    {
        $this->failure = ['reason' => $reason, 'http_status' => $httpStatus];

        return null;
    }

    /** @return array<string, mixed> */
    private function partialDetails(string $html, string $pageUrl): array
    {
        if (trim($html) === '') {
            return [];
        }
        $document = new DOMDocument;
        @$document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $meta = [];
        foreach ($xpath->query('//meta[@property or @name]') as $node) {
            $meta[strtolower($node->getAttribute('property') ?: $node->getAttribute('name'))] ??= trim($node->getAttribute('content'));
        }
        $products = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
            $this->collectStructuredNodes(json_decode($script->textContent, true), $products, ['Product', 'ProductGroup']);
        }
        $product = [];
        foreach ($products as $candidate) {
            $identity = $candidate['url'] ?? $candidate['@id'] ?? null;
            if ((is_string($identity) && $this->sameOffer($identity, $pageUrl)) || ($identity === null && count($products) === 1)) {
                $product = $candidate;
                break;
            }
        }
        $offer = $product['offers'] ?? [];
        if (is_array($offer) && array_is_list($offer)) {
            $exact = array_values(array_filter($offer, fn (mixed $candidate): bool => is_array($candidate) && is_string($candidate['url'] ?? null) && $this->sameOffer($candidate['url'], $pageUrl)));
            if (count($exact) === 1) {
                $offer = $exact[0];
            } else {
                $offer = count($offer) === 1 && is_array($offer[0]) ? $offer[0] : [];
            }
        }
        $offer = is_array($offer) ? $offer : [];
        $partial = [
            'product_name' => $product['name'] ?? $meta['og:title'] ?? null,
            'store_name' => $offer['seller']['name'] ?? $product['seller']['name'] ?? $meta['og:site_name'] ?? null,
            'price' => $offer['price'] ?? $offer['priceSpecification']['price'] ?? $meta['product:price:amount'] ?? $meta['og:price:amount'] ?? null,
            'currency' => $offer['priceCurrency'] ?? $offer['priceSpecification']['priceCurrency'] ?? $meta['product:price:currency'] ?? $meta['og:price:currency'] ?? null,
            'photo_url' => PublicImageUrl::fromOffer($product, $pageUrl)['url'] ?? PublicImageUrl::fromReference($meta['og:image'] ?? null, $pageUrl),
        ];

        return array_filter($partial, fn (mixed $value): bool => is_string($value) || is_int($value) || is_float($value));
    }

    /** @return array{url: ?string, status: string} */
    private function extract(string $html, string $pageUrl): array
    {
        if (trim($html) === '') {
            return ['url' => null, 'status' => 'lookup_failed'];
        }
        $document = new DOMDocument;
        @$document->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $products = [];
        $matchedProduct = false;
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
            $this->collectStructuredNodes(json_decode($script->textContent, true), $products, ['Product', 'ProductGroup']);
        }
        foreach ($products as $product) {
            $identity = $product['url'] ?? $product['@id'] ?? null;
            $matches = is_string($identity) && $this->samePage($identity, $pageUrl);
            if (! $matches && ! ($identity === null && count($products) === 1)) {
                continue;
            }
            $matchedProduct = true;
            $image = PublicImageUrl::fromOffer($product, $pageUrl);
            if ($image['url']) {
                return $image;
            }
        }
        $productMetadata = $xpath->query('//meta[@property="og:type" and (starts-with(@content,"product") or @content="og:product")]')->length > 0;
        if (! $matchedProduct && ! $productMetadata) {
            return ['url' => null, 'status' => 'lookup_failed'];
        }
        foreach ($xpath->query('//meta[@property="og:image" or @property="og:image:url" or @property="og:image:secure_url" or @name="twitter:image"]') as $meta) {
            if ($image = PublicImageUrl::fromReference($meta->getAttribute('content'), $pageUrl)) {
                return ['url' => $image, 'status' => 'available'];
            }
        }

        return ['url' => null, 'status' => 'missing'];
    }

    /** @param array<int, array<string, mixed>> $products
     * @param  array<int, string>  $types
     */
    private function collectStructuredNodes(mixed $data, array &$products, array $types, int $depth = 0): void
    {
        if (! is_array($data) || $depth > 8) {
            return;
        }
        $nodeTypes = array_filter((array) ($data['@type'] ?? []), 'is_string');
        if (array_intersect($types, $nodeTypes) !== []) {
            $products[] = $data;
        }
        foreach ($data as $value) {
            if (is_array($value)) {
                $this->collectStructuredNodes($value, $products, $types, $depth + 1);
            }
        }
    }

    private function samePage(string $identity, string $pageUrl): bool
    {
        try {
            $identityUri = UriResolver::resolve(new Uri($pageUrl), new Uri($identity));
            $pageUri = new Uri($pageUrl);

            return $identityUri->getHost() === $pageUri->getHost() && rtrim($identityUri->getPath(), '/') === rtrim($pageUri->getPath(), '/');
        } catch (Throwable) {
            return false;
        }
    }
}
