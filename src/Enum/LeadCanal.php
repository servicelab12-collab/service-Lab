<?php

declare(strict_types=1);

namespace App\Enum;

enum LeadCanal: string
{
    case SITE_ACCUEIL = 'SITE_ACCUEIL';
    case SITE_CONTACT = 'SITE_CONTACT';
    case TZANET = 'TZANET';

    public function label(): string
    {
        return match ($this) {
            self::SITE_ACCUEIL => 'Accueil client',
            self::SITE_CONTACT => 'Page contact',
            self::TZANET => 'Offre TZANET',
        };
    }

    public function publicRoute(): string
    {
        return match ($this) {
            self::SITE_ACCUEIL => 'app_home',
            self::SITE_CONTACT => 'app_contact',
            self::TZANET => 'partner_tzanet',
        };
    }
}
