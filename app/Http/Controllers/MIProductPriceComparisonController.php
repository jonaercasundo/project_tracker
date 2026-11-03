<?php

namespace App\Http\Controllers;

use App\Models\MI_Product;
use App\Services\AiProviderConfiguration;
use App\Services\OpenAiResponsesClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MIProductPriceComparisonController extends Controller
{
    public function search(Request $request, AiProviderConfiguration $configuration, OpenAiResponsesClient $client): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['prohibited'],
            'image_url' => ['prohibited'],
            'image_file' => ['prohibited'],
            'product_name' => ['required', 'string', 'max:255'],
            'query' => ['nullable', 'string', 'max:300'],
            'max_results' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $apiKey = $configuration->apiKey();
        if ($apiKey === null) {
            return response()->json([
                'message' => 'OpenAI web search is not configured. Add OPENAI_API_KEY or save an API key in AI Provider Settings.',
                'results' => [],
            ], 503);
        }

        try {
            $response = $client->searchProductOffers(
                $apiKey,
                $configuration->model(),
                $validated['product_name'],
                $validated['query'] ?? null,
                (int) $validated['max_results'],
            );
        } catch (Throwable $exception) {
            Log::warning('MI product price search could not reach OpenAI.', ['exception' => $exception::class]);

            return response()->json(['message' => 'The web search provider could not be reached. Try again later.', 'results' => []], 502);
        }

        if (! $response->successful()) {
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
                429 => 'OpenAI rate limits or API quota prevented this search. Check the project usage and billing limits.',
                default => 'OpenAI rejected the web search request'.(is_string($providerMessage) && trim($providerMessage) !== '' ? ': '.Str::limit(strip_tags($providerMessage), 240) : '. Check the model and web search access.'),
            };

            return response()->json(['message' => $message, 'request_id' => $requestId, 'results' => []], 502);
        }

        $results = $this->verifiedResults($response->json('output', []), (int) $validated['max_results']);

        return response()->json([
            'message' => $results === [] ? 'No cited product offers were found. Try a more specific product name.' : 'Review these cited product offers before saving.',
            'results' => $results,
        ]);
    }

    /** @param array<int, mixed> $output
     * @return array<int, array{store_name: string, product_name: string, photo_url: ?string, price: float, currency: string, product_link: string}>
     */
    private function verifiedResults(array $output, int $maxResults): array
    {
        $text = '';
        $citedUrls = [];
        foreach ($output as $item) {
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
                    }
                }
            }
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            return [];
        }

        $results = [];
        foreach ($decoded['results'] ?? [] as $result) {
            if (! is_array($result)) {
                continue;
            }

            $productLink = $result['product_link'] ?? null;
            $photoUrl = $result['photo_url'] ?? null;
            $price = $result['price'] ?? null;
            $currency = $result['currency'] ?? null;
            if (! is_string($productLink)
                || ! in_array($productLink, $citedUrls, true)
                || ! is_string($result['store_name'] ?? null)
                || trim($result['store_name']) === ''
                || ! is_string($result['product_name'] ?? null)
                || trim($result['product_name']) === ''
                || ! is_numeric($price)
                || (float) $price < 0
                || ! is_string($currency)
                || ! preg_match('/\A[A-Z]{3}\z/i', $currency)
                || ! filter_var($productLink, FILTER_VALIDATE_URL)
                || ! in_array(strtolower((string) parse_url($productLink, PHP_URL_SCHEME)), ['http', 'https'], true)
            ) {
                continue;
            }

            if ($photoUrl !== null && (! is_string($photoUrl)
                || ! filter_var($photoUrl, FILTER_VALIDATE_URL)
                || ! in_array(strtolower((string) parse_url($photoUrl, PHP_URL_SCHEME)), ['http', 'https'], true))) {
                $photoUrl = null;
            }

            $results[] = [
                'store_name' => trim($result['store_name']),
                'product_name' => trim($result['product_name']),
                'photo_url' => $photoUrl,
                'price' => (float) $price,
                'currency' => strtoupper($currency),
                'product_link' => $productLink,
            ];
            if (count($results) >= $maxResults) {
                break;
            }
        }

        return $results;
    }

    public function store(Request $request, MI_Product $product): JsonResponse
    {
        Gate::authorize('update', $product);
        $validated = $request->validate([
            'results' => ['required', 'array', 'min:1', 'max:20'],
            'results.*' => ['required', 'array:store_name,product_name,photo_url,price,currency,product_link'],
            'results.*.store_name' => ['required', 'string', 'max:255'],
            'results.*.product_name' => ['required', 'string', 'max:255'],
            'results.*.photo_url' => ['nullable', 'url:http,https', 'max:2048'],
            'results.*.price' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'results.*.currency' => ['required', 'alpha', 'size:3'],
            'results.*.product_link' => ['required', 'url:http,https', 'max:2048'],
        ]);

        $saved = DB::transaction(function () use ($product, $validated): array {
            $comparisons = [];
            foreach ($validated['results'] as $result) {
                $comparisons[] = $product->priceComparisons()->firstOrCreate(
                    ['product_link' => $result['product_link']],
                    [...$result, 'currency' => strtoupper($result['currency'])],
                );
            }

            return $comparisons;
        });

        return response()->json(['message' => 'Selected comparisons saved.', 'comparisons' => $saved], 201);
    }
}
