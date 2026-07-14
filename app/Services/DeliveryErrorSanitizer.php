<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeliveryErrorSanitizer
{
    public function summarize(?Throwable $exception): string
    {
        return match (true) {
            $exception instanceof ConnectionException => 'Could not connect to destination.',
            $exception instanceof ValidationException => 'Destination URL failed the network safety check.',
            $exception === null => 'Delivery failed.',
            default => 'Destination delivery failed.',
        };
    }
}
