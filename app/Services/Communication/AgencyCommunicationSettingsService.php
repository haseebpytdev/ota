<?php

namespace App\Services\Communication;

use App\Models\Agency;
use App\Models\AgencyCommunicationSetting;
use App\Models\AgencyMessageTemplate;
use App\Models\AuditLog;
use App\Models\CommunicationLog;
use App\Models\User;
use App\Support\Security\SensitiveDataRedactor;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AgencyCommunicationSettingsService
{
    public function getOrCreateSettings(Agency $agency): AgencyCommunicationSetting
    {
        $setting = AgencyCommunicationSetting::query()->firstOrCreate(
            ['agency_id' => $agency->id],
            ['notification_rules' => ['email' => true, 'whatsapp' => false]]
        );

        return $setting->fresh();
    }

    public function updateSettings(Agency $agency, User $actor, array $data): AgencyCommunicationSetting
    {
        $settings = $this->getOrCreateSettings($agency);
        $settings->fill($data);
        $settings->save();

        $this->writeAudit($agency, $actor, 'communication_settings_updated', [
            'email_enabled' => $settings->email_enabled,
            'smtp_enabled' => $settings->smtp_enabled,
            'smtp_host' => $settings->smtp_host,
            'smtp_port' => $settings->smtp_port,
            'smtp_username' => $settings->smtp_username,
            'smtp_password' => $settings->maskedSmtpPassword(),
            'whatsapp_enabled' => $settings->whatsapp_enabled,
            'whatsapp_provider' => $settings->whatsapp_provider,
            'whatsapp_access_token' => $settings->maskedWhatsappToken(),
        ]);

        return $settings;
    }

    public function updateTemplate(Agency $agency, User $actor, string $event, string $channel, array $data): AgencyMessageTemplate
    {
        $template = AgencyMessageTemplate::query()->firstOrNew([
            'agency_id' => $agency->id,
            'event' => $event,
            'channel' => $channel,
        ]);
        $template->fill($data);
        $template->save();

        $this->writeAudit($agency, $actor, 'communication_template_updated', [
            'event' => $event,
            'channel' => $channel,
            'is_enabled' => $template->is_enabled,
        ]);

        return $template;
    }

    /** @return array{subject: string|null, body: string, used_template: bool, is_enabled: bool} */
    public function renderTemplate(Agency $agency, string $event, string $channel, array $variables): array
    {
        $template = AgencyMessageTemplate::query()
            ->where('agency_id', $agency->id)
            ->where('event', $event)
            ->where('channel', $channel)
            ->first();

        if ($template === null) {
            return [
                'subject' => null,
                'body' => '',
                'used_template' => false,
                'is_enabled' => true,
            ];
        }

        return [
            'subject' => $this->replacePlaceholders($template->subject, $variables),
            'body' => $this->replacePlaceholders($template->body, $variables),
            'used_template' => true,
            'is_enabled' => $template->is_enabled,
        ];
    }

    public function testEmailSettings(Agency $agency, User $actor, string $recipientEmail): CommunicationLog
    {
        $settings = $this->getOrCreateSettings($agency);
        $log = CommunicationLog::query()->create([
            'agency_id' => $agency->id,
            'user_id' => $actor->id,
            'channel' => 'email',
            'event' => 'settings_test_email',
            'recipient_email' => $recipientEmail,
            'status' => 'queued',
            'provider' => config('mail.default'),
            'meta' => ['smtp_enabled' => $settings->smtp_enabled],
        ]);

        try {
            Mail::raw('Test email for communication settings.', function ($message) use ($recipientEmail): void {
                $message->to($recipientEmail)->subject('Communication Settings Test');
            });

            $log->forceFill(['status' => 'sent', 'sent_at' => now()])->save();
        } catch (Throwable $e) {
            $errorMessage = $e->getMessage();
            if (filled($settings->smtp_password)) {
                $errorMessage = str_replace((string) $settings->smtp_password, '[REDACTED]', $errorMessage);
            }
            $log->forceFill([
                'status' => 'failed',
                'error_message' => $errorMessage,
            ])->save();
        }

        return $log;
    }

    /** @return array{status: string, missing_fields: array<int, string>} */
    public function testWhatsappReadiness(Agency $agency, User $actor): array
    {
        $settings = $this->getOrCreateSettings($agency);
        $required = [
            'whatsapp_provider' => $settings->whatsapp_provider,
            'whatsapp_phone_number_id' => $settings->whatsapp_phone_number_id,
            'whatsapp_business_account_id' => $settings->whatsapp_business_account_id,
            'whatsapp_access_token' => $settings->whatsapp_access_token,
        ];

        $missing = collect($required)->filter(fn ($value) => blank($value))->keys()->values()->all();
        $status = $missing === [] ? 'ready_for_review' : 'missing_fields';

        $this->writeAudit($agency, $actor, 'whatsapp_readiness_checked', ['status' => $status, 'missing_fields' => $missing]);

        return ['status' => $status, 'missing_fields' => $missing];
    }

    private function replacePlaceholders(?string $text, array $variables): string
    {
        $output = (string) $text;
        foreach ($variables as $key => $value) {
            $output = str_replace('{{ '.$key.' }}', e((string) $value), $output);
        }

        return $output;
    }

    private function writeAudit(Agency $agency, User $actor, string $action, array $newValues): void
    {
        AuditLog::query()->create([
            'agency_id' => $agency->id,
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => Agency::class,
            'auditable_id' => $agency->id,
            'properties' => [
                'old_values' => [],
                'new_values' => SensitiveDataRedactor::redact($newValues),
            ],
        ]);
    }
}
