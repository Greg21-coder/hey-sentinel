<?php

namespace App\Enums;

enum UserRole: string
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

    public function canManageBilling(): bool
    {
        return $this === self::Owner;
    }

    public function canInvite(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }
}
