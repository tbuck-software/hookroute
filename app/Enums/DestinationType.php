<?php

namespace App\Enums;

enum DestinationType: string
{
    case Webhook = 'webhook';
    case Discord = 'discord';
    case Email = 'email';
    case Digest = 'digest';

    public function isDigest(): bool
    {
        return $this === self::Digest;
    }
}
