<?php

namespace App\Services;

class HeaderRedactor
{
    private const SENSITIVE_PARTS = [
        'authorization', 'cookie', 'token', 'secret', 'signature', 'api-key', 'apikey',
        'x-forwarded-for', 'forwarded', 'x-real-ip', 'cf-connecting-ip',
    ];

    public function redact(array $headers, array $additionalSensitiveNames = []): array
    {
        $additionalSensitiveNames = array_map('strtolower', $additionalSensitiveNames);

        return collect($headers)
            ->reject(function (mixed $value, string $name) use ($additionalSensitiveNames) {
                $name = strtolower($name);

                return in_array($name, $additionalSensitiveNames, true)
                    || collect(self::SENSITIVE_PARTS)->contains(fn (string $part) => str_contains($name, $part));
            })
            ->map(fn (mixed $value) => is_array($value) ? array_slice($value, 0, 5) : $value)
            ->all();
    }
}
