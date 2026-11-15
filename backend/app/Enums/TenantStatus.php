<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Suspended = 'suspended';
    case Trial = 'trial';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Suspended => 'Suspended',
            self::Trial => 'Trial',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'emerald',
            self::Expired => 'amber',
            self::Suspended => 'rose',
            self::Trial => 'sky',
        };
    }
}
