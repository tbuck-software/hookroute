<?php

namespace App\Services;

class ResponseRedactor
{
    private const SECRET_ASSIGNMENT = '/((?:authorization|token|secret|api[_-]?key|password)"?\s*[:=]\s*"?)([^"\s,}]{4,})/i';

    private const BEARER_TOKEN = '/bearer\s+[a-z0-9._~+\/=-]{8,}/i';

    public function redact(string $body): string
    {
        $body = preg_replace(self::SECRET_ASSIGNMENT, '$1[redacted]', $body) ?? $body;

        return preg_replace(self::BEARER_TOKEN, 'Bearer [redacted]', $body) ?? $body;
    }
}
