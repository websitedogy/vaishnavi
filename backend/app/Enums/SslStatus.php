<?php

namespace App\Enums;

enum SslStatus: string
{
    case Pending = 'pending';
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Failed = 'failed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Provisioning => 'Provisioning',
            self::Active => 'Active',
            self::Failed => 'Failed',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'slate',
            self::Provisioning => 'sky',
            self::Active => 'emerald',
            self::Failed => 'rose',
            self::Expired => 'amber',
        };
    }
}
