<?php

declare(strict_types=1);

namespace App\Enum;

enum ContractStatus: string
{
    case ACTIVE = 'ACTIVE';
    case FINISHED = 'FINISHED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::FINISHED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }
}
