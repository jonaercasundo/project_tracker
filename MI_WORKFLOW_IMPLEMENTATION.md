# MI financial workflow implementation

Date: 6 October 2026. Baseline: MI_WORKFLOW_AUDIT.md and its first implementation appendix. This report describes the subsequent implementation phase. The two liquidation systems remain separate.

## Re-audit checklist

| Classification before this phase | Verified result |
| --- | --- |
| Already fixed | Ownership, ordinary company isolation, authenticated/private receipts, stable item identities, exact budget/travel totals, correct actor keys/routes, Accounting separation, locks, replay checks and ordinary soft-delete evidence. Original suite: 75 tests / 303 assertions. |
| Still broken | Budget/travel company attribution absent; approval denied to everyone rather than assignable by permission; legacy public exposure; signed status resets could reopen amendments; pending company_user FK mismatched users.user_id. |
| Needs enhancement | Actual release/settlement evidence, transition service, activity history/UI, Accounting queues, PDF completeness and isolated MySQL verification. |
| Needs business decision | Approver recipients, company scope, amendments/rejection/cancellation, currencies, release limits, settlement, closure, self-review exceptions, zero amounts and balance tolerance. |

Inspected current routes, financial controllers/policies/models/migrations, middleware, amount helper, relevant Blade/PDF views, receipts, role provisioning, company context/redirects and targeted tests before edits. Versions remain Laravel 11.54.0, Spatie Permission 6.25.0, Pest 3.7.2, DomPDF 3.1.2 and Pint 1.29.1. Boost documentation/schema tools are available in this phase; read-only budget schema inspection returned no configured budget tables. Companies/company_user create migrations are Pending in the configured migration history.

No application migrations, historical attribution/backfill, live permission seeding/grants, deployment or real receipt migration was performed.

## Implemented

1. Nullable persisted company attribution on budget/travel. New records use validated authenticated MI context. Lists and policies compare actual company; historical null ownership and mismatched travel/parent companies fail closed.
2. Approval uses mi.budget.approve and mi.travel.approve, without grants. Existing route names/URIs are preserved; the old user-role restriction is replaced by authenticated MI context and actual record policies. Requester/liquidator self-approval remains denied.
3. Opt-in permission catalog seeder creates four names only. It is not called by DatabaseSeeder and was not run on the application.
4. MiFinancialWorkflowService centralizes submission, approval, Accounting note/review, release, receipt, settlement and closure. Parent budget locks precede travel locks. Policies, states and original stamps are rechecked inside transactions; activity commits with state changes.
5. Dedicated exact-decimal actual release records store amount/currency/method/reference, optional rate/note, company and actor/time. Requested budget is never copied into released cash automatically.
6. Settlement stores explicit return/reimbursement payments, release/expense snapshots and exact outstanding balance. It does not infer payment from variance. Recording/closure are disabled until confirmed configuration and permissions exist.
7. Activity journal stores actual transitions and actor name/role snapshots. No user update/delete endpoints exist; Eloquent instance mutation/deletion is blocked. Actor deletion nulls its FK while preserving snapshots.
8. Budget/travel soft deletion preserves items, relationships and history. Signed records cannot become editable by resetting status. Ordinary archive preserves rows/files; ordinary edits record removed-row evidence.
9. Legacy receipt command defaults to dry-run, requires company scope, rejects unsafe paths, copies to deterministic private references, verifies SHA-256, updates references/history atomically and is idempotent. Public sources are retained.
10. Accounting dashboard adds company-scoped budget/travel queues, filters and policy-protected processing routes. Company comes from validated context. Returned-for-correction is explicitly Not enabled.
11. Detail pages show actual history, responsible party/next action and policy/state-gated actions. Missing historical events remain unavailable; fabricated budget timeline removed.
12. Travel PDF includes company/travel/submission details, method breakdown, actual releases, available stamps, actor history, notes and settlement. Ordinary PDF gains history and rejects unsafe receipt references.
13. Item money uses decimal-string casts on both engines. Format/scale/capacity validation remains. Zero values and one-cent classification remain; classification uses Brick Math.
14. References include archived rows and continue beyond 9999 without rewriting existing references. Concurrent budget reference allocation remains unresolved.
15. Isolated MySQL harness rejects nonlocal connections, invalid tokens, application DB, databases not created by that process and an actual SQL DB differing from its verified target. New schemas are dropped at process shutdown. Regular tests roll back; race fixtures are cleaned only within the guarded schema.
16. Original 75 test cases / 303 assertions retained. One assertion now expects a declared decimal string consistently across engines.

## Files Changed

New:
- app/Services/MiFinancialWorkflowService.php
- app/Models/BudgetRelease.php, FinancialSettlement.php, FinancialActivity.php
- app/Console/Commands/MigrateMIReceiptsPrivate.php
- config/mi_financial.php
- database/migrations/2026_10_06_031105_add_mi_financial_attribution_and_audit_tables.php
- database/seeders/MIFinancialPermissionSeeder.php
- resources/views/mi_app/financial_history.blade.php
- tests/Feature/MIFinancialEnhancementTest.php
- tests/MIFinancialRaceWorker.php

Updated in this phase:
- BudgetRequestController, TravelLiquidationController, LiquidationController, Accounting_DashboardController, Accounting_LiquidationController.
- BudgetRequestPolicy, TravelLiquidationPolicy, MILiquidationPolicy.
- BudgetRequest, BudgetRequestItem, Liquidation, LiquidationItem, MI_Liquidation.
- routes/web.php; tests/MIWorkflowTestCase.php; tests/Pest.php; tests/Feature/MIWorkflowTest.php.
- Pending company_user create migration.
- Accounting dashboard; ordinary employee/Accounting detail/PDF; budget detail; travel detail/PDF.
- MI_WORKFLOW_AUDIT.md links to this subsequent phase.

Earlier audit fixes remain in the working tree. No unrelated catalog/designer rewrite was performed.

## Database Changes

The new forward migration:
- Adds nullable company FK, company/status index and soft-delete timestamps to budget_requests/liquidations. Existing attribution remains null; no membership-based backfill. Company deletion is restricted.
- Creates budget_releases: DECIMAL(12,2), optional DECIMAL(12,4) rate, actual payment evidence, restrictive budget/company FKs. Schema supports future events; current workflow permits one release transition.
- Creates financial_settlements: DECIMAL(12,2) snapshots/payments/outstanding balance, company FK and unique liquidation FK. One immutable settlement snapshot is supported; revisions/partial settlement need approved rules.
- Creates financial_activities: polymorphic record identity, company/actor FKs, actor snapshots, exact amount, metadata and history/queue indexes. Parent record IDs have no single FK because three different tables are journaled; history survives archival.
- Actor FKs use BIGINT UNSIGNED users.user_id, nullable/nullOnDelete. Company FKs reference companies.company_id.
- Refuses automatic down() because dropping financial evidence requires reviewed reconciliation; use forward fixes.

The pending company_user create definition now uses BIGINT UNSIGNED user_id. MySQL exposed its signed INT mismatch. This corrects fresh-install source only; independently deployed incompatible tables need separately reviewed forward reconciliation.

The existing unique one-travel-per-budget constraint remains, including archived travel. No financial rows/files were backfilled or deleted. Schema changes were executed only by isolated tests.

The harness verifies financial dependencies from empty, not the entire unrelated application migration chain. Existing B24 catalog/fresh-install problems remain. Before installation, review deployed tables/migration history, backups, volumes and maintenance needs. Code requires the new schema before these financial routes are activated.

### Safe historical attribution strategy

1. Inventory null-company parents and linked evidence read-only; keep them inaccessible through employee/Accounting financial routes/queues.
2. Authorized administrators verify original company using dated source documents, original payment evidence and contemporaneous records. Current membership is not proof.
3. Prepare an approved mapping with record IDs, evidence, reviewer/date and unresolved reasons. Resolve budget/travel/release/settlement company consistency together.
4. Build a separate bounded, idempotent command against that reviewed mapping, with dry-run, backups and transactional consistency checks. Do not fabricate historical activities.
5. Restore access only after verification. Leave ambiguous rows null for review. Tighten DB nullability only after complete reconciliation.

No attribution UI or automatic backfill is shipped; administrative review is a documented controlled process pending approved mapping.

## Authorization Matrix

Every allowed action also requires active MI membership, validated current company and matching actual record company. Travel parent/child company must agree.

| Actor / permission | Action | Allowed / denied |
| --- | --- | --- |
| MI user, owner | Create; own list/detail/PDF/receipt | Allowed within ownership/company |
| MI user, owner | Amend/archive eligible unprocessed state | Allowed; signed stamps/releases/processing prevent it |
| Other employee; wrong/null company | Read/edit/archive/PDF/review/approval | Denied |
| Employee without Accounting role | Accounting note/review/release | Denied |
| Accounting | Same-company processing view/note/review/release | Allowed in service-validated states; no employee edit rights |
| mi.budget.approve | Budget approval | Different requester, matching company, pending state |
| mi.travel.approve | Final travel approval | Different liquidator, matching company, reviewed state |
| Owner with approval permission | Self-approval | Denied |
| Accounting + mi.travel.settle | Settlement | Denied by default; enabled confirmed config, approved state, valid release evidence, no previous settlement required |
| mi.travel.close | Closure | Denied by default; enabled confirmed config, approved state and exact-zero recorded outstanding required |
| Normal user | Change/delete journal/release/settlement | No endpoints; instance mutation blocked |
| Any actor | Reject/return/cancel | Disabled/default denied |

Accounting self-review/release by a dual-role owner remains a separate business decision. No self-approval exception exists.

### Role provisioning

Spatie HasRoles, RoleMiddleware and financial policies are authoritative; users.role does not grant financial permissions. User and user are different names.

DatabaseSeeder creates Administrator, Manager and user; RoleSeeder creates Administrator, Manager and User. Registration always assigns Spatie user independently of legacy role input. UserController and RoleAccessPermissionController assign/sync roles and company memberships; they also mirror the first role into users.role. The inspected enum lacks accounting, so that mirroring remains a provisioning trap outside financial endpoints. No existing assignments/enum were changed automatically.

MIFinancialPermissionSeeder is opt-in and was not executed against the application. It creates web-guard names without grants. After management confirms recipients, authorized administrators must explicitly assign approved permissions through reviewed provisioning. No new permission-management UI or global Administrator bypass is introduced.

## Workflow

Existing enum vocabulary remains; no destructive status migration.

| Record | Transition | Guard / evidence |
| --- | --- | --- |
| Budget | Creation -> budget_requested | Owner/company, exact items, created/submitted events |
| Budget | budget_requested -> approved | Explicit permission, different requester, lock, approval stamp/amount snapshot |
| Budget | approved -> approved (note) | Accounting, once-only stamp/event |
| Budget | approved + note -> released | Accounting; actual payment record when configured, otherwise explicitly unquantified confirmation |
| Budget | released -> in_progress | Owner receipt, once-only timestamp/event |
| Travel | Creation draft -> submitted | Owned received parent, matching company, unique child, exact totals/events |
| Travel | submitted -> noted | Accounting review, once-only stamp/event |
| Travel | noted -> approved | Explicit permission, different liquidator; no automatic settlement/closure |
| Travel | approved -> approved (settlement) | Confirmed config + Accounting permission; explicit payments and immutable snapshot/event |
| Travel + budget | approved -> closed; in_progress -> liquidated | Confirmed config + closure permission; exact-zero outstanding; both records/events atomic |
| Ordinary | Creation -> Pending | Company/actor, private new receipts, event |
| Eligible unprocessed record | Soft archive | Policy + lock; evidence retained |

Unquantified release preserves the original verified endpoint. Its event is release_confirmed_unquantified; no budget_release row is invented. It cannot support settlement. Once payment recording is enabled, missing amount/currency/method/reference is rejected.

Outstanding = recorded release + explicitly recorded company reimbursement - actual expenses - explicitly recorded employee return. settlement_amount is the sum of recorded return/reimbursement payments, labelled recorded payments. This prepared reconciliation is not an approved accounting policy; recording/closure remain disabled. One-cent budget variance remains informational and never proves payment.

No new budget draft endpoint, rejection/return/cancellation, ordinary approval or partial/multiple-release policy is invented.

## Tests

| Engine / run | Passed | Assertions | Skipped | Failures |
| --- | ---: | ---: | ---: | ---: |
| Last combined isolated MariaDB 10.4.32 (MySQL driver) run | 121 | 570 | 0 | 0 |
| Last combined SQLite run | 116 | 541 | 5 MySQL-only | 0 |

Actual local server: MariaDB 10.4.32, not Oracle MySQL. No Oracle MySQL server/runtime was available (only Workbench; Docker CLI unavailable). Oracle MySQL-specific verification remains outstanding; do not interpret the compatible-server results as Oracle MySQL certification.

The final company-scoped employee selector adds two assertions to the Accounting queue case. Its affected test was rerun on both engines: one passed / 12 assertions each. The observed combined figures above precede that final selector adjustment. Original 75 cases / 303 assertions remain included.

Expanded coverage: permission assignment/default deny/self approval; unknown/foreign/mismatched company; replay/stamps/status reset; audit rollback; independent releases/currencies/limits/forged actors; exact settlement under/equal/over/return/reimbursement; actor snapshots/blocked activity mutations/endpoints; archive evidence; receipt dry-run/idempotency/path safety; Accounting queues/filtering; PDF rendering; archived references/five-digit continuation.

Five MySQL-driver-only tests verify schema/FK/DECIMAL/enum/uniqueness and simultaneous approval, release, receipt and travel creation. Each race has one success and one 422 repeat, one transition event/one travel row.

Isolated MySQL-driver test database in PowerShell (this session used MariaDB):

    $env:MI_TEST_MYSQL_TOKEN = [guid]::NewGuid().ToString('N').Substring(0,24)
    php artisan test --compact tests/Feature/MIWorkflowTest.php tests/Feature/MIFinancialEnhancementTest.php
    Remove-Item Env:MI_TEST_MYSQL_TOKEN

A fresh token is required. The harness creates only its distinct local DB, positively validates actual SQL selection before reset, refuses existing schemas not created by the current process and drops new schemas at shutdown. Early diagnostic schemas created before shutdown cleanup was added may remain; they are isolated test data, not application data.

SQLite, without a MySQL token:

    php artisan test --compact tests/Feature/MIWorkflowTest.php tests/Feature/MIFinancialEnhancementTest.php

The case hard-selects SQLite :memory:. Five MySQL checks are skipped.

Pint --dirty --format agent and git diff --check are final checks. Full application suite was not run: phpunit.xml still leaves its default DB overrides commented out. Run php artisan test --compact only after isolating that suite's DB. This targeted harness does not protect unrelated default TestCase bindings.

PDF QA: authorized real PDFs from isolated fixtures, every page visually reviewed using Windows PDF rendering (Poppler/Python renderer unavailable). Travel: 45 expenses, repeated table headers across two pages, releases/travel dates/history. Ordinary: report, invalid/missing receipt page and history across three pages. No overlap/clipping found. Ordinary VND zero-decimal/rate two-decimal display remains pending presentation rules. No browser responsive/dark-mode verification claimed.

Intermediate MySQL FK failure, enum assertion (case-insensitive Yes can map to yes), template punctuation conversion and harness teardown defects were corrected. Final results establish the delivered state.

## Security

Actual owner/company policies protect employee actions/PDF/receipts and explicit processing routes; middleware alone is insufficient. Unknown attribution is denied. Approval permissions never bypass company/self-approval checks. Client status/actor/company/totals are not authoritative.

Private receipts are directory constrained and parent authorized. PDFs reject unsafe references. Migration never removes public sources; static exposure remains until verified removal is separately authorized. No real receipt reference/file was migrated.

Transitions and activities share transactions, consistent parent/child lock order, stamps and replay checks. Unique travel linkage remains. Snapshots survive actor changes/deletion; archive retains financial children/files.

Append-only protection covers application instances/endpoints, not privileged SQL or future code bypassing model events. Operational DB privileges/retention/recovery need review. Existing ordinary FX/UI/chart display paths still use floats/formatting; persisted budget/release/travel/settlement authority uses exact decimals.

## NEEDS BUSINESS CONFIRMATION

1. Who receives mi.budget.approve?
2. Who receives mi.travel.approve?
3. Are budget/travel permanently MI-only or multi-company?
4. Can anyone approve their own request/liquidation? Default no; no exception exists.
5. Can submitted requests be amended, by whom, and how? Pending budget remains metadata-only; baseline travel draft/submitted PUT is retained with history, while edit GET remains draft-only.
6. What are rejection/return/cancellation rules and statuses?
7. Which currencies, FX rules and presentation precision are supported?
8. May actual release differ from approved amount; what limits/payment evidence are required?
9. What is the official refund/reimbursement, cash/card/agent settlement, partial settlement and correction process?
10. Is +/-0.01 balanced? Indicator preserved; proposed exact-zero closure remains disabled.
11. Are zero-value requests/payments permitted? Existing validation preserved.
12. Who may close/cancel and receive mi.travel.close?
13. Who receives mi.travel.settle; may dual-role Accounting process its own records?
14. Who verifies historical company evidence and approves the attribution mapping?
15. When may verified public sources be removed, and what is evidence retention?
16. What is the concurrent budget reference allocator/numbering/revision policy?
17. How should legacy users.role provisioning be reconciled across the application?

Release payment recording, settlement recording and closure flags remain false; supported currencies are empty and no permissions were granted. Unsupported transitions remain disabled.

This is not production-ready certification. Application schema installation, historical attribution, legacy public exposure, authority/accounting decisions, complete application migration-chain issues, Oracle MySQL verification and whole-suite isolation remain outstanding. B19/B24 catalog work and operational retention/provisioning are not silently resolved.
