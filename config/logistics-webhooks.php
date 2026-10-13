<?php

$configuredKeys = json_decode((string) env('LOGISTICS_WEBHOOK_KEYS', '{}'), true);
$configuredScopes = json_decode((string) env('LOGISTICS_WEBHOOK_PROJECT_SCOPES', '{}'), true);

return [
    'keys' => is_array($configuredKeys) ? $configuredKeys : [],
    'signature_window_seconds' => 300,
    'maximum_payload_bytes' => 65536,
    'rate_limit_per_minute' => 120,
    'integration_scopes' => is_array($configuredScopes) ? $configuredScopes : [],
    'allowed_event_types' => [
        'delivery.logistics_received',
        'delivery.delivered',
    ],
];
