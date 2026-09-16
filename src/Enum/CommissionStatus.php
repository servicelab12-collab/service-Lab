<?php

declare(strict_types=1);

namespace App\Enum;

enum CommissionStatus: string
{
    case A_PAYER = 'A_PAYER';
    case PAYEE = 'PAYEE';

    public function label(): string
    {
        return match ($this) {
            self::A_PAYER => 'À payer',
            self::PAYEE => 'Payée',
        };
    }
}
