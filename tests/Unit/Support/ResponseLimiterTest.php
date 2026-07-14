<?php

use App\Services\ResponseLimiter;
use GuzzleHttp\Psr7\Response;

it('rejects declared and streamed responses beyond the configured limit', function () {
    config(['hookroute.max_response_bytes' => 10]);
    $options = (new ResponseLimiter)->options();

    expect(fn () => $options['on_headers'](new Response(200, ['Content-Length' => '11'])))
        ->toThrow(RuntimeException::class)
        ->and(fn () => $options['progress'](0, 11))
        ->toThrow(RuntimeException::class);
});
