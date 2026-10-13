<?php

namespace App\Http\Controllers;

use App\Services\LogisticsWebhookEventService;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use JsonException;

class LogisticsWebhookController extends Controller
{
    public function __invoke(Request $request, LogisticsWebhookEventService $events): JsonResponse
    {
        $rawPayload = $request->getContent();

        try {
            $decodedObject = json_decode($rawPayload, false, 512, JSON_THROW_ON_ERROR);
            $payload = json_decode($rawPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json([
                'success' => false,
                'code' => 'malformed_json',
                'message' => 'The request body must contain a valid JSON object.',
            ], 400);
        }

        if (! is_object($decodedObject) || ! is_array($payload)) {
            return response()->json([
                'success' => false,
                'code' => 'malformed_json',
                'message' => 'The request body must contain a valid JSON object.',
            ], 400);
        }

        $validator = Validator::make($payload, [
            'event_id' => ['required', 'string', 'max:191'],
            'event_type' => ['required', 'string', Rule::in(config('logistics-webhooks.allowed_event_types', []))],
            'dr_reference' => ['required', 'string', 'max:100'],
            'occurred_at' => ['required', 'string', 'max:64'],
            'sequence' => ['required', 'integer', 'min:1', 'max:9223372036854775807'],
        ]);

        $validator->after(function ($validator) use ($payload): void {
            if (! is_int($payload['sequence'] ?? null)) {
                $validator->errors()->add('sequence', 'The sequence field must be a JSON integer.');
            }

            $occurredAt = $payload['occurred_at'] ?? null;

            if (! is_string($occurredAt)
                || preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z/', $occurredAt) !== 1
            ) {
                $validator->errors()->add('occurred_at', 'The occurred_at field must be an ISO 8601 timestamp with a timezone.');

                return;
            }

            try {
                new DateTimeImmutable($occurredAt);
            } catch (\Throwable) {
                $validator->errors()->add('occurred_at', 'The occurred_at field must be a valid timestamp.');
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'code' => 'validation_failed',
                'message' => 'The webhook event is invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $events->accept(
            $payload,
            $rawPayload,
            (string) $request->attributes->get('logistics_integration_id')
        );

        if ($result['result'] === 'conflict') {
            return response()->json([
                'success' => false,
                'code' => 'event_id_conflict',
                'message' => 'This event_id was already received with a different request body.',
                'event_id' => $result['event_id'],
            ], 409);
        }

        if ($result['result'] === 'queue_unavailable' || $result['result'] === 'failed') {
            return response()->json([
                'success' => false,
                'code' => 'event_stored_retry_later',
                'message' => 'The event is stored but could not be queued. Retry this event_id later.',
                'event_id' => $result['event_id'],
            ], 503);
        }

        if ($result['result'] === 'processed_duplicate') {
            return response()->json([
                'success' => true,
                'code' => 'duplicate_processed',
                'message' => 'This event was already processed.',
                'event_id' => $result['event_id'],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'code' => $result['result'] === 'duplicate' ? 'duplicate_received' : 'event_accepted',
            'message' => $result['result'] === 'duplicate'
                ? 'This event was already accepted for processing.'
                : 'The event was stored for asynchronous validation.',
            'event_id' => $result['event_id'],
        ], 202);
    }
}
