<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class MmcPplWorkbookReadFilter implements IReadFilter
{
    /** @param array<string, array<string, true>> $columnsBySheet */
    public function __construct(private array $columnsBySheet) {}

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($row <= 4 && $worksheetName === '3. MUNICIPAL TAX & INSURANCE') {
            return in_array($columnAddress, ['A', 'B', 'C'], true);
        }

        return isset($this->columnsBySheet[$worksheetName][$columnAddress]);
    }
}
