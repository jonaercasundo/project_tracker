<?php

return [
    'documents' => [
        'disk' => env('BIDDING_DOCUMENT_DISK', 'bidding'),
        'max_file_size_kb' => (int) env('BIDDING_DOCUMENT_MAX_SIZE_KB', 25600),
        'max_files' => 20,
        'max_folders' => 500,
        'per_page' => 25,
        'max_zip_files' => 100,
        'max_zip_bytes' => 268435456,
        'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png'],
        'mime_types' => [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword', 'application/CDFV2', 'application/x-ole-storage'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls' => ['application/vnd.ms-excel', 'application/CDFV2', 'application/x-ole-storage'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'csv' => ['text/csv', 'text/plain', 'application/csv'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
        ],
    ],
];
