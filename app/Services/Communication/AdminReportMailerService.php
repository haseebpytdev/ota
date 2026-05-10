<?php

namespace App\Services\Communication;

use App\Enums\OtaNotificationEvent;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\BookingPayment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AdminReportMailerService
{
    public function __construct(
        protected OtaNotificationService $notificationService,
    ) {}

    public function sendDailyReport(Agency $agency, ?CarbonImmutable $day = null): void
    {
        $date = $day ?? CarbonImmutable::now($agency->timezone ?? config('app.timezone'));
        $start = $date->startOfDay();
        $end = $date->endOfDay();

        $payload = [
            'period' => $start->toDateString(),
            'bookings_created' => Booking::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->count(),
            'bookings_by_status' => Booking::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status')->all(),
            'payment_proofs_submitted' => BookingPayment::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->count(),
            'payments_verified' => BookingPayment::query()->where('agency_id', $agency->id)->where('status', 'verified')->whereBetween('updated_at', [$start, $end])->count(),
            'unpaid_balance' => (float) Booking::query()->where('agency_id', $agency->id)->sum('balance_due'),
            'gross_sales' => (float) Booking::query()->where('agency_id', $agency->id)->sum('amount_paid'),
            'unassigned_bookings' => Booking::query()->where('agency_id', $agency->id)->whereNull('assigned_staff_id')->count(),
        ];

        $this->notificationService->send(
            agency: $agency,
            eventKey: OtaNotificationEvent::DailyAdminReport->value,
            payload: $payload,
            fallbackSubject: 'Daily OTA Admin Report',
            fallbackBody: $this->bodyFromPayload('Daily report generated.', $payload),
            templateVariables: ['period_label' => $start->toFormattedDateString()]
        );
    }

    public function sendWeeklyReport(Agency $agency, ?CarbonImmutable $date = null): void
    {
        $anchor = $date ?? CarbonImmutable::now($agency->timezone ?? config('app.timezone'));
        $start = $anchor->startOfWeek();
        $end = $anchor->endOfWeek();

        $payload = [
            'period' => $start->toDateString().' to '.$end->toDateString(),
            'bookings_created' => Booking::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->count(),
            'gross_sales' => (float) Booking::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->sum('amount_paid'),
            'top_routes' => Booking::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->select('route', DB::raw('COUNT(*) as total'))->groupBy('route')->orderByDesc('total')->limit(5)->pluck('total', 'route')->all(),
            'top_airlines' => Booking::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->select('airline', DB::raw('COUNT(*) as total'))->groupBy('airline')->orderByDesc('total')->limit(5)->pluck('total', 'airline')->all(),
        ];

        $this->notificationService->send(
            agency: $agency,
            eventKey: OtaNotificationEvent::WeeklyAdminReport->value,
            payload: $payload,
            fallbackSubject: 'Weekly OTA Admin Report',
            fallbackBody: $this->bodyFromPayload('Weekly report generated.', $payload),
            templateVariables: ['period_label' => $payload['period']]
        );
    }

    public function sendMonthlyReport(Agency $agency, ?CarbonImmutable $date = null): void
    {
        $anchor = $date ?? CarbonImmutable::now($agency->timezone ?? config('app.timezone'));
        $start = $anchor->startOfMonth();
        $end = $anchor->endOfMonth();

        $payload = [
            'period' => $start->format('F Y'),
            'gross_sales' => (float) Booking::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->sum('amount_paid'),
            'unpaid_balance' => (float) Booking::query()->where('agency_id', $agency->id)->sum('balance_due'),
            'verified_payments' => BookingPayment::query()->where('agency_id', $agency->id)->where('status', 'verified')->whereBetween('updated_at', [$start, $end])->count(),
            'refunds_paid' => BookingPayment::query()->where('agency_id', $agency->id)->where('status', 'refunded')->whereBetween('updated_at', [$start, $end])->count(),
            'top_routes' => Booking::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->select('route', DB::raw('COUNT(*) as total'))->groupBy('route')->orderByDesc('total')->limit(5)->pluck('total', 'route')->all(),
        ];

        $this->notificationService->send(
            agency: $agency,
            eventKey: OtaNotificationEvent::MonthlyAdminReport->value,
            payload: $payload,
            fallbackSubject: 'Monthly OTA Admin Report',
            fallbackBody: $this->bodyFromPayload('Monthly report generated.', $payload),
            templateVariables: ['period_label' => $payload['period']]
        );
    }

    public function sendMonthlyLedgers(Agency $agency, ?CarbonImmutable $date = null): void
    {
        $anchor = $date ?? CarbonImmutable::now($agency->timezone ?? config('app.timezone'));
        $start = $anchor->startOfMonth();
        $end = $anchor->endOfMonth();

        $payload = [
            'period' => $start->format('F Y'),
            'bookings_count' => Booking::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->count(),
            'payments_count' => BookingPayment::query()->where('agency_id', $agency->id)->whereBetween('created_at', [$start, $end])->count(),
            'note' => 'CSV/PDF attachments can be enabled in a follow-up once document service export hooks are finalized.',
        ];

        $this->notificationService->send(
            agency: $agency,
            eventKey: OtaNotificationEvent::MonthlyFinanceLedger->value,
            payload: $payload,
            fallbackSubject: 'Monthly OTA Ledger Summary',
            fallbackBody: $this->bodyFromPayload('Monthly ledgers generated.', $payload),
            templateVariables: ['period_label' => $payload['period']]
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function bodyFromPayload(string $intro, array $payload): string
    {
        return $intro."\n\n".json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
