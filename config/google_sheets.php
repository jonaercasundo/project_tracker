<?php

return [
    'billing' => [
        'enabled' => (bool) env('PROJECT_BILLING_SHEETS_ENABLED', true),
        'spreadsheet_id' => env('PROJECT_BILLING_SHEETS_SPREADSHEET_ID', '1sOESkXSA8CbgkJMA_Y-sYzkngEimsrnn3qQwBvZHtUw'),
        'sheet_tab' => env('PROJECT_BILLING_SHEETS_TAB', 'Copy of SME LOT 2,9,11'),
        'service_account_json' => env('GOOGLE_SHEETS_SERVICE_ACCOUNT_JSON'),
        'business_timezone' => env('PROJECT_BILLING_SHEETS_TIMEZONE', env('APP_TIMEZONE', 'Asia/Manila')),
        'header_scan_rows' => 20,
        'read_range' => 'A:CI',
        'batch_read_range' => 'A:CT',
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'oauth_token_url' => 'https://oauth2.googleapis.com/token',
        'api_base_url' => 'https://sheets.googleapis.com/v4/spreadsheets',
    ],
];
