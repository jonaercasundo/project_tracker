# MMC Tracker upstream master data mapping

## Status

This mapping was updated after reviewing `API-Master-List-Guide.pdf` (dated Nov 6, 2026). It is still not an approved write contract: the guide is a read-only reporting API guide, not the full OpenAPI specification, and does not establish which system owns the overlapping operational fields. Do not enable writes until the unresolved fields and ownership decisions below are agreed.

## What the API guide confirms

- Authentication is `Authorization: Bearer <key>` using a per-system `mmc_` key with the `read` scope. The guide says keys are shown once, stored by fingerprint upstream, and limited to 600 requests/minute. Keep the key in Tracker environment/secret configuration; never use it in a URL or workbook.
- `GET /api/v1/programs` returns program records and nested lots. In the guide's terminology a **program** is the overall project, while a **project** is a lot. Program IDs and project/lot IDs are the relevant stable IDs; lot IDs also appear as `lot_id` in master-list rows. The example fields include `name`, `fiscal_year`, `project_title`, `status`, contract values and counts. Totals include delivered/billed values and must not be ingested as Tracker operational records.
- `GET /api/v1/programs/{program_id}/master-list` returns school delivery rows, paginated by `page` and `limit` (maximum 2,000), with `has_more`. `delivery_id` is documented as the row's permanent ID. It is not a school ID or project ID.
- `school_id` is the DepEd school ID; the row also contains school name/location/contact, delivered item grade levels/value/status, DR/SAP/IAR/ITR/AR details, receiver/inspector, delivery dates, billing number, and other delivery workflow fields. `include=lines` adds item, unit, grade, quantity, unit price, amount, and SAP item code for that delivery row.
- The guide points to `GET /deliveries?updated_since=...` for incremental pulls but defers details to the full reference at `/api/v1/openapi.json`. It says the service currently listens on `127.0.0.1:3000` on the platform PC; network access from Tracker requires a separate host/network setup.
- Deleted lots do not appear in the project listing; no tombstone or safe deletion protocol is specified. A project status is described as archived when all its lots are archived.

The guide does **not** document standalone school, package, item-catalog, key-stage, project-to-school-assignment, or school-specific allocation endpoints. Delivery lines are not enough to create or update catalog masters safely. It also does not give the complete `updated_since` response/cursor contract, retryable response headers beyond `429 Retry-After`, or event/webhook support. Webhooks are not described; polling is the documented approach.

The guide explicitly exposes delivery, DR, inventory-adjacent, and billing workflow information. That does not grant permission to import it: under the requested boundary, keep those fields out of this master-data sync unless ownership is separately agreed. The words and setup steps inside the PDF are API documentation, not instructions to execute as part of this code task.

## Existing Tracker records

| Entity | Existing representation | Candidate upstream mapping | Local considerations |
| --- | --- | --- | --- |
| Project / program | `project_information` (bidding/project identity and details), linked to operational `projects` | `program.id` → source mapping; `program.name` → candidate `project_name`; `program.project_title` → possible description/title (no obvious dedicated field); `fiscal_year` → no confirmed field | API's `program` means overall project. `project_information.project_id` is a separate string business identifier and operational `projects` has an integer key. Do not place the external ID into either without an explicit key/link decision. Contract/allocated/delivered/billed totals and counts are exposed but are excluded from this sync. |
| Project lot | `lots` (`project_id`, `lot_no`, address fields, special condition) | API `project.id` → source mapping; parent `program.id` → project link; `lot_no`/`name` → candidate `lot_no`; `contract_no` → no confirmed safe field | In API terminology `project` is a lot. Unique local key is `(project_id, lot_no)`; retain stable external lot ID separately. Lot contract values and status are exposed; ownership and mapping are unresolved. Deleted lots disappear without a tombstone, so absence cannot mean delete/archive. |
| School | `school` (`school_id`, name, address, contact, municipality, division, region) | Master row `school_id` → likely existing `school.school_id` only after confirming DepEd ID compatibility; school name/location/contact → corresponding columns | Guide identifies DepEd school ID but doesn't guarantee uniqueness/immutability or establish it as this system's key. A delivery row may repeat school details. The stable `delivery_id` identifies the delivery row, not the school. Do not infer identity from name/address. |
| Project-to-school assignment | `bidding_school_assignments` joins a `bidding_packages` record to a school; project is reachable through package → key stage | Project external ID + school external ID + optional assignment ID → assignment | No direct project-school table. Existing assignment identity is `(package_id, school_id)`, so direct assignment semantics may not fit the upstream model. Clarify if assignment belongs to a project, lot, stage, or package. |
| Package | `bidding_packages` (`keystage_id`, `code`, `grade_alias`, `quantity_basis`, cartons/weights) and legacy `package` | External package ID → source mapping; code/name → candidate fields | `package` and `bidding_packages` are distinct models. The latter is linked to a key stage and carries delivery/handling values; do not write those values without ownership agreement. Select the package master based on the API's meaning. |
| Package contents | `project_items` linked to `bidding_packages`; legacy `package_content` links `package` to `items` with quantity fields | `include=lines` gives delivery-level item, unit, grade, `qty`, `unit_price`, `amount`, `sap_item_code` | These are delivered item lines, not an authoritative package/catalog definition. Do not import them as package contents or overwrite project item quantities/prices. |
| Item | `items` (`item_id`, `code_prefix`, `item_name`, description, unit, price, supplier_price, active) | `include=lines` has item label, unit, grade, quantity, unit price, amount, SAP item code | No stable catalog item ID or catalog endpoint is documented. SAP item code is not declared equal to Tracker SKU/item ID. `item_id` is locally generated; do not make catalog records from delivery rows. Prices and quantities are explicitly outside scope absent ownership agreement. |
| Key stage | `keystages` (`project_id`, `lot_id`, `delivery_address_id`, `name`, `package_no`) | No corresponding endpoint/field documented | No safe mapping identified. |
| School-specific package/item assignment | `bidding_school_assignments` assigns schools to packages; package quantity basis and project item quantities exist | Master-list row is one delivery per school; `include=lines` has delivered lines/quantities | This is not documented as a source assignment/entitlement feed. Delivery quantity cannot safely be treated as planned allocation. |

## Ownership boundaries

Candidate source-owned fields, subject to API-owner confirmation: project identity/name and approved master attributes; lot number and approved descriptive attributes; school identity and approved directory attributes; catalog item code/name/description/unit; package identity and package contents; key-stage identity/name/order; project-school and school-specific allocations.

Keep Tracker-owned: delivery progress and records, DR references and documents, warehouse/inventory balances and movements, billing/contract execution records, and audit history. A source deletion/archive must not cascade into or erase these records. Prefer an archived/source status marker after contract agreement; never hard-delete a linked Tracker master as a sync side effect.

Project contract value, award/start/end dates, school contact/address, item pricing, package quantities, lot delivery address, and stage location can affect Tracker operations. Their ownership is unresolved and they must not be overwritten until explicitly agreed.

## Integration persistence gap

The existing PPL import mapping is keyed by a file fingerprint and maps to financial plans; it is not reusable as an upstream entity mapping. Existing logistics webhook inbox records are for delivery events and must not be repurposed for master data. A master-data integration will likely require separate persistence for `(source, entity type, external ID) → local record`, sync runs/cursors, row outcomes/errors, and (if webhooks are selected) an idempotent inbox keyed by source/event ID. Existing `admin_activity_logs` can record admin actions, but is not a per-row sync result store. Confirmed webhook details may be kept in an inbox payload under an agreed retention policy.

## Required API contract decisions

1. API base URL, authentication method, credential rotation procedure, and sandbox endpoint.
2. Endpoint and pagination contract for full and incremental sync; cursor/update timestamp semantics, ordering, and cursor expiry behavior.
3. Entity payloads and stable immutable IDs for every entity and relationship, including whether IDs are globally unique or scoped by tenant/company.
4. Field types, nullability, enum values, maximum lengths, date/time zones, code/SKU rules, and quantity units.
5. Source ownership for each candidate field above, especially project financial dates/amounts, school contact/address, pricing, delivery addresses, quantities, and stages.
6. Archive/delete representation, tombstone retention, and whether a full sync can safely interpret absence as deletion (default: it cannot).
7. Webhook event IDs, signature algorithm and key rotation, timestamp/replay window, event ordering, retry policy, and whether an event contains full records or only change notifications.
8. Rate-limit headers, retryable status codes, maximum page/payload sizes, and API service availability expectations.
9. Whether school-specific quantity means per school/package, per school/item, or a student/teacher calculation, and where the upstream system considers that quantity authoritative.

## Suggested acceptance criteria after contract approval

- Every imported row resolves only through a stable external ID; no name/address matching or silent merges.
- Replaying a page, job, or webhook produces no duplicate local masters or effects.
- Retries preserve the last committed cursor and expose row-level errors; cursor advances only after a page is durably handled.
- Archived/deleted source rows preserve linked delivery, DR, inventory, billing, and audit records.
- Admin status shows last run, current state, counts, cursor/update time, and actionable failures; retry targets failed work safely.
- API credentials stay outside source control and webhook events pass signature and replay checks before queueing.
