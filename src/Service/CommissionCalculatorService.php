<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Contract;
use App\Entity\Offer;
use App\Enum\LeadSource;
use App\Repository\OfferRepository;

/**
 * TZANET partner rules:
 * - Client gets first month free (when offer says so / code TZANET)
 * - TZANET gets a one-time commission = 1 month of rent (or fixed offer amount)
 * - TZANET also gets annual return = 2% of the client's annual purchases
 */
final class CommissionCalculatorService
{
    public function __construct(
        private readonly OfferRepository $offerRepository,
    ) {
    }

    /**
     * @return array{montantSignature: string, retourAnnuel: string, premierMoisGratuit: bool, mode: string}|null
     */
    public function calculateForContract(Contract $contract, ?Offer $offer = null): ?array
    {
        $lead = $contract->getLead();
        if ($lead === null || $lead->getSource() !== LeadSource::TZANET) {
            return null;
        }

        $offer ??= $this->offerRepository->findActiveOffer($contract->getDateSignature());
        if ($offer === null || !$offer->isCurrentlyValid($contract->getDateSignature())) {
            throw new \RuntimeException('Aucune offre partenaire active n\'est disponible pour calculer la commission.');
        }

        $montant = $offer->isCommissionEqualsMonthlyRent()
            ? $contract->getLoyerMensuel()
            : $offer->getCommissionSignature();

        if ((float) $montant <= 0) {
            throw new \RuntimeException('La commission calculée est invalide. Vérifiez l’offre (loyer ou commission fixe).');
        }

        return [
            'montantSignature' => number_format((float) $montant, 2, '.', ''),
            'retourAnnuel' => $offer->getRetourAnnuel(),
            'premierMoisGratuit' => $offer->isPremierMoisGratuit(),
            'mode' => $offer->isCommissionEqualsMonthlyRent() ? 'loyer' : 'fixe',
        ];
    }

    public function calculateRetourMontant(string|float $achatsAnnuel, string|float $tauxPourcent): string
    {
        $amount = (float) $achatsAnnuel * ((float) $tauxPourcent / 100);

        return number_format($amount, 2, '.', '');
    }

    public function calculateTotalTzanet(string|float $commissionSignature, string|float $achatsAnnuel, string|float $tauxPourcent): string
    {
        $total = (float) $commissionSignature + (float) $this->calculateRetourMontant($achatsAnnuel, $tauxPourcent);

        return number_format($total, 2, '.', '');
    }
}
