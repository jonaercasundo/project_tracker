# Project billing Google Sheets sync setup

The app queues a date reconciliation after a delivery enters **For Billing**. It never sends an API request during the billing transaction. The configured worker reads the requested tab, identifies the lot-specific BIR DR and Core Package Code headers, and writes the billing transition date only when there is exactly one matching row and its destination cell is blank.

## Google Cloud and sharing

1. In the Google Cloud project that owns the integration, enable the Google Sheets API.
2. Create a service account and a JSON key. Store the JSON file in the deployment secret store or a protected mounted file; do not commit it.
3. Share the target spreadsheet with the service account `client_email` as an **Editor**. The sheet is private unless the owner grants access.
4. The service requests the `https://www.googleapis.com/auth/spreadsheets` scope. This is the Sheets API read/write scope required to check existing cell values and conditionally write the date.

The target spreadsheet ID supplied for this feature is `1sOESkXSA8CbgkJMA_Y-sYzkngEimsrnn3qQwBvZHtUw`; the exact tab name is `Copy of SME LOT 2,9,11`. The ID is configuration, not a credential. The app does not create a substitute spreadsheet.

## Application configuration

Before an authorized MMC `user` saves settings in **Billing → Google Sheets Sync**, the sync continues to use the environment configuration below. Saving the settings page creates an encrypted database override for spreadsheet ID, worksheet name, enablement, and optional credentials; a blank credential field preserves the saved credential or uses the environment credential fallback.

The persisted service-account JSON is encrypted with Laravel's `APP_KEY`. Keep the same application key across web/queue servers and retain it in secure backups; changing it makes saved credentials unreadable. Credential values are never returned to the browser or written to audit details.

Environment fallback values are supplied through the deployment secret/configuration system:

```dotenv
PROJECT_BILLING_SHEETS_ENABLED=true
PROJECT_BILLING_SHEETS_SPREADSHEET_ID=1sOESkXSA8CbgkJMA_Y-sYzkngEimsrnn3qQwBvZHtUw
PROJECT_BILLING_SHEETS_TAB="Copy of SME LOT 2,9,11"
PROJECT_BILLING_SHEETS_TIMEZONE=Asia/Manila
GOOGLE_SHEETS_SERVICE_ACCOUNT_JSON=/run/secrets/google-sheets-service-account.json
QUEUE_CONNECTION=database
```

Set the timezone to the Project Tracker business timezone if it differs from `Asia/Manila`. The stored value comes from the recorded `submitted_for_billing_at` transition timestamp, not the time the job runs.

Apply the `billing_sheet_syncs` and `google_sheets_settings` migrations, then run a queue worker for the default queue, for example:

```shell
php artisan queue:work database --queue=default --tries=5
```

The Billing record detail page displays configuration diagnostics and per-package reconciliation outcomes. A reviewer can retry failed, unconfigured, or unresolved records after fixing the cause. Retries re-check the sheet and never replace a non-empty destination cell.

## Sheet matching and destinations

The sync locates the header row in the first 20 rows and requires unique lot-specific BIR DR and Core Package Code columns. Both values are compared after trimming and case-folding; punctuation and leading zeroes are retained. The destination columns are BF for Lot 2, BU for Lot 9, and CI for Lot 11. Missing or duplicate matches and occupied cells are recorded for review without a write.
