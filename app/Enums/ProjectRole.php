<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function canManage(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }
}
