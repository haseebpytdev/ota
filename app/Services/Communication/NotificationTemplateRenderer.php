<?php

namespace App\Services\Communication;

use App\Models\Agency;
use App\Models\AgencyMessageTemplate;

class NotificationTemplateRenderer
{
    /**
     * @param  array<string, scalar|null>  $variables
     * @return array{subject: string, body: string, used_template: bool, template_enabled: bool}
     */
    public function render(Agency $agency, string $eventKey, string $channel, array $variables, string $fallbackSubject, string $fallbackBody): array
    {
        $template = AgencyMessageTemplate::query()
            ->where('agency_id', $agency->id)
            ->where('event', $eventKey)
            ->where('channel', $channel)
            ->first();

        if ($template === null) {
            return [
                'subject' => $fallbackSubject,
                'body' => $fallbackBody,
                'used_template' => false,
                'template_enabled' => true,
            ];
        }

        return [
            'subject' => $this->replacePlaceholders($template->subject ?? $fallbackSubject, $variables),
            'body' => $this->replacePlaceholders($template->body ?? $fallbackBody, $variables),
            'used_template' => true,
            'template_enabled' => (bool) $template->is_enabled,
        ];
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    private function replacePlaceholders(string $text, array $variables): string
    {
        $output = $text;
        foreach ($variables as $key => $value) {
            $output = str_replace('{{ '.$key.' }}', e((string) $value), $output);
        }

        return $output;
    }
}
