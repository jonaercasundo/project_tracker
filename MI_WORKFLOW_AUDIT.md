# MI financial workflow audit

Audit date: 6 October 2026. Baseline: clean working tree; Laravel 11.54.0, PHP 8.3 application, Pest 3.7.2, Spatie Permission 6.25.0, Laravel DomPDF 3.1.2. This report records the implementation **before fixes**. The implementation appendix records verified changes separately.

Evidence: registered `route:list`, controllers, middleware, models, migrations, Blade forms and their JavaScript, navigation, PDF templates, role seeder, and existing test setup. Boost tools are not exposed in this session. Read-only `db:table` checks report that `budget_requests`, `liquidation_items`, and `mi_liquidations` do not exist in the configured database. Live data, deployed schema, historical attribution, actual grants, and production concurrency therefore remain unverified. No production writes, migration execution, deployment, or data backfill is authorized or performed. UI findings are source inspection, not browser verification.

Additional read-only evidence: the configured MySQL `users.user_id` is `bigint(20) unsigned`. The four budget/travel create migrations, ordinary create migration, and requested_by conversion are Pending in `migrate:status`; `mi_product_images` is also absent. The isolated test runner executes migrations only against SQLite `:memory:`. The financial application tables are not available for end-to-end use in the configured database until schema installation is separately performed.

## A. Current Process

There are two separate liquidation systems:

* Ordinary Liquidation uses `MI_Liquidation` / `mi_liquidations` and `MI_LiquidationItem`. It records petty cash in VND and derives USD using an exchange rate. It has a company and preparer, defaults to `Pending`, and has no registered approval/rejection action. Accounting lists and views only this system.
* Travel Liquidation uses `Liquidation` / `liquidations` and `LiquidationItem`. A unique budget-request foreign key enforces one liquidation per budget. It compares cash + credit card + travel agent actuals with the requested budget total. It has no company column.
* Budget Request uses `BudgetRequest` and `BudgetRequestItem`; it also has no company column. Creation immediately submits the request. There is no persisted budget draft or separate submit endpoint.
* MI Dashboard is a product dashboard, not a financial dashboard. Designer, taxonomy, materials and product images are MI-prefixed shared tables without company/employee ownership columns. Their routes restrict the caller to MI membership and `user`; the code grants that role shared CRUD rights. Whether finer designer permissions are required needs confirmation.

Actual transition map at baseline:

| Current status | Who can reach the action | Action | Next status / effect |
| --- | --- | --- | --- |
| none | MI `user` | Budget store | `budget_requested` |
| `budget_requested` | Any MI `user`, including requester | Budget approve | `approved`; actor/time |
| `approved` | Any MI `user` | Accounting note | stays `approved`; overwrites note actor/time |
| `approved` | Any MI `user`, even without note | Release | `released`; actor/time |
| `released` | Owner check uses authenticated key | Mark received | `in_progress`; receipt timestamp |
| `in_progress` budget | Any MI `user` | Travel store | creates `draft`, immediately `submitted`; budget remains `in_progress` |
| `submitted` travel | Any MI `user` | Accounting note | `noted` |
| `noted` travel | Any MI `user`, including liquidator | Approve | travel `closed`; budget `liquidated` |
| none | MI `user` | Ordinary store | `Pending` |

Budget enum `closed` and `cancelled`, and travel enum `approved`, have no reachable transitions. Travel `draft` is transient in store; edit/delete require a draft, but update also accepts submitted records. No rejection, return-for-correction, cancellation, settlement of refund/reimbursement, or final budget closure action exists.

## B. Critical Bugs

Paths below are relative to the repository. Reproduction scenarios are source-derived unless the appendix explicitly records a test.

| ID / severity / priority | File and method | Problem and reproduction | Recommended smallest fix |
| --- | --- | --- | --- |
| B01 Critical P0 | `app/Http/Controllers/BudgetRequestController.php`: approve, noteByAccounting, release; `TravelLiquidationController.php`: noteByAccounting, approve; `routes/web.php` | An ordinary employee can POST their own approval, Accounting note and release, then review/close their own travel report. Only statuses are checked. Accounting-only users receive 403 from `role:user`. | Move named Accounting actions to Accounting middleware; authorize every record/action. Deny unassigned approval authority until the business confirms it. |
| B02 Critical P0 | BudgetRequestController: show/edit/update/destroy; TravelLiquidationController: create/store/show/edit/update/destroy/downloadPdf; LiquidationController: index/show/edit/update/destroy/downloadPdf | An MI user changes a URL ID to read, edit or delete another employee's records or download their PDF. Travel store accepts another employee's budget. Ordinary list/statistics expose everyone. | Owner checks on employee endpoints; company checks for ordinary records; constrain listings and linked parents/items. |
| B03 High P0 | Accounting_DashboardController: dashboard; Accounting_LiquidationController: downloadPdf | Dashboard aggregates and recent rows include other companies; registered PDF method lacks the company guard present in the unused `liquidation_pdf` method. | Scope dashboard and item breakdown by selected company; guard the registered PDF endpoint. |
| B04 High P0 | BudgetRequest / Liquidation models, migrations, all financial controllers | Budget/travel lack persisted company identity. Middleware verifies caller context, never record company. A dual-company owner cannot establish which company owns a historical request. | NEEDS BUSINESS CONFIRMATION: are these tables exclusively MI? If shared, add nullable company FK, stamp new records, deny unassigned records and backfill only verified attribution; do not infer company from current employee membership. |
| B05 High P0 | BudgetRequestController: store/index; TravelLiquidationController: store/index; BudgetRequest/Liquidation: transition methods | User PK is `user_id`; code uses `$user->id`, so creation writes null actor/owner foreign keys and employee listings filter against null. | Use `getKey()` / `Auth::id()` throughout these paths. |
| B06 High P0 | LiquidationController: edit/update/destroy | Approved/rejected reports can be edited or deleted. Destroy permanently deletes children and receipt files before soft-deleting header. A restored header loses financial evidence. | Restrict mutation to Pending; preserve items/files on soft deletion. Cancellation after submission is a separate business decision. |
| B07 High P0 | BudgetRequestController: workflow/update/destroy; TravelLiquidationController: store/update/review/approve | Status checked before transaction, no row lock. A stale tab can edit after approval, overwrite review stamps or race release. Final travel approval updates two records without a transaction. Duplicate travel insert fails as a database exception despite the unique FK. | Lock parent row before rechecking status; serialize edits and transitions; require an Accounting note before release; reject duplicates with 422; close parent/child atomically. Test sequential replay; MySQL race tests remain separate. |
| B08 High P0 | TravelLiquidationController: store/update | `exists` rules accept a budget item from another request or item ID from another liquidation. `updateOrCreate` can attempt to recreate a foreign existing PK, causing DB failures. | Scope exists rules to the selected parent, reject duplicate supplied IDs, update only validated related items. |
| B09 High P0 | financial controllers; BudgetRequestItem / LiquidationItem / Liquidation: calculations | `numeric|min:0` accepts scientific notation, excess decimal places and values beyond DECIMAL(12,2); line and aggregate totals can overflow. Floats derive totals/variance. Hidden totals are not directly trusted (good), but precision/capacity are not enforced. | Validate decimal format/capacity; use existing Brick Math exact decimals, bound aggregate totals before commit; ignore browser totals. Preserve zero policy until confirmed. |
| B10 High P1 | BudgetRequestController: store/update/destroy | Redirects target nonexistent `mi_app.budget_requests.*` names. A successful write is followed by a route exception, inviting resubmission. | Use existing `budget_requests.*` names. |
| B11 High P1 | TravelLiquidationController: store/update/destroy; `resources/views/mi_app/liquidations/*`; budget show | Travel form posts to ordinary store; redirects, links and PDFs point to ordinary model IDs; `liquidation.note/approve` do not exist. Travel detail can fail while rendering. | Change only travel references to `travel_liquidation.*`; leave ordinary references intact. |
| B12 High P1 | TravelLiquidationController: store/update; migration `2026_09_18_091209_create_liquidations_items_table.php`; travel create view | Form/controller use `Yes,No,N/A`, but enum allows `yes,no,n_a`. MySQL strict writes fail. | Canonical lower-case values; normalize legacy form submissions at boundary. Verify MySQL strict enum separately from SQLite. |
| B13 Medium P1 | BudgetRequestController: edit; TravelLiquidationController: edit | Both return nonexistent edit views. Travel store immediately submits, so ordinary records cannot satisfy edit's draft-only check anyway. Budget update ignores expense edits entirely. | NEEDS BUSINESS CONFIRMATION: pending/submitted amendment policy; add minimal metadata-only budget edit and permitted travel item edit if confirmed. Do not silently change amendment semantics. |
| B14 High P0 | LiquidationController: update; ordinary edit view | Existing receipts are keyed by line position; removing/reordering rows attaches the wrong receipt to a different expense. Replaced files are deleted inside a DB transaction; rollback cannot restore them. | Preserve receipts by validated item identity, retain old files until commit, clean newly written files on rollback. Do not delete evidence on rollback. |
| B15 High P0 | LiquidationController: store/update; ordinary detail/edit/accounting views | Receipt images are on public disk and exposed as static URLs, bypassing ownership and company checks. MIME and 10MB limits exist; random names do not authorize downloads. | Store new receipts privately; authenticated item download with parent authorization; verified legacy migration required to remove old public exposure. |
| B16 Medium P1 | LiquidationController: store/update | Catch-all Throwable catches ValidationException, replacing field errors / JSON 422 with generic redirects; debug response leaks exception details. | Rethrow validation exceptions; report internal errors without exposing them. |
| B17 Medium P2 | BudgetRequest: generateControlId | Last-row sequence generation races; parsing only last 4 digits wraps after 9999. Unique index prevents duplication but causes failed saves. | Confirm reference format; use a DB sequence/locked allocator or collision-resistant identifier with bounded retries. Preserve numbering pending confirmation. |
| B18 Medium P1 | `resources/views/accounting/dashboard.blade.php` | Dashboard links navigate to employee-only ordinary routes and Create, causing 403 for Accounting. | Use registered `accounting.mi.liquidation.*` read routes; don't grant employee write rights merely to make buttons work. |
| B19 Medium P2 | MIAppController: setting_store/store/update/destroy/taxonomy_destroy | Invalid entity default returns with open transaction; product create lacks update's taxonomy ancestry checks; negative dimensions/cost accepted on create; images deleted before commit; destroy removes only legacy product_file; taxonomy cascades may erase descendants. | Validate entity first; apply consistent ancestry/amount rules; defer file deletion; confirm shared catalog deletion rights. Product financial-workflow ownership rules must not be guessed. |
| B20 Medium P2 | MI_LiquidationItem: requestedBy; LiquidationController/Accounting controllers eager loading | Migration changed requested_by to a free-text name, but model still treats it as user FK. Unnecessary relation queries; misleading comments claim server-owned identity. | Treat requested_by as free text consistently, remove stale relation use after auditing consumers; preparer remains authenticated actor. |
| B21 High P1 | `database/migrations/2026_09_02_093217_create_liquidations_table.php`, `2026_09_18_091109_create_budget_requests_table.php`, `2026_09_18_091159_create_liquidations_table.php`: up | User foreign keys are signed INT while both the users creation migration and inspected MySQL schema use BIGINT UNSIGNED. MySQL cannot create these foreign keys. The conversion migration also omits explicit nullable retention. | Correct pending create definitions to unsignedBigInteger, retain nullable on column changes. Do not run migrations during this audit; environments with an older applied schema need a separately planned forward reconciliation. |
| B22 Medium P1 | `app/Http/Controllers/CompanyController.php`: redirectForCompany | Selecting/switching MI as Accounting sends the user to maintenance; login already supports Accounting dashboard. | Add the existing Accounting destination to company redirects, preserving existing multi-role precedence. |
| B23 Medium P2 | `resources/views/mi_app/liquidations/pdf.blade.php`, ordinary liquidation PDF templates | Travel PDF includes employee, department, reference, status, budget/actual/variance and receipt flags, but lacks company identity, travel/submission dates, item payment-method breakdown, notes and approval actor/timestamp details. Ordinary PDF includes company, preparer, report/item references, dates, exchange rate, PCF, expenses, USD/balance and receipt pages, but no Accounting approval history exists to print. Ordinary VND is displayed with zero decimals while inputs permit cents; exchange rate prints two decimals although storage has four. | Agree currency/presentation rules and add existing sign-off fields to travel PDF; do not print fabricated release/settlement/approval data. Visual multi-page QA remains required for a PDF layout change. |
| B24 High P1 | `MI_Product_Image`, MIAppController: store/update; `database/migrations` | No committed migration creates mi_product_images, and the configured database lacks that table. Creating a product with media fails. The product upgrade migration drops columns such as item_no/sub_sub_category that its committed initial create migration does not define, so a clean schema installation also needs reconciliation. | Verify deployed catalog schema/history; add a reviewed missing-table migration and reconcile fresh-install upgrade paths. Do not reconstruct existing product data or execute destructive product migrations in this audit. |

## C. Route & Authorization Problems

Registered routes use correct resource HTTP verbs and valid public controller methods. Static create/settings/dashboard/designer paths precede `/{product}`; multi-segment PDF/edit paths do not collide with show. No duplicate names were found in the MI/Accounting/Budget/Travel groups. The old **registration** collision between `liquidation.*` and `travel_liquidation.*` is eliminated; stale references B11 still break navigation. Whole-project route output also contains duplicated `items.index/store/create/edit/update/destroy` names outside this audit scope; those need a separate route audit.

`/liquidation/{id}/edit` works at baseline because edit manually receives `$id` and finds the model. It is inconsistent, not itself a binding failure. Standardizing parameter and typed signature together is safe. Budget typed `$budgetRequest` and travel typed `$liquidation` match route parameters. Models use their correct record PKs; User PK usage is B05. Taxonomy `$id` versus `{product}` is positional/manual lookup, not implicit model binding.

CheckCompany verifies active MI membership and exact selected-company session. RoleMiddleware uses Spatie roles, not `users.role`. RoleSeeder seeds `Administrator`, `Manager`, `User`, while routes check lowercase `user`, `accounting` and controller checks `admin`. Actual production grants are unverified; do not assume the differently named roles are interchangeable. `bootstrap/app.php` actually configures the running app and aliases; the supplied guidance about a Laravel 10-style bootstrap does not match the inspected runtime.

Accounting review routes belong under Accounting middleware. Approval authority, delegation, role naming and whether dual-role staff may review their own records are NEEDS BUSINESS CONFIRMATION. Default deny is safer than assigning Manager/admin from a suggestive name. Shared view access must remain constrained by record company; absent company attribution cannot be solved by caller middleware alone.

## D. Workflow Problems

No separate submit action, approval rejection, correction return, cancellation, verified release amount/payment evidence, receipt actor beyond owner/time, or settlement record exists. Budget note doesn't advance status, and release doesn't require it. Travel can close with any variance; `isBalanced` tolerates +/-0.01, treating an exact one-cent discrepancy as balanced. Whether to allow this tolerance, nonzero settlement, zero-budget requests, repeated budget-item allocations and submitted edits is NEEDS BUSINESS CONFIRMATION. Multiple travel records are disallowed by unique FK. Do not remove the constraint to implement revisions.

Accounting dashboard has no budget queue, travel queue, released-without-liquidation aging, employee department/date/reference filters for budget processing, or reviewed/closed travel counts. Ordinary Approved/Rejected counts may reflect manually imported statuses; no route sets them. Adding approval actions requires a confirmed ordinary workflow and audit fields.

## E. Financial/Data Integrity Problems

Budget amounts and travel actuals use DECIMAL(12,2), maximum 9,999,999,999.99. Ordinary VND/PCF use DECIMAL(14,2); exchange rate DECIMAL(12,4). Models sum ordinary VND and calculate USD/balance dynamically; travel caches actual_total and variance. Browser previews use floating-point and `toFixed`; they must remain previews. PDFs use model/header and row values; no separate server formula should diverge.

Travel variance = requested budget total - actual expenses. There is no independently recorded released amount or currency, return amount, reimbursement amount/payment, or FX snapshot for travel. Consequently requested total cannot prove cash actually released. Cash, card and agent figures should not automatically be treated as employee cash to return. NEEDS BUSINESS CONFIRMATION: currency and settlement basis. Positive variance indicates unused budget, negative variance overrun; neither proves a refund or reimbursement payment.

Budget deletion cascades to budget items and travel liquidation; travel deletion cascades to actual items; deleting budget items nulls travel item linkage. Controller status checks mitigate but do not eliminate races. User deletion is restricted for employee/liquidator, while nullable sign-off actors are set null, losing historical identity. Ordinary company/preparer also set null on deletion. Prefer immutable actor snapshots and protected history before introducing new cancellation/archival semantics. No existing records should be deleted or automatically reassigned.

## F. Security Problems

Confirmed source-level gaps: B01-B04, B06-B09, B14-B16. No financial policy exists at baseline. Input is largely validated and explicit attribute arrays prevent forged status/header totals from being assigned; this does not authorize the record. Search values are query-bound; no demonstrated SQL injection. State-changing forms inspected contain @csrf and DELETE/PUT spoofing; web routes have framework CSRF middleware. Do not disable it. Blade escapes visible financial fields. Designer dashboard legend uses text nodes; public product cart escapes names/SKUs with pdEscapeHtml, and inspected edit select renderers escape labels. Many innerHTML uses contain static markup or clear previews; no confirmed XSS was found in those paths. Browser regression coverage remains recommended for dynamic rendering. PDF confidentiality depends on controller authorization; public receipt URLs bypass it.

## G. UI/UX Improvements

Use policy-backed action visibility, show current responsible role and next action, and distinguish note/release/receipt/closure. Include stamped actors/times and clear return/reimbursement labels after settlement policy is confirmed. Show field validation errors and preserve submitted rows; travel create currently lacks errors/old values. Add confirmation for destructive/cancellation actions, button submission feedback, empty states, responsive table scrolling and single-column mobile forms. Do not infer a cancelled timeline via array_search(false). Accounting navigation needs B18; sidebar Travel Create has no required budget query and currently yields a 404. Offer eligible owned received budgets rather than an invalid link.

## H. Recommended Architecture

Keep the two liquidation models distinct. Add focused policies, preserving route names and existing controllers. Use transactional row-locked workflow methods, exact decimal operations with the already installed Brick Math, and parent-scoped validation. Extract a workflow service only when shared domain operations warrant it; no redesign or new dependency is necessary.

Confirmed responsibilities: employee creates/receives/liquidates own records; Accounting notes and releases. Approver roles and self-review rules remain unassigned until confirmed. Persist company identity before supporting shared financial tables. A future append-only financial activity table should capture event, record/company, actor snapshot, previous/new status, time and note inside the same transaction. Actor/timestamp columns alone are mutable snapshots, not immutable history. No workflow notifications/events were found; User having Notifiable does not implement notifications.

Target process, subject to confirmation: request -> designated approver -> Accounting note -> recorded release -> owner receipt -> expenses -> Accounting balance review -> authorized final approval + documented settlement -> closure. Keep status vocabulary unless a migration and data transition are explicitly agreed.

## I. Test Matrix

| Scenario | Required contract |
| --- | --- |
| Create budget | real user_id, server totals, valid redirect, forged status/totals ignored |
| Own/other budget reads, update/delete | owner allowed; others denied with no mutation |
| Approval | employee self/other approval denied; designated approver success NEEDS BUSINESS CONFIRMATION |
| Accounting review | accountant reaches endpoint; employee denied; wrong status rejected |
| Release | note + approved status required; repeat fails without overwriting actor/time |
| Receipt | owner only, released only; repeat rejected |
| Travel creation | owned received budget; invalid IDs, unreleased budgets, foreign item links rejected |
| Duplicate travel | only one record; friendly 422; MySQL concurrent submissions tested separately |
| Totals | cents exact, browser totals ignored, under/equal/over budget; per-line and aggregate bounds |
| Travel mutation | owned editable states only; foreign/duplicate item IDs rejected; omitted row semantics confirmed |
| Cross-company | ordinary read/edit/delete/PDF/list/dashboard blocked; budget historical company identity unresolved |
| PDF | own/company-authorized succeeds; other employee/company denied |
| Deletion | ordinary soft delete preserves items/files; signed-off edits/delete blocked; budget cascade guarded |
| Uploads | unsupported MIME, >10MB, private authorized download, rollback cleanup and stable receipt identity |
| Validation | missing fields, invalid dates, negatives, zero policy, large/exponent/malformed values |
| Rendering | correct travel routes, no nonexistent action names, policy-backed buttons, accounting navigation |
| Concurrency | locks + unique constraint on MySQL; SQLite tests cannot prove MySQL locking |

Existing phpunit.xml leaves DB_CONNECTION/DB_DATABASE commented out; default RefreshDatabase can therefore operate on configured application DB. New audit tests must explicitly configure SQLite :memory: and use only relevant migrations, following the existing isolated BiddingTestCase approach. Full-suite execution requires an isolated test database, not production configuration.

## J. Implementation Priority

P0: authorization/ownership, ordinary company isolation/PDF, correct user keys, transition/edit locks, duplicate/link integrity, decimal bounds, preserved deletion evidence and receipt safety. P1: correct redirects/form links/enums, Accounting route access/navigation, missing edit forms and queues once amendment/business rules are confirmed. P2: immutable history, reference allocator, query efficiency, consistent validation and focused services. P3: responsive polish, status timelines and notifications.

Outstanding business decisions: approver roles; budget/travel company attribution; post-submission amendment/deletion; self-review/separation of duties; released amount/currency/settlement basis; zero amounts and balance tolerance; ordinary approval/cancellation; numbering/revisions. These cannot be safely invented from current code.

## Implementation and verification appendix

### Verified safe changes

| Finding | Changed files | Result / regression coverage |
| --- | --- | --- |
| B01, B02, B05 | financial controllers; three new policies; AppServiceProvider; financial models; routes/web.php; budget/travel detail views | Owner and role checks enforced server-side; User getKey used for ownership/actor stamps; Accounting note/release/review moved to role:accounting; ordinary employees cannot approve or review/release. Approval policies deliberately deny everyone until authority is confirmed. Named routes/URIs retained. Tests cover employee and foreign-owner denials and Accounting-only success paths. |
| B03 | Accounting_DashboardController, Accounting_LiquidationController | Dashboard/report-item aggregates are scoped to selected company; registered downloadPdf is authorized; dashboard eager-loads items. Cross-company Accounting PDF and dashboard tests pass. |
| B06, B07 | financial controllers and policies | Status/authorization rechecked after row lock inside transactions. Budget release requires note actor/time; duplicate note/release/receipt rejected without replacing stamps. Linked-budget deletion rejected; stamped budget/travel records cannot be deleted merely by resetting status. Ordinary soft deletion retains child rows and receipts; employee list/detail pages hide unauthorized edit/delete controls through policies. Tests cover state restrictions, sequential repeats, action visibility and evidence preservation. SQLite does not prove concurrent MySQL locking. |
| B08 | TravelLiquidationController, LiquidationController | Budget-item links and update item identities constrained to the parent; duplicate update IDs rejected. Tests cover foreign linked IDs and foreign ordinary/travel item updates. Omitted travel rows retain the baseline behavior (not deleted). |
| B09 | MiFinancialAmount; BudgetRequest, BudgetRequestItem, Liquidation, LiquidationItem, MI_Liquidation; validation in financial controllers | Existing Brick Math performs exact line/header sums and travel variance; browser totals ignored. Plain decimal format, scale, per-field capacity and aggregate capacity checked. Ordinary VND sum is exact before display conversion. Zero-budget policy and one-cent balance tolerance preserved. Tests cover cents, forged totals, positive/zero/negative variance and aggregate rollback. USD and UI/chart formatting still use floats. |
| B10-B12 | BudgetRequestController, TravelLiquidationController, budget show, travel views | Existing route names restored in redirects; travel-only references corrected throughout inspected project; ordinary references retained. Lowercase receipt enum values emitted; legacy Yes/No/N/A normalized. Successful create/show/list/redirect tests pass. |
| B13 | new budget_requests/edit view; TravelLiquidationController; travel create view | Pending budget metadata edit now renders; expense edit semantics are unchanged. Travel draft edit reuses the form with actual item IDs/values. Store still immediately submits; edit GET remains draft-only while update accepts draft/submitted, pending business confirmation. Standalone travel Create redirects to budget selection instead of failing for missing query. Rendering and update regressions pass. |
| B14 | LiquidationController; ordinary edit view | Pending item edits use validated stable IDs; surviving receipts/references remain with their original rows, rather than their old position. Original files are retained on replacement/removal; only newly uploaded files are cleaned after rollback. Identity-preservation and forced-failure rollback tests pass. File retention/archival policy remains a business decision. |
| B15 | LiquidationController; routes/web.php; ordinary employee/Accounting detail and PDF templates | New receipts stored at private/liquidations/receipts on local disk. Authorized, directory-constrained receipt endpoint serves employee/Accounting pages; PDFs select private/legacy disks. Own retrieval succeeds; foreign employee and traversal attempts fail. Existing public files were neither moved nor deleted and remain exposed through old static URLs. A verified legacy migration is still necessary. |
| B16 | LiquidationController | ValidationException propagates to normal field errors/JSON 422; new files cleaned on rollback; save errors logged without custom debug-detail flash. MIME and oversized upload tests pass. |
| B18, B22 | Accounting dashboard/detail Blade, CompanyController | Links use Accounting read routes; unsupported employee Create shortcuts now point to the Accounting list with matching labels. Pending filter uses actual Pending value. Company selection/switch uses the same Accounting destination as login. Redirect tests pass. |
| B20 (partial) | LiquidationController, Accounting_LiquidationController | Stale requestedBy eager-loading removed. The legacy relationship remains for untouched consumers; no blanket model removal performed. |
| B21 | three pending financial create migrations; requested_by conversion migration | User FK definitions match inspected BIGINT UNSIGNED user_id; nullable explicitly retained. No application-database migration executed. Source correction only affects future installs; any independently deployed older schema needs a forward reconciliation. Tests execute these corrected definitions in isolated SQLite; MySQL DDL still needs verification in an isolated MySQL environment. |

### Verification

`php artisan test --compact tests/Feature/MIWorkflowTest.php`: **75 passed, 303 assertions**. The test case selects SQLite `:memory:` before migrations, guards that connection, includes only relevant schema files, and resets migration state after each test. Existing default test binding explicitly excludes this isolated case. Actual DomPDF output was exercised for authorized employee ordinary/travel PDFs and Accounting ordinary PDFs, including application/pdf and PDF magic bytes. No browser screenshots or visual PDF page review were performed; no PDF layout was redesigned.

Pint `--dirty --format agent` completed successfully; `git diff --check` passed. The complete suite was not run because its default RefreshDatabase binding can use the configured application database; run `php artisan test --compact` only after isolating the full suite's test database. Targeted tests do not validate MySQL enum enforcement, foreign-key DDL or simultaneous row locks.

### Remaining decisions and work

* **NEEDS BUSINESS CONFIRMATION:** budget approver and final travel approver roles. Both approval policies return false; no permission/role was created or granted. The approval routes still have their original role:user boundary, so they must be moved to confirmed approver middleware together with policy grants. No valid new end-to-end approval/closure can occur until then.
* **NEEDS BUSINESS CONFIRMATION:** whether budget/travel tables are exclusively MI. Accounting actions constrain the linked employee to active MI membership, but membership is not record company attribution. True company isolation for those tables remains unresolved for dual-company users/historical data. Employee views remain owner-only; no new Accounting budget/travel listing or broad read access was added.
* B15 legacy public files, B17 reference-number concurrency/10000 rollover, B19 catalog validation/file lifecycle, B23 PDF completeness, B24 missing catalog schema, immutable event history, settlement accounting, amendment/rejection/cancellation and the new Accounting queues remain open. These require reviewed schema/workflow decisions or a separate focused correction; no data cleanup/backfill was attempted.
* The original one-cent tolerance is retained even after exact decimal calculations; confirm whether this should classify a one-cent balance as settled. Requested budget total still stands in for the released amount, since no separate release amount/currency exists.
* Role provisioning remains inconsistent: the inspected users.role enum does not contain accounting, while Spatie role assignments control access. Keep role provisioning consistent without assuming that enum and permission names are interchangeable.

This change closes the demonstrated authorization and route defects within the known boundaries. It does **not** certify the financial workflow as fully closed, immutable, historically company-attributed, or safe for production migration without the outstanding decisions and MySQL verification.


## Subsequent implementation phase

The first appendix above is a historical record of the initial fixes. Permission-based approval, persisted company attribution, dedicated releases/settlement, activity history, receipt migration and isolated MySQL verification were subsequently implemented. See [MI_WORKFLOW_IMPLEMENTATION.md](MI_WORKFLOW_IMPLEMENTATION.md) for the current authorization matrix, transition map, migration strategy, verification results and remaining decisions. The baseline A-J findings have not been rewritten.
