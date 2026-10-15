<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class Pricing
{
    /** Prices and fixed order discounts use integer USD cents; discount cannot exceed subtotal. */
    public function total(int $subtotal, int $discount): int
    {
        if ($subtotal < 0 || $discount < 0 || $discount > $subtotal) {
            throw ValidationException::withMessages(['discount_minor' => 'Discount must be between zero and the subtotal.']);
        }

        return $subtotal - $discount;
    }
}
