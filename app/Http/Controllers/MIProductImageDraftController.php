<?php

namespace App\Http\Controllers;

use App\Services\AiProviderConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MIProductImageDraftController extends Controller
{
    public function store(Request $request, AiProviderConfiguration $configuration): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        if (! $configuration->isEnabled()) {
            return response()->json(['message' => 'AI features are disabled. Ask an administrator to enable them in AI Provider Settings.'], 503);
        }

        $apiKey = $configuration->researchApiKey('openai');
        if ($apiKey === null) {
            return response()->json(['message' => 'OpenAI is not configured. Add an OpenAI API key in AI Provider Settings to use photo autofill.'], 503);
        }

        $image = $validated['image'];
        $dataUrl = 'data:'.$image->getMimeType().';base64,'.base64_encode(file_get_contents($image->getRealPath()));

        try {
            $response = Http::withToken($apiKey)
                ->connectTimeout(10)
                ->timeout((int) config('services.openai.assistant_timeout', 90))
                ->acceptJson()
                ->post('https://api.openai.com/v1/responses', [
                    'model' => $configuration->model(),
                    'store' => false,
                    'max_output_tokens' => 1200,
                    'text' => ['format' => ['type' => 'json_object']],
                    'input' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'input_text', 'text' => 'Inspect this product photo and draft a product record as JSON. Return only these keys: item_name, description, type_of_sample, designed_by, materials, colors. Use strings for all values; materials and colors may be comma-separated strings. Leave unknown values empty. Do not guess price, country of production, SKU, dimensions, weight, or taxonomy. Describe only visible evidence and identify uncertainty in the description.'],
                            ['type' => 'input_image', 'image_url' => $dataUrl, 'detail' => 'high'],
                        ],
                    ]],
                ]);
        } catch (Throwable $exception) {
            Log::warning('MI product photo draft request failed.', ['exception' => $exception::class]);

            return response()->json(['message' => 'The AI service could not be reached. Try again later.'], 502);
        }

        if (! $response->successful()) {
            Log::warning('MI product photo draft was rejected by the AI provider.', ['status' => $response->status(), 'request_id' => $response->header('x-request-id')]);

            return response()->json(['message' => 'The AI service could not process this photo. Check AI Provider Settings or try another image.'], 502);
        }

        $text = collect($response->json('output', []))
            ->where('type', 'message')
            ->flatMap(fn (array $message): array => $message['content'] ?? [])
            ->first(fn (array $content): bool => ($content['type'] ?? null) === 'output_text')['text'] ?? null;

        $draft = is_string($text) ? json_decode($text, true) : null;
        if (! is_array($draft)) {
            return response()->json(['message' => 'The AI returned an incomplete draft. Try a clearer photo.'], 502);
        }

        $fields = ['item_name', 'description', 'type_of_sample', 'designed_by', 'materials', 'colors'];
        $draft = array_intersect_key($draft, array_flip($fields));

        return response()->json(['draft' => $draft, 'message' => 'Review the suggested details and edit anything that needs correction.']);
    }
}
