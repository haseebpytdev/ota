<?php

namespace App\Services\Communication;

use App\Enums\AccountType;
use App\Models\Agency;
use App\Models\AgencyNotificationSetting;
use App\Models\Booking;
use App\Models\User;

class NotificationRecipientResolver
{
    /**
     * @return array{to: array<int, string>, cc: array<int, string>, bcc: array<int, string>, scope: string}
     */
    public function resolve(Agency $agency, string $eventKey, ?Booking $booking, ?User $actor = null): array
    {
        $eventSetting = AgencyNotificationSetting::query()
            ->where('agency_id', $agency->id)
            ->where('event_key', $eventKey)
            ->where('channel', 'email')
            ->first();

        $scope = (string) ($eventSetting?->recipient_scope ?? 'admin');
        $to = $this->emailsForScope($agency, $scope, $booking, $actor);

        if ($eventSetting !== null && is_array($eventSetting->recipient_emails) && $eventSetting->recipient_emails !== []) {
            $to = $this->normalizeEmails($eventSetting->recipient_emails);
        }

        return [
            'scope' => $scope,
            'to' => $to,
            'cc' => $this->normalizeEmails($eventSetting?->cc_emails ?? []),
            'bcc' => $this->normalizeEmails($eventSetting?->bcc_emails ?? []),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function emailsForScope(Agency $agency, string $scope, ?Booking $booking, ?User $actor): array
    {
        return match ($scope) {
            'customer' => $this->customerEmails($booking),
            'agent' => $this->agentEmails($booking, $actor),
            'staff' => $this->staffEmails($agency, $booking),
            default => $this->adminEmails($agency),
        };
    }

    /**
     * @return array<int, string>
     */
    private function customerEmails(?Booking $booking): array
    {
        if ($booking === null) {
            return [];
        }

        $booking->loadMissing(['contact', 'customer']);

        return $this->normalizeEmails([
            $booking->contact?->email,
            $booking->customer?->email,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function agentEmails(?Booking $booking, ?User $actor): array
    {
        $emails = [];

        if ($booking !== null) {
            $booking->loadMissing('agent.user');
            $emails[] = $booking->agent?->user?->email;
        }

        if ($actor?->account_type === AccountType::Agent) {
            $emails[] = $actor->email;
        }

        return $this->normalizeEmails($emails);
    }

    /**
     * @return array<int, string>
     */
    private function staffEmails(Agency $agency, ?Booking $booking): array
    {
        $emails = [];
        if ($booking?->assignedStaff?->email) {
            $emails[] = $booking->assignedStaff->email;
        }

        $staff = $agency->users()->where('account_type', AccountType::Staff)->pluck('email')->all();

        return $this->normalizeEmails(array_merge($emails, $staff));
    }

    /**
     * @return array<int, string>
     */
    private function adminEmails(Agency $agency): array
    {
        $adminEmails = $agency->users()
            ->whereIn('account_type', [AccountType::AgencyAdmin, AccountType::PlatformAdmin])
            ->pluck('email')
            ->all();

        return $this->normalizeEmails($adminEmails);
    }

    /**
     * @param  array<int, mixed>  $emails
     * @return array<int, string>
     */
    private function normalizeEmails(array $emails): array
    {
        return collect($emails)
            ->filter(fn ($email) => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values()
            ->all();
    }
}
