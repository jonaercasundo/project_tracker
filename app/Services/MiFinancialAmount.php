<?php

namespace App\Services;

use Brick\Math\BigDecimal;
use Illuminate\Validation\ValidationException;

class MiFinancialAmount
{
    public const MAX_AMOUNT = '9999999999.99';

    /** @param iterable<int, string|int|float|null> $amounts */
    public static function sum(iterable $amounts, string $field = 'items', string $maximum = self::MAX_AMOUNT): string
    {
        $total = BigDecimal::zero();
        foreach ($amounts as $amount) {
            $total = $total->plus($amount ?? 0);
        }

        if ($total->isGreaterThan($maximum)) {
            throw ValidationException::withMessages([$field => 'The total exceeds the supported amount.']);
        }

        return (string) $total->toScale(2);
    }
}
