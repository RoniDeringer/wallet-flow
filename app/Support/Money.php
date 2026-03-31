<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class Money
{
    public static function parseAmountToCents(mixed $raw, string $field = 'amount'): int
    {
        $value = trim((string) $raw);
        $value = str_replace(['R$', ' '], '', $value);
        $value = str_replace(',', '.', $value);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages([
                $field => ['Informe um valor válido (ex: 10.00).'],
            ]);
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) substr($fraction, 0, 2);
    }
}

