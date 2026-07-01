<?php

namespace App\Enums;

enum ProfileStatus: string
{
    case Free = 'free';
    case Assigned = 'assigned';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Libre',
            self::Assigned => 'Asignado',
        };
    }
}
