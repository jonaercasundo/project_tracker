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
        'school_id_mappings' => [
            [
                'mapping_id' => 'bacsil-es-134967-to-102205',
                'spreadsheet_id' => env('PROJECT_BILLING_SHEETS_SPREADSHEET_ID', '1sOESkXSA8CbgkJMA_Y-sYzkngEimsrnn3qQwBvZHtUw'),
                'sheet_tab' => env('PROJECT_BILLING_SHEETS_TAB', 'Copy of SME LOT 2,9,11'),
                'tracker_school_id' => '102205',
                'sheet_school_id' => '134967',
                'school_name' => 'Bacsil Elementary School',
                'dr_no' => '35470',
                'lot_number' => '2',
                'package_code' => 'C1 LOT 2 KS1 G1-G3 & C1 LOT 2 KS2 G4-G6',
                'evidence' => 'Tracker school record previously inspected: Bacsil Elementary School, address BRGY.56-A BACSIL NORTH, Region I. Reference worksheet row 2: Bacsil Elementary School, Bacsil, Bangued, Abra; Lot 2 BIR DR 35470. Exact name and locality plus DR match support this explicit cross-system ID mapping.',
                'verified' => true,
            ],
        ],
    ],
];
