<?php

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

pest()->extend(TestCase::class)->in(__FILE__);

it('retains the project foreign key and school coordinates in the configured MariaDB schema', function () {
    if (getenv('SCHOOL_MARIADB_TESTS') !== '1') {
        $this->markTestSkipped('Set SCHOOL_MARIADB_TESTS=1 to inspect the configured MariaDB schema.');
    }

    if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
        $this->markTestSkipped('Requires a MySQL or MariaDB connection.');
    }

    $database = DB::connection()->getDatabaseName();
    $columns = DB::select(
        'SELECT COLUMN_NAME AS column_name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME IN (?, ?, ?)',
        [$database, 'school', 'project_id', 'latitude', 'longitude'],
    );
    $projectForeignKey = DB::selectOne(
        'SELECT REFERENCED_TABLE_NAME AS referenced_table, REFERENCED_COLUMN_NAME AS referenced_column FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
        [$database, 'school', 'project_id'],
    );

    expect(array_column($columns, 'column_name'))->toContain('project_id', 'latitude', 'longitude');
    expect($projectForeignKey?->referenced_table)->toBe('project_information');
    expect($projectForeignKey?->referenced_column)->toBe('id');
});
