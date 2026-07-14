<?php

use App\Jobs\ProcessDelivery;
use App\Jobs\SendDigest;

it('keeps job timeouts and processing leases below the database retry window', function () {
    $retryAfter = config('queue.connections.database.retry_after');
    $delivery = new ProcessDelivery(1);
    $digest = new SendDigest(1);

    expect($delivery->timeout)->toBeLessThan(config('hookroute.delivery_processing_lease_seconds'))
        ->and(config('hookroute.delivery_processing_lease_seconds'))->toBeLessThan($retryAfter)
        ->and($digest->timeout)->toBeLessThan(config('hookroute.digest_processing_lease_seconds'))
        ->and(config('hookroute.digest_processing_lease_seconds'))->toBeLessThan($retryAfter);
});
