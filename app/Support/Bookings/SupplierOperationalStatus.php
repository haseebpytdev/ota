<?php

namespace App\Support\Bookings;

class SupplierOperationalStatus
{
    /**
     * @return array{code: string, label: string, meaning: string}
     */
    public static function fromValues(?string $supplierStatus, ?string $provider, bool $hasPnr = false): array
    {
        $raw = strtolower(trim((string) $supplierStatus));
        $provider = strtolower(trim((string) $provider));
        $providerSupportsAutomation = in_array($provider, ['duffel', 'sabre', 'pia', 'airline_direct'], true);

        $code = match (true) {
            $raw === 'failed' => 'failed',
            in_array($raw, ['manual_review', 'review_required'], true) => 'manual_review',
            $raw === 'pending' => 'pending',
            in_array($raw, ['created', 'booked', 'pending_ticketing', 'ticketed'], true) || $hasPnr => 'booked',
            $raw === 'ready' => 'ready',
            ! $providerSupportsAutomation && in_array($raw, ['not_started', '', 'none', 'unknown'], true) => 'not_supported',
            in_array($raw, ['payment_pending', 'offer_validated'], true) => 'ready',
            default => 'not_started',
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
            'not_started' => 'No supplier booking attempted.',
            'ready' => 'Payment/offer state allows supplier booking.',
            'pending' => 'Supplier request in progress.',
            'booked' => 'Supplier reference/PNR stored.',
            'failed' => 'Supplier booking failed.',
            'manual_review' => 'Requires staff review.',
            'not_supported' => 'Provider cannot perform this action automatically.',
            default => 'Supplier status unavailable.',
        };
    }
}
