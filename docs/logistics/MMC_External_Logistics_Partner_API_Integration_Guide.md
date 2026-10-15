# MMC Tracker External Logistics Partner API Integration Guide

**Document status:** Implementation-based handover draft  
**Audience:** External Logistics Application development and operations teams  
**Contract version:** Requires Confirmation  
**Last verified against source:** 13 October 2026

This guide describes the Logistics webhook receiver currently implemented in MMC Tracker. It is not a substitute for environment-specific production values. Any item marked **Requires Confirmation** must be agreed by MMC and the Logistics partner before production traffic begins.

## 1. Integration overview

The Logistics Application sends delivery-receipt milestone events to MMC Tracker. MMC authenticates each request with HMAC-SHA256, stores accepted events, acknowledges receipt, then validates the DR scope and updates a separate Logistics tracking projection asynchronously.

```text
Logistics Application
  └─ HTTPS POST + HMAC ─> MMC API route
       ├─ signature, timestamp, size and rate-limit checks
       ├─ validate and persist immutable webhook event
       ├─ return 202 (durable receipt; processing is asynchronous)
       └─ queue worker ─> resolve integration/project/school/DR scope
                           └─ update Logistics tracking projection
```

Logistics status is separate from MMC's delivery/package status, warehouse release, inventory, manual receiving, acceptance, and billing workflows. A Logistics `delivered` event does not confirm MMC acceptance.

## 2. Endpoint

| Item | Value |
|---|---|
| Method | `POST` |
| Path | `/api/v1/logistics/webhooks/delivery-status` |
| Content type | `application/json` |
| Production base URL | **Requires Confirmation** from MMC deployment owner |
| Request body limit | 65,536 bytes |

The application route is registered in `routes/api.php`. MMC must provide the production HTTPS hostname and confirm that TLS termination, proxy limits, and routing preserve the request body bytes.

## 3. Authentication and signing

Send these exact headers:

```http
Content-Type: application/json
X-Integration-Key-Id: <issued-key-id>
X-Webhook-Timestamp: <Unix-time-seconds>
X-Webhook-Signature: v1=<64-lowercase-hex-characters>
```

MMC signs the exact raw request body bytes. The signed content is the UTF-8 encoding of:

```text
key_id + LF + timestamp + LF + raw_request_body
```

`LF` is one line-feed byte (`0x0A`); do not use CRLF. Calculate:

```text
signature_hex = lowercase_hex(HMAC-SHA256(secret, signed_content))
X-Webhook-Signature = "v1=" + signature_hex
```

The JSON must not be parsed, normalized, or re-serialized between signing and sending. A retry keeps the same event ID and body bytes; it uses a fresh timestamp and a newly calculated signature.

`X-Integration-Key-Id` identifies an issued credential. The database-managed channel can issue credential key IDs distinct from the integration's business ID. Do not assume the key ID is the integration ID. MMC provisions the key ID and secret through a separate secure channel. HMAC comparison is constant-time in the receiver.

The timestamp must be exactly 10 decimal digits representing Unix seconds and within 300 seconds (five minutes) of MMC server time. Requests outside this window receive `401 stale_signature`. Keep sender and receiver clocks synchronized. Event-ID idempotency prevents repeat processing of an already-known event, but the timestamp window is the request freshness check; a replay within the window with the same body is treated as a duplicate.

The receiver does not log the secret or raw payload in its authentication/queue failure logs. The sender must also keep secrets and signed request material out of logs, tickets, and source control.

## 4. Request schema

The request body must be a JSON object. The implementation requires the following fields:

| Field | Type | Requirement and validation |
|---|---|---|
| `event_id` | string | Required, non-empty, maximum 191 characters. Stable across retries; unique within one integration. |
| `event_type` | string | Required; must be enabled for this integration and be one of the two event types below. |
| `dr_reference` | string | Required, maximum 100 characters. Preserve it exactly as a string, including leading zeros, punctuation, and letter case. |
| `occurred_at` | string | Required valid ISO 8601 timestamp with seconds and `Z` or an explicit UTC offset. Up to six fractional-second digits are accepted. |
| `sequence` | JSON integer | Required positive integer. Must increase monotonically for the resolved DR scope; gaps are allowed. |

No optional fields are defined in the current contract. The current validator does not explicitly reject unknown JSON properties. **Requires Confirmation:** whether the partner may send additional properties and whether a future contract should reject them.

Example (sign the exact bytes of the body actually transmitted):

```json
{
  "event_id": "e3b349d6-45cf-48bc-85e4-f79045b4b20e",
  "event_type": "delivery.logistics_received",
  "dr_reference": "000123-DR",
  "occurred_at": "2026-10-13T14:30:00+08:00",
  "sequence": 42
}
```

Example request construction for a non-production test credential (Bash, `curl`, and OpenSSL):

```sh
key_id='logistics-test-key'
secret='use-the-test-secret-provided-by-mmc'
timestamp="$(date +%s)"
body='{"event_id":"e3b349d6-45cf-48bc-85e4-f79045b4b20e","event_type":"delivery.logistics_received","dr_reference":"000123-DR","occurred_at":"2026-10-13T14:30:00+08:00","sequence":42}'
signature="$(printf '%s\n%s\n%s' "$key_id" "$timestamp" "$body" | openssl dgst -sha256 -hmac "$secret" | awk '{print $NF}')"

curl --request POST 'https://<mmc-host>/api/v1/logistics/webhooks/delivery-status' \
  --header 'Content-Type: application/json' \
  --header "X-Integration-Key-Id: $key_id" \
  --header "X-Webhook-Timestamp: $timestamp" \
  --header "X-Webhook-Signature: v1=$signature" \
  --data-binary "$body"
```

Use the production host and secret supplied by MMC only after go-live approval. Do not use a real credential in shell history; the snippet is for a disposable test credential. `--data-binary` preserves the body bytes used to calculate the signature.

## 5. Supported events and progression

| Event type | MMC Logistics status | Meaning |
|---|---|---|
| `delivery.logistics_received` | `logistics_received` | Logistics has received custody of all packages in the verified DR scope. |
| `delivery.delivered` | `delivered` | Logistics reports delivery of all packages in the verified DR scope to the destination. |

The implemented progression is `logistics_received` → `delivered`. A `delivered` event received before `logistics_received` is retained in event history and reconciled as out of order; it does not advance tracking. A later `logistics_received` cannot reverse a delivered scope. A separate new `logistics_received` event after the scope is already received is not an accepted progression. No cancellation, exception, partial-delivery, package-level, or MMC-acceptance event is supported by this implementation.

Each event must have a higher sequence and a later occurrence time than the latest accepted event for that integration and resolved DR scope. Equal sequence is recorded as `duplicate_sequence`; lower sequence as `out_of_order`; a higher sequence with equal/older occurrence time as `stale`. Sequence gaps are allowed. Reconciliation outcomes are asynchronous and are not returned in the initial HTTP acknowledgement.

## 6. DR and scope matching

The request carries `dr_reference` only. The partner does not send `project_id` or `school_id` in the current contract. MMC resolves the DR using the authenticated integration's configured project and school authorization, or an MMC-approved exact DR mapping.

MMC treats a receipt scope as the exact combination of project, school, and DR reference. A single scope can contain multiple delivery rows. MMC does not assume DR numbers are globally unique, and never applies an event to every matching scope.

Before go-live, MMC and Logistics must verify that every partner DR resolves to exactly one authorized project/school receipt scope. If the same exact DR exists in multiple authorized projects or schools, automatic resolution is ambiguous and the event is quarantined for reconciliation. MMC must narrow that integration's school/project scope or provision an approved mapping for the exact DR. Unknown and out-of-scope DRs are also retained for reconciliation and do not update tracking.

DR matching is exact and case-sensitive; preserve leading zeros. **Requires Confirmation:** the partner's authoritative source for DR strings and the operational process for resolving reused DRs before sending events.

## 7. Acknowledgements and error handling

Successful receipt response:

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

`202` means the request was authenticated, validated, stored, and queued (or already durably received for processing). It does not confirm successful DR matching or a synchronized status.

| HTTP | Response code | Meaning and sender action |
|---:|---|---|
| `200` | `duplicate_processed` | Same event was already processed. Treat as successful; do not create a new event ID. |
| `202` | `event_accepted` | Event stored for asynchronous validation. |
| `202` | `duplicate_received` | Same event was already accepted for processing. Treat as acknowledged. |
| `400` | `malformed_json` | Body is not a valid JSON object. Correct the body; do not retry unchanged. |
| `401` | `invalid_signature` | Missing/invalid credential headers or signature. Check key ID and signing bytes. |
| `401` | `stale_signature` | Timestamp outside the five-minute window. Retry with current timestamp and recomputed signature. |
| `409` | `event_id_conflict` | Same event ID was received with different raw request bytes. Stop automatic retries; investigate sender event-ID reuse. |
| `413` | `payload_too_large` | Request exceeded 65,536 bytes. Reduce the request body. |
| `415` | `unsupported_media_type` | Send JSON with `Content-Type: application/json`. |
| `422` | `validation_failed` | Required field, event type, type, length, timestamp, or sequence validation failed. Correct the request. |
| `429` | `rate_limited` (custom limiter) | Rate limit reached. Honor `Retry-After` when present and retry with a fresh signature timestamp. |
| `503` | `event_stored_retry_later` | Event is stored but could not be queued. Retry the same event ID and exact body with a fresh timestamp/signature. |

The route also has a framework throttle of 120 requests per minute. Its framework-generated `429` body is not explicitly standardized by this endpoint; **Requires Confirmation:** partner handling of a `429` that has no `Retry-After` header. Other unexpected infrastructure/server errors are not given a webhook-specific response contract; retry policy for these responses requires confirmation with MMC operations.

Example validation error shape:

```json
{
  "success": false,
  "code": "validation_failed",
  "message": "The webhook event is invalid.",
  "errors": {
    "event_type": ["The selected event type is invalid."]
  }
}
```

Error text may vary by failed field; clients should use the HTTP status and `code` where supplied.

## 8. Duplicate delivery and retry policy

MMC enforces uniqueness on `(integration, event_id)`. The stored SHA-256 payload hash is calculated from the exact raw request body. Reusing the same event ID with byte-identical body is idempotent. Reusing it with any byte-different body—including whitespace or property-order changes—returns `409 event_id_conflict`.

Recommended sender behavior, consistent with the receiver:

1. Persist the event ID, body bytes, DR, type, occurrence timestamp, and sequence before sending.
2. On network timeout, retry the same event ID and exact body; refresh only the timestamp and signature headers.
3. Treat `200` and `202` as acknowledged. Do not resend as a new event just because dashboard tracking has not updated yet; processing is asynchronous.
4. On `429`, honor `Retry-After`; otherwise use bounded exponential backoff with jitter. **Requires Confirmation:** agreed retry ceiling and schedule.
5. On `503`, retry the same event ID/body; alert if retries are exhausted because MMC may need to run event recovery.
6. Do not automatically retry `400`, `409`, `413`, `415`, or `422` without correcting the request. For `401`, correct authentication or timestamp before retrying.

MMC's queue job is configured for three attempts with 10- and 60-second backoff intervals. MMC also schedules recovery of stored-but-unqueued events once per minute. These are receiver-side recovery mechanisms, not a guarantee of immediate dashboard visibility.

## 9. Partner and MMC responsibilities

| Logistics partner | MMC Tracker |
|---|---|
| Preserve exact DR strings and send one event per supported milestone. | Provision integration, credential, enabled event types, and project/school scope. |
| Generate stable unique event IDs and strictly increasing per-scope sequence numbers. | Verify the DR resolves to one authorized project/school receipt scope. |
| Supply accurate ISO 8601 occurrence time with timezone and keep clocks synchronized. | Maintain authenticated event storage, queue processing, reconciliation, and tracking projection. |
| Sign the exact transmitted bytes and protect credential material. | Maintain and rotate the signing secret; provide the key ID/secret securely. |
| Retry according to agreed rules without changing the event ID/body. | Investigate unknown, ambiguous, stale, and out-of-order events and advise partner of corrective action. |
| Test the agreed UAT cases and provide delivery/sequence evidence. | Keep Logistics state separate from MMC receiving, acceptance, inventory, and billing states. |

## 10. UAT test scenarios and acceptance criteria

The parties should run these scenarios in a non-production environment using an approved test integration and known DR scopes:

| Scenario | Acceptance criteria |
|---|---|
| Valid signed `delivery.logistics_received` | `202`; event stored; worker resolves one authorized scope; projection becomes `logistics_received`. |
| Valid later `delivery.delivered` with higher sequence and later time | `202`; event stored; projection advances to `delivered`. |
| Same event ID and exact body submitted again | Acknowledged as duplicate (`202` while pending or `200` after processed); no second application. |
| Same event ID with changed body bytes | `409 event_id_conflict`; no overwritten event. |
| Missing/incorrect signature or stale timestamp | `401`; no accepted event. |
| Invalid JSON/event type/sequence/timestamp | `400` or `422` as specified; no accepted event. |
| DR outside integration scope or unknown | Initial receipt can be `202`; event remains auditable and reconciliation identifies unresolved scope; tracking unchanged. |
| Same DR in multiple authorized projects/schools | Event is marked ambiguous and does not update either unrelated scope. |
| Delivered before received, stale or lower-sequence event | Event retained; tracking does not regress or advance incorrectly. |
| Worker unavailable then recovery | Webhook receipt behavior and recovery outcome are observable to MMC operators; partner does not create replacement event IDs. |
| MMC internal receiving/inventory/billing checks | Logistics events do not change MMC statuses, inventory, acceptance, or billing eligibility. |

**UAT pass condition:** all agreed cases produce the expected HTTP acknowledgement, immutable event/audit result, resolved scope or quarantine outcome, and tracking projection; MMC confirms no changes to protected internal workflows. Partner and MMC sign off the final test record and production scope.

## 11. Production go-live checklist

- [ ] MMC provides and partner verifies the production HTTPS base URL and certificate chain.
- [ ] MMC confirms deployed migrations, database compatibility, queue connection, persistent worker, and scheduler/recovery command.
- [ ] MMC provisions active integration and authorized project/school scope; every DR in the launch set resolves uniquely or has an approved mapping.
- [ ] MMC and partner confirm the production contract version, production event types, event sequence scope, and retry/backoff policy.
- [ ] Signing key is transferred out of band and stored in an approved secret manager on both sides.
- [ ] Sender/receiver clocks are synchronized; proxy/body-size settings preserve exact body bytes and allow 65,536-byte maximum requests.
- [ ] UAT cases pass, including duplicate, conflicting ID, ambiguous DR, stale sequence, and worker recovery.
- [ ] Monitoring and support contacts are exchanged; alert ownership and escalation severity/response targets are agreed (**Requires Confirmation**).
- [ ] Credential rotation overlap and emergency revocation procedure are rehearsed.
- [ ] MMC confirms production readiness and authorizes the partner to enable delivery traffic.

## 12. Support and troubleshooting

Use the response `code` and HTTP status as the first diagnostic signal. For `invalid_signature`, compare the exact raw bytes, key ID, LF separators, lowercase hex digest, clock, and active credential. For `stale_signature`, refresh the timestamp and signature. For `event_id_conflict`, stop retries and compare the original body bytes. For `202` with no visible status, allow asynchronous processing, then ask MMC to inspect event resolution/reconciliation using the event ID; do not send a new ID to force processing. For `ambiguous`, `unknown`, or `out_of_scope` reconciliation, provide the event ID and exact DR to MMC through the agreed secure support channel.

Never send a secret, full signature secret material, or sensitive payload in an ordinary support ticket. **Requires Confirmation:** MMC and Logistics production support email/phone/ticket queue, on-call coverage, escalation contacts, response targets, and secure channel for exchanging event diagnostics.

## 13. Items requiring confirmation before partner development/go-live

1. Production HTTPS hostname/base URL and any IP allowlisting requirements.
2. Partner ownership of `event_id`, sequence-number scope, and sequence persistence across retries/restarts.
3. Retry ceiling/backoff/jitter, timeout behavior, and behavior for framework-throttle `429` without `Retry-After` and unexpected `5xx` responses.
4. Whether additional JSON properties should be allowed or rejected.
5. Whether each integration will be scoped narrowly enough to resolve repeated DRs, or MMC will approve exact mappings before launch.
6. UAT environment, test credential exchange, representative DR data, sign-off owners, and test window.
7. Production incident contacts, escalation path, alert ownership, support hours, and response-time targets.
8. Go-live approval owner and rollout/rollback timing.

MMC operators should use the separately maintained internal credential provisioning checklist. Do not distribute internal credential procedures with the partner copy.
