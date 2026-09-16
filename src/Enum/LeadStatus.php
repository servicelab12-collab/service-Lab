<?php

declare(strict_types=1);

namespace App\Enum;

enum LeadStatus: string
{
    case NEW = 'NEW';
    case CONTACTED = 'CONTACTED';
    case NEED_ANALYSIS = 'NEED_ANALYSIS';
    case OFFER_SENT = 'OFFER_SENT';
    case CONTRACT_SIGNED = 'CONTRACT_SIGNED';
    case ACTIVE_CLIENT = 'ACTIVE_CLIENT';
    case CONTRACT_CANCELLED = 'CONTRACT_CANCELLED';
    case LOST = 'LOST';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nouveau contact',
            self::CONTACTED => 'Contact effectué',
            self::NEED_ANALYSIS => 'Analyse du besoin',
            self::OFFER_SENT => 'Offre proposée',
            self::CONTRACT_SIGNED => 'Contrat signé',
            self::ACTIVE_CLIENT => 'Client actif',
            self::CONTRACT_CANCELLED => 'Annuler le contrat',
            self::LOST => 'Perdu',
        };
    }

    /**
     * @return list<self>
     */
    public static function pipeline(): array
    {
        return [
            self::NEW,
            self::CONTACTED,
            self::NEED_ANALYSIS,
            self::OFFER_SENT,
            self::CONTRACT_SIGNED,
            self::ACTIVE_CLIENT,
        ];
    }
}
