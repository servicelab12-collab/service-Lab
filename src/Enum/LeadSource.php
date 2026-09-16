<?php

declare(strict_types=1);

namespace App\Enum;

enum LeadSource: string
{
    case TZANET = 'TZANET';
    case DIRECT = 'DIRECT';

    public function label(): string
    {
        return match ($this) {
            self::TZANET => 'TZANET',
            self::DIRECT => 'Direct',
        };
    }
}
