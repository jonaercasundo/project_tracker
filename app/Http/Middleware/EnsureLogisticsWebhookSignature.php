<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnsureLogisticsWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $maximumPayloadBytes = (int) config('logistics-webhooks.maximum_payload_bytes', 65536);
        $payload = $request->getContent();

        if (strlen($payload) > $maximumPayloadBytes) {
            return response()->json([
                'success' => false,
                'code' => 'payload_too_large',
                'message' => 'The request body exceeds the configured size limit.',
            ], 413);
        }

        if (! $request->isJson()) {
            return response()->json([
                'success' => false,
                'code' => 'unsupported_media_type',
                'message' => 'Content-Type must be application/json.',
            ], 415);
        }

        $integrationId = $request->header('X-Integration-Key-Id');
        $timestamp = $request->header('X-Webhook-Timestamp');
        $signature = $request->header('X-Webhook-Signature');
        $keys = config('logistics-webhooks.keys', []);

        if (! is_string($integrationId)
            || preg_match('/\A[A-Za-z0-9._-]{1,64}\z/', $integrationId) !== 1
            || ! is_array($keys)
            || ! isset($keys[$integrationId])
            || ! is_string($keys[$integrationId])
            || $keys[$integrationId] === ''
            || ! is_string($timestamp)
            || preg_match('/\A[0-9]{10}\z/', $timestamp) !== 1
            || ! is_string($signature)
            || preg_match('/\Av1=([a-f0-9]{64})\z/', $signature, $matches) !== 1
        ) {
            return $this->unauthorized();
        }

        if (abs(time() - (int) $timestamp) > (int) config('logistics-webhooks.signature_window_seconds', 300)) {
            return response()->json([
                'success' => false,
                'code' => 'stale_signature',
                'message' => 'The signature timestamp is outside the allowed time window.',
            ], 401);
        }

        $signedContent = $integrationId."\n".$timestamp."\n".$payload;
        $expectedSignature = hash_hmac('sha256', $signedContent, $keys[$integrationId]);

        if (! hash_equals($expectedSignature, $matches[1])) {
            return $this->unauthorized();
        }

        $rateLimitKey = 'logistics-webhook:'.hash('sha256', $integrationId.'|'.$request->ip());
        $rateLimit = (int) config('logistics-webhooks.rate_limit_per_minute', 120);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $rateLimit)) {
            $retryAfter = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'success' => false,
                'code' => 'rate_limited',
                'message' => 'Too many webhook requests. Retry after the indicated delay.',
            ], 429)->header('Retry-After', (string) $retryAfter);
        }

        RateLimiter::hit($rateLimitKey, 60);
        $request->attributes->set('logistics_integration_id', $integrationId);

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response()->json([
            'success' => false,
            'code' => 'invalid_signature',
            'message' => 'Webhook authentication failed.',
        ], 401);
    }
}
