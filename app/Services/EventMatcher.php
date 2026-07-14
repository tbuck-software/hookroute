<?php

namespace App\Services;

class EventMatcher
{
    public function matches(array $payload, ?array $filters): bool
    {
        foreach ($filters ?? [] as $filter) {
            $field = trim((string) ($filter['field'] ?? ''));
            $operator = $filter['operator'] ?? 'equals';
            $expected = $filter['value'] ?? null;
            $sentinel = new \stdClass;
            $actual = data_get($payload, $field, $sentinel);

            $matches = match ($operator) {
                'equals' => $actual !== $sentinel && $actual == $expected,
                'not_equals' => $actual === $sentinel || $actual != $expected,
                'contains' => $actual !== $sentinel && $this->contains($actual, $expected),
                'exists' => $actual !== $sentinel,
                'not_exists' => $actual === $sentinel,
                'greater_than' => is_numeric($actual) && is_numeric($expected) && $actual > $expected,
                'less_than' => is_numeric($actual) && is_numeric($expected) && $actual < $expected,
                default => false,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }

    private function contains(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            return in_array($expected, $actual, false);
        }

        return str_contains((string) $actual, (string) $expected);
    }
}
