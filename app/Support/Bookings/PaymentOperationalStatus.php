<?php

namespace App\Support\Bookings;

class PaymentOperationalStatus
{
    /**
     * @return array{code: string, label: string, meaning: string}
     */
    public static function fromValue(?string $paymentStatus): array
    {
        $normalized = strtolower(trim((string) $paymentStatus));
        $code = match ($normalized) {
            'submitted' => 'proof_submitted',
            'unpaid', 'proof_submitted', 'partial', 'paid', 'rejected', 'refunded', 'partial_refund' => $normalized,
            default => 'unpaid',
        };

        return [
            'code' => $code,
            'label' => self::label($code),
            'meaning' => self::meaning($code),
        ];
    }

    public static function label(string $code): string
    {
        return str_replace('_', ' ', $code);
    }

    public static function meaning(string $code): string
    {
        return match ($code) {
            'unpaid' => 'No verified payment.',
            'proof_submitted' => 'Customer/agent submitted proof, admin must verify.',
            'partial' => 'Some payment verified, balance remains.',
            'paid' => 'Full amount verified.',
            'rejected' => 'Payment proof rejected.',
            'partial_refund' => 'Some amount refunded.',
            'refunded' => 'Fully refunded.',
            default => 'Payment status unavailable.',
        };
    }
}
