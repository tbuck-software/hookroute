<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class DigestWindow
{
    /** @return array{CarbonImmutable, CarbonImmutable}|null */
    public function dueWindow(array $config, CarbonInterface $now): ?array
    {
        $timezone = $config['timezone'] ?? 'UTC';
        $localNow = CarbonImmutable::instance($now)->setTimezone($timezone);
        $end = $localNow->startOfDay()->setTimeFromTimeString($config['send_time'] ?? '18:00');

        if ($localNow->lt($end)) {
            return null;
        }

        $start = $localNow->startOfDay()->setTimeFromTimeString($config['window_start_time'] ?? '08:00');
        if ($start->gte($end)) {
            $start = $start->subDay();
        }

        return [$start->utc(), $end->utc()];
    }
}
