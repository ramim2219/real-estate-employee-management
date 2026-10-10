<?php

namespace App\Support;

class Money
{
    // All payroll arithmetic is performed as integer paisa to minimize rounding drift.
    public static function toPaisa(int|float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function toDecimal(int $paisa): string
    {
        return number_format($paisa / 100, 2, '.', '');
    }
}
