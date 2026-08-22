<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Delivered = 'delivered';
    case Retrying = 'retrying';
    case Failed = 'failed';
    case Skipped = 'skipped';

    /**
     * Statuses that a worker may claim or that are waiting for a worker.
     *
     * @return list<self>
     */
    public static function queued(): array
    {
        return [self::Pending, self::Retrying];
    }
}
