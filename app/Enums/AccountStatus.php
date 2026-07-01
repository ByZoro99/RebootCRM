<?php

namespace App\Enums;

enum AccountStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Expired => 'Vencida',
            self::Blocked => 'Bloqueada',
        };
    }
}
