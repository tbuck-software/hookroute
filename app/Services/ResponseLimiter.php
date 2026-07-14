<?php

namespace App\Services;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ResponseLimiter
{
    public function options(): array
    {
        $limit = config('hookroute.max_response_bytes');

        return [
            'on_headers' => function (ResponseInterface $response) use ($limit): void {
                $length = (int) $response->getHeaderLine('Content-Length');
                if ($length > $limit) {
                    throw new RuntimeException('Destination response exceeded the configured size limit.');
                }
            },
            'progress' => function (int $downloadTotal, int $downloaded) use ($limit): void {
                if ($downloaded > $limit) {
                    throw new RuntimeException('Destination response exceeded the configured size limit.');
                }
            },
        ];
    }
}
