<?php

namespace App\Services;

class TemplateRenderer
{
    public function render(?string $template, array $context, bool $jsonSafe = true): string
    {
        if ($template === null || $template === '') {
            return '';
        }

        return preg_replace_callback('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', function (array $match) use ($context, $jsonSafe) {
            $value = data_get($context, $match[1], '');

            if (is_array($value) || is_object($value)) {
                return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if ($value === null) {
                return 'null';
            }

            if (! $jsonSafe) {
                return (string) $value;
            }

            $encoded = json_encode((string) $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            return is_string($encoded) ? substr($encoded, 1, -1) : (string) $value;
        }, $template) ?? $template;
    }
}
