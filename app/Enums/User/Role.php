<?php

namespace App\Enums\User;

enum Role: string
{
    case User = 'user';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function internal(): bool
    {
        return match ($this) {
            self::Admin, self::SuperAdmin => true,
            default => false,
        };
    }
}
