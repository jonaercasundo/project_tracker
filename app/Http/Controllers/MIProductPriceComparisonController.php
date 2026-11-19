<?php

namespace App\Http\Controllers;

use App\Helpers\PublicImageUrl;
use App\Models\MI_Product;
use App\Services\AiProviderConfiguration;
use App\Services\AnthropicResearchClient;
use App\Services\OpenAiResponsesClient;
use App\Services\PublicDnsResolver;
use App\Services\VerifiedListingImageResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MIProductPriceComparisonController extends Controller
{
    public function addByLink(Request $request, VerifiedListingImageResolver $listings): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:2048', PublicImageUrl::rule('product page')],
        ]);
        $extracted = $listings->comparison($validated['url']);
        if ($extracted['result'] === null) {
            return $this->listingFallback($validated['url'], $extracted);
        }
        $result = $extracted['result'];
        $validator = Validator::make($result, [
            'store_name' => ['required', 'string', 'max:255'],
            'product_name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'currency' => ['required', 'in:USD'],
            'photo_url' => ['nullable', 'string', 'max:2048', PublicImageUrl::rule()],
            'product_link' => ['required', 'url:http,https', 'max:2048'],
        ]);
        if ($validator->fails() || ! is_finite((float) $result['price'])) {
            $message = ($result['currency'] ?? null) !== 'USD'
                ? 'This comparison workflow accepts USD listings only. The listing currency could not be verified as USD; no currency conversion was performed.'
                : 'The product listing returned incomplete or unsupported details. No comparison was added.';

            return $this->listingFallback($validated['url'], ['status' => 'unsupported_details', 'partial' => $result], $message);
        }

        return response()->json(['message' => 'Review the product listing and select it before saving.', 'results' => [$result]]);
    }

    public function confirmManual(Request $request, PublicDnsResolver $dns): JsonResponse
    {
        if (is_string($request->input('currency'))) {
            $request->merge(['currency' => strtoupper(trim($request->input('currency')))]);
        }
        $validated = $request->validate([
            'product_name' => ['required', 'string', 'max:255'],
            'store_name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'currency' => ['required', 'in:USD'],
            'photo_url' => ['nullable', 'string', 'max:2048', PublicImageUrl::rule()],
            'product_link' => ['required', 'string', 'max:2048', PublicImageUrl::rule('product page')],
        ]);
        $host = parse_url($validated['product_link'], PHP_URL_HOST);
        $port = parse_url($validated['product_link'], PHP_URL_PORT);
        $addresses = $dns->addresses($host);
        if (($port !== null && ! in_array($port, [80, 443], true)) || $addresses === [] || array_filter($addresses, fn (string $address): bool => ! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE))) {
            return response()->json(['message' => 'The product page must resolve to a public HTTP or HTTPS address on a standard port.', 'results' => []], 422);
        }
        if (! empty($validated['photo_url'])) {
            $imageAddresses = $dns->addresses(parse_url($validated['photo_url'], PHP_URL_HOST));
            if ($imageAddresses === [] || array_filter($imageAddresses, fn (string $address): bool => ! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE))) {
                return response()->json(['message' => 'The product image URL must resolve to a public address.', 'errors' => ['photo_url' => ['The product image URL must resolve to a public address.']], 'results' => []], 422);
            }
        }
        $validated['price'] = (float) $validated['price'];
        $validated['photo_url'] = PublicImageUrl::normalize($validated['photo_url'] ?? null);
        $validated['image_status'] = $validated['photo_url'] ? 'available' : 'missing';
        $validated['source'] = 'manual';
        $validated['price_evidence'] = 'Listing details entered and confirmed manually; verify them against the source.';

        return response()->json(['message' => 'Comparison added for review. Select it before saving.', 'results' => [$validated]]);
    }

    /** @param array<string, mixed> $extracted */
    private function listingFallback(string $url, array $extracted, ?string $message = null): JsonResponse
    {
        $correlationId = (string) Str::uuid();
        $status = $extracted['status'];
        Log::warning('MI product listing requires manual review.', [
            'correlation_id' => $correlationId,
            'host' => parse_url($url, PHP_URL_HOST),
            'reason' => $status,
            'http_status' => $extracted['diagnostics']['http_status'] ?? null,
            'exception' => $extracted['diagnostics']['exception'] ?? null,
        ]);
        $message ??= match ($status) {
            'access_blocked' => 'This store blocked automatic access. Enter the listing details to add it manually.',
            'rate_limited' => 'This store is limiting requests. Try later or enter the listing details manually.',
            'timeout' => 'The store took too long to respond. Enter the listing details manually or try later.',
            'network_error', 'read_error' => 'The store could not be reached. Enter the listing details manually or try later.',
            'not_found' => 'The store reports that this listing is unavailable. Check the link before entering details manually.',
            'store_unavailable', 'http_error' => 'The store returned an error. Enter the listing details manually or try later.',
            'unsafe_url', 'unsafe_or_unresolved_host' => 'The product page could not be retrieved because its address or redirect is unsafe or could not resolve publicly.',
            'ambiguous_price' => 'This listing has multiple prices. Confirm the exact variant and enter its details manually.',
            'unsupported_content', 'page_too_large', 'invalid_redirect', 'redirect_limit' => 'This page cannot be read automatically. Enter the listing details manually.',
            default => 'The listing does not provide a verifiable product name, store, price, and currency. Enter the missing details manually.',
        };
        $partial = array_intersect_key($extracted['partial'] ?? [], array_flip(['product_name', 'store_name', 'price', 'currency', 'photo_url']));

        return response()->json([
            'message' => $message, 'error_code' => $status, 'correlation_id' => $correlationId, 'results' => [],
            'fallback' => array_merge($partial, ['product_link' => $url]),
        ], 422);
    }

    public function search(Request $request, AiProviderConfiguration $configuration, OpenAiResponsesClient $client, AnthropicResearchClient $anthropic, VerifiedListingImageResolver $images): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['prohibited'],
            'image_url' => ['prohibited'],
            'image_file' => ['prohibited'],
            'product_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'query' => ['nullable', 'string', 'max:300'],
            'reference_url' => ['nullable', 'url:http,https', 'max:2048'],
            'max_results' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if (! $configuration->isEnabled()) {
            return response()->json(['setup_required' => true, 'error_code' => 'provider_disabled', 'message' => 'AI product research is disabled. Ask an administrator to enable it in AI Provider Settings.', 'results' => []], 503);
        }

        $provider = $configuration->researchProvider();
        $apiKey = $configuration->researchApiKey();
        if ($apiKey === null) {
            return response()->json([
                'message' => $provider === 'anthropic' ? 'Claude web search is not configured. Add ANTHROPIC_API_KEY or save a Claude key in AI Provider Settings.' : 'OpenAI web search is not configured. Add OPENAI_API_KEY or save an API key in AI Provider Settings.',
                'setup_required' => true,
                'error_code' => 'credentials_missing',
                'results' => [],
            ], 503);
        }

        try {
            $response = ($provider === 'anthropic' ? $anthropic : $client)->searchProductOffers(
                $apiKey,
                $configuration->researchModel(),
                $validated['product_name'],
                $validated['query'] ?? null,
                $validated['reference_url'] ?? null,
                (int) $validated['max_results'],
                $validated['description'] ?? null,
            );
        } catch (Throwable $exception) {
            Log::warning('MI product price search could not reach the provider.', ['provider' => $provider, 'exception' => $exception::class]);

            return response()->json(['message' => 'The web search provider could not be reached. Try again later.', 'results' => []], 502);
        }

        if (! $response->successful()) {
            if ($provider === 'anthropic') {
                $failure = $anthropic->failure($response);
                Log::warning('MI product price search provider rejected the request.', ['provider' => $provider, 'status' => $response->status(), 'request_id' => $response->header('request-id')]);

                return response()->json([...$failure, 'results' => [], 'request_id' => $response->header('request-id')], 502);
            }
            $providerCode = $response->json('error.code');
            $providerMessage = $response->json('error.message');
            $requestId = $response->header('x-request-id');
            Log::warning('MI product price search provider rejected the request.', [
                'status' => $response->status(),
                'provider_code' => is_string($providerCode) ? $providerCode : null,
                'request_id' => $requestId,
                'model' => $configuration->model(),
            ]);

            $message = match ($response->status()) {
                401 => 'OpenAI rejected the saved API key. Verify the key used by the AI assistant.',
                403 => 'OpenAI denied access to the configured model or web search tool for this API project.',
                429 => in_array($providerCode, ['credit_balance_exhausted', 'insufficient_quota', 'billing_hard_limit_reached', 'billing_not_active'], true)
                    ? 'OpenAI API credits are exhausted or billing is inactive. Add credits to the API project or replace the key in AI Provider Settings with a funded project key.'
                    : 'OpenAI rate-limited this search. Wait briefly and try again.',
                default => 'OpenAI rejected the web search request'.(is_string($providerMessage) && trim($providerMessage) !== '' ? ': '.Str::limit(strip_tags($providerMessage), 240) : '. Check the model and web search access.'),
            };

            $setupRequired = in_array($response->status(), [400, 401, 403], true)
                || in_array($providerCode, ['credit_balance_exhausted', 'insufficient_quota', 'billing_hard_limit_reached', 'billing_not_active'], true);

            return response()->json(['setup_required' => $setupRequired, 'error_code' => $setupRequired ? 'provider_setup_required' : 'provider_unavailable', 'message' => $message, 'request_id' => $requestId, 'results' => []], 502);
        }

        try {
            $output = $provider === 'anthropic' ? $anthropic->output($response) : $response->json('output');
            if (! is_array($output) || in_array($response->json('status'), ['incomplete', 'failed'], true)) {
                throw new RuntimeException('Incomplete search response.');
            }
            $results = $this->verifiedResults($output, (int) $validated['max_results'], $images);
        } catch (RuntimeException $exception) {
            Log::warning('MI product price search returned an unusable response.', ['request_id' => $response->header('x-request-id')]);

            return response()->json(['message' => $provider === 'anthropic' ? 'Claude research did not complete or web search failed. Try fewer results and check web-search access in the Claude Console.' : 'The search provider returned an incomplete or unreadable result. Try again with fewer results or a more specific product description.', 'results' => []], 502);
        }

        return response()->json([
            'message' => $results === [] ? 'No cited product offers were found. Try a more specific product name.' : 'Review these cited product offers before saving.',
            'results' => $results,
        ]);
    }

    /** @param array<int, mixed> $output
     * @return array<int, array{store_name: string, product_name: string, photo_url: ?string, image_status: string, price: float, currency: string, product_link: string, match_reason?: string, price_evidence?: string}>
     */
    private function verifiedResults(array $output, int $maxResults, VerifiedListingImageResolver $images): array
    {
        $text = '';
        $citedUrls = [];
        $sourceImages = [];
        foreach ($output as $item) {
            if (($item['type'] ?? null) === 'web_search_call') {
                foreach ($item['action']['sources'] ?? [] as $source) {
                    if (is_string($source['url'] ?? null)) {
                        $citedUrls[] = $source['url'];
                        $sourceImages[$source['url']][] = $source;
                    }
                }
            }
            if (($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ($item['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                    $text .= $content['text'];
                }
                foreach ($content['annotations'] ?? [] as $annotation) {
                    $url = $annotation['url'] ?? $annotation['url_citation']['url'] ?? null;
                    if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
                        $citedUrls[] = $url;
                        $sourceImages[$url][] = $annotation;
                    }
                }
            }
        }

        $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text));
        $decoded = json_decode($text, true);
        if (! is_array($decoded) && preg_match('/\{.*\}/s', $text, $json)) {
            $decoded = json_decode($json[0], true);
        }
        if (! is_array($decoded) || ! is_array($decoded['results'] ?? null)) {
            throw new RuntimeException('Invalid search JSON.');
        }

        $results = [];
        foreach ($decoded['results'] ?? [] as $result) {
            if (! is_array($result)) {
                continue;
            }

            $productLink = $result['product_link'] ?? null;
            $price = $result['price'] ?? null;
            $currency = $result['currency'] ?? null;
            if (! is_string($productLink)
                || ! in_array($productLink, $citedUrls, true)
                || ! is_string($result['store_name'] ?? null)
                || trim($result['store_name']) === ''
                || mb_strlen($result['store_name']) > 255
                || ! is_string($result['product_name'] ?? null)
                || trim($result['product_name']) === ''
                || mb_strlen($result['product_name']) > 255
                || ! is_numeric($price)
                || (float) $price < 0
                || ! is_finite((float) $price)
                || (float) $price > 999999999999.99
                || ! is_string($currency)
                || strtoupper($currency) !== 'USD'
                || ! filter_var($productLink, FILTER_VALIDATE_URL)
                || strlen($productLink) > 2048
                || ! in_array(strtolower((string) parse_url($productLink, PHP_URL_SCHEME)), ['http', 'https'], true)
            ) {
                continue;
            }

            if (in_array($productLink, array_column($results, 'product_link'), true)) {
                continue;
            }

            $image = PublicImageUrl::fromOffer($result, $productLink);
            foreach ($sourceImages[$productLink] ?? [] as $source) {
                if ($image['url']) {
                    break;
                }
                $sourceImage = PublicImageUrl::fromOffer($source, $productLink);
                if ($sourceImage['url'] || $image['status'] === 'missing') {
                    $image = $sourceImage;
                }
            }
            if (! $image['url']) {
                $listingImage = $images->resolve($productLink);
                if ($listingImage['url'] || $image['status'] !== 'unresolved') {
                    $image = $listingImage;
                }
            }

            $listing = [
                'store_name' => trim($result['store_name']),
                'product_name' => trim($result['product_name']),
                'photo_url' => $image['url'],
                'image_status' => $image['status'],
                'price' => (float) $price,
                'currency' => strtoupper($currency),
                'product_link' => $productLink,
            ];
            foreach (['match_reason', 'price_evidence'] as $detail) {
                if (is_string($result[$detail] ?? null) && trim($result[$detail]) !== '') {
                    $listing[$detail] = Str::limit(trim(strip_tags($result[$detail])), 600);
                }
            }
            $results[] = $listing;
            if (count($results) >= $maxResults) {
                break;
            }
        }

        return $results;
    }

    public function store(Request $request, MI_Product $product): JsonResponse
    {
        Gate::authorize('update', $product);
        if (is_array($request->input('results'))) {
            $request->merge(['results' => array_map(function (mixed $result): mixed {
                if (is_array($result) && is_string($result['currency'] ?? null)) {
                    $result['currency'] = strtoupper($result['currency']);
                }

                return $result;
            }, $request->input('results'))]);
        }
        $validated = $request->validate([
            'results' => ['required', 'array', 'min:1', 'max:20'],
            'results.*' => ['required', 'array:store_name,product_name,photo_url,price,currency,product_link'],
            'results.*.store_name' => ['required', 'string', 'max:255'],
            'results.*.product_name' => ['required', 'string', 'max:255'],
            'results.*.photo_url' => ['nullable', 'string', 'max:2048', PublicImageUrl::rule()],
            'results.*.price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'results.*.currency' => ['required', 'in:USD'],
            'results.*.product_link' => ['required', 'url:http,https', 'max:2048'],
        ]);

        $saved = DB::transaction(function () use ($product, $validated): array {
            $comparisons = [];
            foreach ($validated['results'] as $result) {
                $comparison = $product->priceComparisons()->firstOrCreate(
                    ['product_link' => $result['product_link']],
                    [...$result, 'currency' => strtoupper($result['currency'])],
                );
                if (! $comparison->photo_url && ! $comparison->photo_path && ! empty($result['photo_url'])) {
                    $comparison->update(['photo_url' => $result['photo_url']]);
                }
                $comparisons[] = $comparison;
            }

            return $comparisons;
        });

        return response()->json(['message' => 'Selected comparisons saved.', 'comparisons' => $saved], 201);
    }
}
