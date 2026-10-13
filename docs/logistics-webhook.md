# Logistics DR-level delivery status webhook

MMC Tracker receives delivery receipt events from Logistics. Logistics identifies shipments by DR number and does not send package IDs. `delivery.logistics_received` means Logistics has taken custody of all packages in the verified receipt scope. `delivery.delivered` means Logistics has delivered all packages in that scope to the destination. These are separate from MMC's manual receiving, acceptance, inventory, warehouse, and billing states.

## Endpoint

```http
POST /api/v1/logistics/webhooks/delivery-status
Content-Type: application/json
X-Integration-Key-Id: logistics-prod
X-Webhook-Timestamp: 1791873000
X-Webhook-Signature: v1=<lowercase hex HMAC-SHA256>
```

The body limit is 65,536 bytes. Requests are IP-throttled and additionally limited to 120 authenticated requests per minute per integration and IP.

## Authentication

Configure one dedicated secret for each integration key ID using `LOGISTICS_WEBHOOK_KEYS`. Keep these values in the deployment secret store.

```text
LOGISTICS_WEBHOOK_KEYS={"logistics-prod":"replace-with-a-random-secret"}
```

Sign the exact UTF-8 request body bytes. Do not parse, normalize, or re-encode the JSON before signing.

```text
signed_bytes = key_id + LF + timestamp + LF + raw_request_body
signature = lowercase_hex(HMAC-SHA256(secret, signed_bytes))
X-Webhook-Signature = "v1=" + signature
```

The timestamp is Unix time in seconds and must be within five minutes of MMC's clock. Sign every retry with a fresh timestamp while keeping its event ID and body unchanged. MMC compares signatures in constant time and does not log credentials or payload contents.

## DR-level event body

Each event describes the logistics milestone for one DR number. The DR number is a string and is matched exactly, including leading zeros and letter case.

```json
{
  "event_id": "e3b349d6-45cf-48bc-85e4-f79045b4b20e",
  "event_type": "delivery.logistics_received",
  "dr_reference": "000123-DR",
  "occurred_at": "2026-10-13T14:30:00+08:00",
  "sequence": 42
}
```

Only two event types are supported:

| Event type | Stored Logistics status | Meaning |
|---|---|---|
| `delivery.logistics_received` | `logistics_received` | Logistics has custody of all packages in this DR scope. |
| `delivery.delivered` | `delivered` | Logistics delivered all packages in this DR scope to the destination. |

The valid progression is `logistics_received` then `delivered`. A delivered event received before a Logistics Received event is retained in event history with `reconciliation_status=out_of_order` and does not advance the projection. Once delivered, the projection cannot regress to Logistics Received.

| Field | Requirement |
|---|---|
| `event_id` | Stable sender-generated string, at most 191 characters; retain it unchanged on retries. Unique per integration. |
| `event_type` | One supported delivery event type above. |
| `dr_reference` | String, at most 100 characters. Preserve leading zeros and case. |
| `occurred_at` | ISO 8601 timestamp including `Z` or an explicit UTC offset. |
| `sequence` | Positive integer, monotonically increasing for a resolved DR scope. Gaps are allowed. |

There is no package identifier or allocation reference in this contract. `delivery.delivered` is an external Logistics milestone and does not mean MMC acceptance, warehouse release, or billing completion.

## DR resolution and quarantine rules

The verified MMC schema has no global uniqueness constraint on `deliveries.dr_no`. A receipt scope is the exact tuple `project_id + school_id + dr_no`; more than one `deliveries` row may legitimately belong to that scope. Matching compares DR text exactly and never selects an arbitrary delivery row.

Automatic resolution is limited to the authenticated integration's configured project IDs. Set `LOGISTICS_WEBHOOK_PROJECT_SCOPES` to a JSON object keyed by the same integration key ID:

```text
LOGISTICS_WEBHOOK_PROJECT_SCOPES={"logistics-prod":{"project_ids":[10,11],"school_ids":[]}}
```

`school_ids` is optional. If supplied, it further narrows the integration's allowed schools. An integration without project scope needs an approved explicit mapping for each DR; it cannot resolve against all MMC projects by default.

For reviewed exceptions, an administrator may add an approved row to `logistics_webhook_dr_mappings`. It binds one integration and exact DR string to one `project_id` and `school_id`; `approved_by` and `approved_at` record the approval. A mapping only resolves if that exact scope currently contains delivery rows for that DR.

The asynchronous resolver records `resolution_status` and `reconciliation_status` on the immutable event record:

| Condition | Recorded result |
|---|---|
| Exactly one receipt scope within the integration's project/school scope, or a valid approved mapping | `resolution_status=resolved` |
| No delivery rows use that exact DR | `unknown` |
| More than one project/school scope remains | `ambiguous` |
| DR exists but no authorized scope or mapping permits it | `out_of_scope` |
| Equal sequence to the latest accepted event | `duplicate_sequence` |
| Lower sequence than the latest accepted event | `out_of_order` |
| Higher sequence but occurrence time is not newer | `stale` |
| First `logistics_received` event or a valid later `delivered` event with a higher sequence and newer occurrence time | `accepted` |
| `delivered` before `logistics_received`, or an attempted reverse transition | `out_of_order` |

Unknown, ambiguous, and out-of-scope events are retained for reconciliation and do not synchronize status. A repeated `event_id` with the same exact body is idempotent; reuse with different request bytes returns a conflict. Separate event IDs with equal, lower, or stale sequence/times remain in history with the result above. No event is silently allowed to replace a newer accepted Logistics milestone. Sequence gaps are accepted; Logistics should send each scope's events in increasing sequence order.

Accepted milestones update only `logistics_dr_tracking`, keyed by integration, verified project, verified school, and exact DR string. The projection keeps a reference to the latest immutable webhook event. No event writes `deliveries.status`, `package_status.status`, warehouse or inventory records, manual receiving, acceptance, or billing data. `LogisticsDrTrackingService::dashboardRows()` is a read-only Phase 2E integration seam that returns the Logistics milestone and an MMC receiving summary derived from the existing delivery/package allocations and latest package status rows. Existing monitoring calculations are unchanged.

## MMC monitoring dashboard

The authenticated MMC Delivery Monitoring page includes a separate Logistics DR Tracking section. It shows DR#, project, school, integration key ID, the latest Logistics milestone and timestamp, MMC receiving completion with package counts, and the tracking reconciliation state. Separate project/school/DR/integration scopes remain separate even when DR strings are reused. A DR with no accepted Logistics milestone is not assigned a Logistics status or shown as a tracking row.

The section uses the authenticated `GET /deliveries/monitoring/logistics` endpoint. It inherits the dashboard's `auth`, MMC company-context, and user/administrator role middleware. It returns only rendered tracking rows, summary counts, and pagination metadata; it never returns signed payloads or credentials. Filters support an exact string DR#, project, school ID/name, Logistics status, and local-date range. Results are server-side paginated at 10, 25, or 50 scopes per page.

The section polls every seven seconds while the page is visible and refreshes only its own rows and summary cards. In-flight requests are not overlapped; polling stops while the page is hidden. Existing project delivery progress reports and calculations are not part of this refresh.

The summary cards count Logistics Received and Logistics Delivered from the unique verified tracking scopes. Pending Logistics updates count distinct project/school/DR scopes represented by MMC delivery rows that have no matching Logistics tracking milestone; this card does not create a Logistics status. Reconciliation required counts distinct integration/DR event scopes with processed unresolved or rejected reconciliation outcomes. DR/project/school filters and date filters apply; the date range uses local dates for Logistics event times and delivery dates for scopes that have no event. A selected Logistics status filters status-specific counters and rows; the pending count remains the number of scopes without a milestone.

## Acknowledgements and errors

```http
HTTP/1.1 202 Accepted
Content-Type: application/json
```

```json
{
  "success": true,
  "code": "event_accepted",
  "message": "The event was stored for asynchronous validation.",
  "event_id": "e3b349d6-45cf-48bc-85e4-f79045b4b20e"
}
```

The asynchronous worker resolves the DR after acknowledgement. A `202` confirms durable receipt and queueing, not that a DR was matched or the milestone accepted. Reconciliation results are stored in `logistics_webhook_events`; this phase does not provide an outbound callback.

| HTTP status | Response code | Sender action |
|---:|---|---|
| 400 | `malformed_json` | Correct the JSON; do not retry unchanged. |
| 401 | `invalid_signature`, `stale_signature` | Correct signing/credentials or send with a fresh timestamp. |
| 413 | `payload_too_large` | Reduce the request body. |
| 415 | `unsupported_media_type` | Send `application/json`. |
| 409 | `event_id_conflict` | Stop and reconcile event ID reuse. |
| 422 | `validation_failed` | Correct required fields or event type. |
| 429 | `rate_limited` | Retry after the `Retry-After` delay, with a fresh signature timestamp. |
| 503 | `event_stored_retry_later` | Retry the same event ID and body with a fresh signature timestamp. |

## Storage and deployment

The additive migrations create `logistics_webhook_events` reconciliation fields, `logistics_webhook_dr_mappings`, and `logistics_dr_tracking`. The tracking scope uses SHA-256 hashes for the school ID and DR number in its composite unique key so case differences remain distinct under MariaDB's common case-insensitive collations. It references the latest source event and stores the original identifiers as strings. Existing delivery and package tables are not modified. The worker uses the dedicated `logistics_webhooks` database queue connection and `jobs` table. Run:

```sh
php artisan queue:work logistics_webhooks --queue=logistics-webhooks --tries=3 --timeout=60
```

Run Laravel's scheduler every minute to invoke `logistics:webhooks:recover --limit=100`. The recovery command requeues events that were durably stored but not queued. The repository has no Docker Compose, Dockerfile, Supervisor, or worker service definition, so deployment orchestration must start this worker. Apply and verify the new migrations in a non-production environment before production rollout; this implementation does not execute migrations automatically.

For local verification:

```powershell
$env:LOGISTICS_WEBHOOK_KEYS = '{"logistics-test":"test-secret"}'
php artisan test --compact tests/Feature/LogisticsWebhookTest.php
```

The webhook tests explicitly use an in-memory SQLite database and test fixture tables; they do not connect to the configured MariaDB database.

## Operational decisions

- Logistics must maintain a monotonically increasing sequence per DR receipt scope. Retries keep the same event ID, body, and sequence, while refreshing the signature timestamp.
- Sequence gaps are accepted, but event occurrence time must also move forward.
- MMC must configure each integration's authorized project/school scope and approve any explicit DR mapping before enabling that integration.
- MMC manual receiving and acceptance remain independent internal states. Logistics cancellation and exception events are not part of this finalized two-event contract.
- No MariaDB staging connection is provided by this repository's test configuration. Automated webhook tests use isolated in-memory SQLite; validate this additive migration and concurrency behavior against a separately provisioned MariaDB staging database before deployment.
