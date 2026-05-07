<?php

namespace App\Support\Travel;

class TravelDocumentFormatter
{
    /**
     * Mask a passport or national ID for customer-facing surfaces (e.g. AB123•••).
     */
    public static function maskPassport(?string $number): ?string
    {
        if ($number === null) {
            return null;
        }

        $n = preg_replace('/\s+/u', '', $number) ?? '';
        if ($n === '') {
            return null;
        }

        $len = mb_strlen($n);
        if ($len <= 3) {
            return str_repeat('•', max(0, $len - 1)).mb_substr($n, -1);
        }

        $prefixLen = min(5, $len - 1);
        $prefix = mb_substr($n, 0, $prefixLen);

        return $prefix.'•••';
    }
}
