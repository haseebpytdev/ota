<?php

namespace App\Support\Security;

class SensitiveDataRedactor
{
    protected const SENSITIVE_KEYS = [
        'password',
        'token',
        'access_token',
        'refresh_token',
        'client_secret',
        'secret',
        'api_key',
        'authorization',
        'bearer',
        'credentials',
    ];

    public static function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $key => $inner) {
                $normalizedKey = is_string($key) ? strtolower($key) : '';
                if (is_string($key) && self::isSensitiveKey($normalizedKey)) {
                    $redacted[$key] = '[REDACTED]';

                    continue;
                }
                $redacted[$key] = self::redact($inner);
            }

            return $redacted;
        }

        return $value;
    }

    protected static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if ($key === $sensitive || str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }
}
