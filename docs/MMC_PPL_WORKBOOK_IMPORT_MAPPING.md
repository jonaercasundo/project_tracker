# MMC PPL workbook import mapping

The import stages an `.xlsx` workbook for review, then creates new MMC `ProjectFinancialPlan` records only after an authorized Finance user confirms. It never creates or updates `ppl_forms`. Source cells, formulas, saved formula results, warnings, and calculation comparisons are retained in the import audit rows and financial-plan version snapshot.

## Source worksheets

| Workbook source | Required fields | Application destination | Rule |
| --- | --- | --- | --- |
| `1. FORM` | A project code, B lot, C title, D project identifier, M ABC, S:AO financial rates/amount inputs, AX revenue year | New financial plan, `revenue_year`, and version inputs | ABC and all input rates are taken from the row. Blank/non-numeric required inputs block that row. AX is persisted to the plan when the review-only revenue-year migration is applied and is also retained in the version provenance. |
| `2. FACTORY COST` | A project code, B lot, D item number, E description, G factory, I quantity, J USD unit cost, K forex, L PHP amount | Financial-plan `factory_cost` input and version factory-cost detail snapshots | Rows must match the exact project-code/lot key. L is consumed as the saved workbook value; the importer does not recalculate workbook formulas. It checks `quantity × USD unit cost × forex` for review and warns on zero forex or differences. These records are not MI product factory-cost records and do not become additive cost lines. |
| `3. MUNICIPAL TAX & INSURANCE` | B6:B11, B15:B17, B21:B24, B28:B30, B34:B36 | Financial-plan version assumption snapshot | Each listed rate/fee is copied from its workbook coordinate. Missing/non-numeric values block the row. |
| `PPL` | A project code, B lot, G contract amount, H:I, K:AM calculation outputs | Contract input and reconciliation-only outputs | G is accepted only as a static numeric source input. Formula-driven results use cached values for comparison only. Result differences greater than ₱0.01 are warnings. Duplicate PPL keys block import until the source is corrected. |

## Source linking

- Existing `PplForm` records are matched by exact project code plus lot. A unique match is linked read-only; zero or multiple matches are shown in preview. No PPL Form row is inserted, updated, converted, or deleted.
- Existing projects are matched by exact project code when that column is available.
- Bidding/project information is matched by exact workbook project identifier to `project_information.project_id_no`. A lot is linked only when an exact lot number exists under that matched bidding record.
- Optional `contract_amount` and `lot_no` bidding columns are inspected before querying. A missing source column is treated as unavailable; it is never selected unconditionally. Existing bidding contract/ABC values are compared only when available and lot-specific where required.
- Existing financial plans and import mappings are checked before confirmation. Existing plans are not overwritten; existing source mappings cause duplicate rows to be skipped.

## Calculation and history

The calculator runs with trusted `MMC` context. It uses the workbook’s MMC tax-lawyer base (ABC). The preview compares workbook cached values for net sales, factory cost, each PPL cost column, total costs, gross profit/margin, OPEX, net profit, income tax, incentive, PPL/margin, importation VAT, and working capital. A successful confirmation creates a new plan and version 1; later revisions continue through the existing versioned financial-plan workflow. The imported source workbook and reconciliation are preserved in version input/assumption snapshots.

## Import audit and safety

Each upload is stored on the private local disk and fingerprinted by SHA-256. Batch records preserve uploader, filename, status, summary, and timestamps. Row records preserve source location, exact source cells (including formula text and cached result), normalized inputs, exact matching identifiers, errors, warnings, and plan link. A unique company/source fingerprint prevents duplicate project/lot imports. Confirmation runs in a database transaction; a failed row write rolls back plan, version, and source mapping writes. Upload limits and XLSX archive checks are enforced before workbook parsing.

## Database preparation

Migration `2026_10_22_015100_create_mmc_ppl_import_tables.php` adds the three import-audit tables. Migration `2026_10_22_020051_add_revenue_year_to_project_financial_plans_table.php` adds a nullable plan revenue year and a company/year index for MMC monitoring. Neither migration has been executed. Review current MariaDB foreign-key integer types, JSON support, index-name limits, and the existing financial-plan table before applying them. The workflow does not alter existing PPL Forms or add project/bidding columns.
