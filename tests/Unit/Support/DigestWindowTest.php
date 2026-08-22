<?php

use App\Services\DigestWindow;
use Carbon\CarbonImmutable;

$service = new DigestWindow;
$config = [
    'send_time' => '18:00',
    'window_start_time' => '08:00',
    'timezone' => 'UTC',
];

it('returns no window before the configured send time', function () use ($service, $config) {
    expect($service->dueWindows($config, CarbonImmutable::parse('2026-07-13 17:59:00 UTC')))->toBe([]);
});

it('builds the daily window ending at the send time', function () use ($service, $config) {
    $windows = $service->dueWindows($config, CarbonImmutable::parse('2026-07-13 18:05:00 UTC'));

    expect($windows)->toHaveCount(1)
        ->and($windows[0][0]->toDateTimeString())->toBe('2026-07-13 08:00:00')
        ->and($windows[0][1]->toDateTimeString())->toBe('2026-07-13 18:00:00');
});

it('does not repeat the already covered day', function () use ($service, $config) {
    $windows = $service->dueWindows(
        $config,
        CarbonImmutable::parse('2026-07-14 18:05:00 UTC'),
        CarbonImmutable::parse('2026-07-13 18:00:00 UTC'),
    );

    expect($windows)->toHaveCount(1)
        ->and($windows[0][1]->toDateTimeString())->toBe('2026-07-14 18:00:00');
});

it('catches up one window per missed day after downtime', function () use ($service, $config) {
    $windows = $service->dueWindows(
        $config,
        CarbonImmutable::parse('2026-07-15 18:05:00 UTC'),
        CarbonImmutable::parse('2026-07-13 18:00:00 UTC'),
    );

    expect($windows)->toHaveCount(2)
        ->and($windows[0][0]->toDateTimeString())->toBe('2026-07-14 08:00:00')
        ->and($windows[0][1]->toDateTimeString())->toBe('2026-07-14 18:00:00')
        ->and($windows[1][0]->toDateTimeString())->toBe('2026-07-15 08:00:00')
        ->and($windows[1][1]->toDateTimeString())->toBe('2026-07-15 18:00:00');
});

it('limits catch-up to a bounded number of days', function () use ($service, $config) {
    $windows = $service->dueWindows(
        $config,
        CarbonImmutable::parse('2026-07-15 18:05:00 UTC'),
        CarbonImmutable::parse('2026-01-01 18:00:00 UTC'),
    );

    expect(count($windows))->toBe(7)
        ->and($windows[0][1]->toDateTimeString())->toBe('2026-07-09 18:00:00')
        ->and($windows[6][1]->toDateTimeString())->toBe('2026-07-15 18:00:00');
});

it('respects the destination timezone for the send point', function () use ($service) {
    $berlin = ['send_time' => '18:00', 'window_start_time' => '08:00', 'timezone' => 'Europe/Berlin'];

    // 14:00 UTC is 16:00 in Berlin, before the send point.
    expect($service->dueWindows($berlin, CarbonImmutable::parse('2026-07-13 14:00:00 UTC')))->toBe([]);

    $windows = $service->dueWindows($berlin, CarbonImmutable::parse('2026-07-13 16:05:00 UTC'));

    expect($windows)->toHaveCount(1)
        ->and($windows[0][0]->utc()->toDateTimeString())->toBe('2026-07-13 06:00:00')
        ->and($windows[0][1]->utc()->toDateTimeString())->toBe('2026-07-13 16:00:00');
});
