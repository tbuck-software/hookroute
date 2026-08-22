<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class DigestWindow
{
    /**
     * How many past daily send points are caught up after downtime,
     * so an offline host does not queue weeks of digests at once.
     */
    private const MAX_CATCH_UP_DAYS = 7;

    /**
     * All daily windows whose send point has passed and whose window_end
     * lies after the last covered window. Ordered chronologically.
     *
     * @return list<array{CarbonImmutable, CarbonImmutable}>
     */
    public function dueWindows(array $config, CarbonInterface $now, ?CarbonInterface $lastCoveredEnd = null): array
    {
        $timezone = $config['timezone'] ?? 'UTC';
        $localNow = CarbonImmutable::instance($now)->setTimezone($timezone);
        $sendTime = $config['send_time'] ?? '18:00';
        $startTime = $config['window_start_time'] ?? '08:00';

        $todaySendPoint = $localNow->startOfDay()->setTimeFromTimeString($sendTime);
        $latestDue = $localNow->gte($todaySendPoint) ? $todaySendPoint : $todaySendPoint->subDay();

        if ($lastCoveredEnd === null) {
            // Without any prior run only today's window is considered,
            // matching the behaviour of a host that never missed a day.
            if ($localNow->lt($todaySendPoint)) {
                return [];
            }

            $candidate = $todaySendPoint;
        } else {
            $candidate = CarbonImmutable::instance($lastCoveredEnd)->setTimezone($timezone);
            $nextSendPoint = $candidate->startOfDay()->setTimeFromTimeString($sendTime);
            $candidate = $nextSendPoint->gt($candidate) ? $nextSendPoint : $nextSendPoint->addDay();

            $earliest = $latestDue->subDays(self::MAX_CATCH_UP_DAYS - 1);
            while ($candidate->lt($earliest)) {
                $candidate = $candidate->addDay();
            }
        }

        $windows = [];
        for (; $candidate->lte($latestDue); $candidate = $candidate->addDay()) {
            $end = $candidate;
            $start = $end->startOfDay()->setTimeFromTimeString($startTime);
            if ($start->gte($end)) {
                $start = $start->subDay();
            }

            $windows[] = [$start->utc(), $end->utc()];
        }

        return $windows;
    }
}
