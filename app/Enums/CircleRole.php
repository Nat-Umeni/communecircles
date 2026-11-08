<?php

namespace App\Enums;

enum CircleRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }

    public function hasManagerRole(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            default => false
        };
    }
}
