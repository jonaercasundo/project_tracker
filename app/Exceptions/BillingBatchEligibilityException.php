<?php

namespace App\Exceptions;

use RuntimeException;

class BillingBatchEligibilityException extends RuntimeException
{
    /** @param array<int, array{delivery_id: int, dr_no: string, reason: string}> $rejectedRecords */
    public function __construct(public readonly array $rejectedRecords, string $message, public readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }
}
