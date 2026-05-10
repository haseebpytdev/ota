<?php

namespace App\Services\Communication;

use App\Models\Agency;
use App\Models\AgencyNotificationSetting;
use App\Models\Booking;
use App\Models\CommunicationLog;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OtaNotificationService
{
    public function __construct(
        protected AgencyCommunicationSettingsService $communicationSettingsService,
        protected NotificationRecipientResolver $recipientResolver,
        protected NotificationPayloadSanitizer $payloadSanitizer,
        protected NotificationTemplateRenderer $templateRenderer,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, scalar|null>  $templateVariables
     */
    public function send(
        Agency $agency,
        string $eventKey,
        array $payload = [],
        ?Booking $booking = null,
        ?User $actor = null,
        string $fallbackSubject = 'Asif Travels Notification',
        string $fallbackBody = 'A new OTA event was recorded.',
        array $templateVariables = [],
    ): void {
        $settings = $this->communicationSettingsService->getOrCreateSettings($agency);
        $eventSetting = $this->getOrCreateEventSetting($agency, $eventKey);
        $recipientBundle = $this->recipientResolver->resolve($agency, $eventKey, $booking, $actor);
        $scope = $recipientBundle['scope'];
        $safePayload = $this->payloadSanitizer->sanitizeForScope($payload, $scope);

        $rendered = $this->templateRenderer->render(
            $agency,
            $eventKey,
            'email',
            array_merge([
                'agency_name' => (string) ($agency->agencySetting?->display_name ?? $agency->name),
                'booking_reference' => (string) ($booking?->reference_code ?? ''),
            ], $templateVariables),
            $fallbackSubject,
            $fallbackBody
        );

        if (! $settings->email_enabled || ! $eventSetting->enabled || ! $rendered['template_enabled']) {
            $this->logSkipped($agency, $eventKey, $booking, $actor, $recipientBundle['to'], $safePayload, 'Notification disabled by settings/template.');

            return;
        }

        if ($recipientBundle['to'] === []) {
            $this->logSkipped($agency, $eventKey, $booking, $actor, [], $safePayload, 'No recipients resolved.');

            return;
        }

        $log = CommunicationLog::query()->create([
            'agency_id' => $agency->id,
            'booking_id' => $booking?->id,
            'user_id' => $actor?->id ?? $booking?->customer_id,
            'channel' => 'email',
            'event' => $eventKey,
            'recipient_email' => implode(', ', $recipientBundle['to']),
            'subject' => $rendered['subject'],
            'message' => $rendered['body'],
            'status' => $this->isImmediateMailer() ? 'sending' : 'queued',
            'provider' => (string) config('mail.default'),
            'meta' => [
                'cc' => $recipientBundle['cc'],
                'bcc' => $recipientBundle['bcc'],
                'scope' => $scope,
                'payload' => $safePayload,
            ],
        ]);

        try {
            $messageCallback = function ($message) use ($recipientBundle, $rendered): void {
                $message->to($recipientBundle['to'])->subject($rendered['subject']);
                if ($recipientBundle['cc'] !== []) {
                    $message->cc($recipientBundle['cc']);
                }
                if ($recipientBundle['bcc'] !== []) {
                    $message->bcc($recipientBundle['bcc']);
                }
            };

            Mail::raw($rendered['body'], $messageCallback);
            $log->forceFill([
                'status' => $this->isImmediateMailer() || $eventSetting->digest_mode === 'immediate' ? 'sent' : 'queued',
                'sent_at' => now(),
            ])->save();
        } catch (Throwable $e) {
            $log->forceFill([
                'status' => 'failed',
                'error_message' => $this->safeError($e->getMessage(), $settings->smtp_password),
            ])->save();
        }
    }

    private function getOrCreateEventSetting(Agency $agency, string $eventKey): AgencyNotificationSetting
    {
        if (! Schema::hasTable('agency_notification_settings')) {
            return new AgencyNotificationSetting([
                'agency_id' => $agency->id,
                'event_key' => $eventKey,
                'channel' => 'email',
                'enabled' => true,
                'recipient_scope' => 'admin',
                'digest_mode' => 'immediate',
            ]);
        }

        return AgencyNotificationSetting::query()->firstOrCreate(
            [
                'agency_id' => $agency->id,
                'event_key' => $eventKey,
                'channel' => 'email',
            ],
            [
                'enabled' => true,
                'recipient_scope' => 'admin',
                'digest_mode' => 'immediate',
            ]
        );
    }

    /**
     * @param  array<int, string>  $recipients
     * @param  array<string, mixed>  $payload
     */
    private function logSkipped(
        Agency $agency,
        string $eventKey,
        ?Booking $booking,
        ?User $actor,
        array $recipients,
        array $payload,
        string $reason
    ): void {
        CommunicationLog::query()->create([
            'agency_id' => $agency->id,
            'booking_id' => $booking?->id,
            'user_id' => $actor?->id ?? $booking?->customer_id,
            'channel' => 'email',
            'event' => $eventKey,
            'recipient_email' => $recipients !== [] ? implode(', ', $recipients) : null,
            'status' => 'skipped',
            'error_message' => $reason,
            'provider' => (string) config('mail.default'),
            'meta' => ['payload' => $payload],
        ]);
    }

    private function safeError(string $message, ?string $smtpPassword): string
    {
        if (filled($smtpPassword)) {
            return str_replace((string) $smtpPassword, '[REDACTED]', $message);
        }

        return $message;
    }

    private function isImmediateMailer(): bool
    {
        return in_array((string) config('mail.default'), ['log', 'array', 'local'], true);
    }
}
