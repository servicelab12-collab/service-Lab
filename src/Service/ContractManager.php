<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Commission;
use App\Entity\Contract;
use App\Entity\Lead;
use App\Enum\ContractStatus;
use App\Enum\LeadStatus;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ContractManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CommissionCalculatorService $commissionCalculator,
        private readonly OfferRepository $offerRepository,
    ) {
    }

    public function createFromLead(
        Lead $lead,
        string|float $loyerMensuel,
        int $dureeMois = 12,
        ?\DateTimeImmutable $dateSignature = null,
    ): Contract {
        if ($lead->getContract() !== null) {
            throw new \RuntimeException('Ce lead possède déjà un contrat.');
        }

        $dateSignature ??= new \DateTimeImmutable('today');
        $offer = $this->offerRepository->findActiveOffer($dateSignature);

        $contract = (new Contract())
            ->setLead($lead)
            ->setLoyerMensuel($loyerMensuel)
            ->setDureeMois($dureeMois)
            ->setDateSignature($dateSignature)
            ->setStatut(ContractStatus::ACTIVE)
            ->setPremierMoisGratuit($offer?->isPremierMoisGratuit() ?? false)
            ->setOffer($offer);

        $amounts = $this->commissionCalculator->calculateForContract($contract, $offer);
        if ($amounts !== null) {
            $contract->setPremierMoisGratuit($amounts['premierMoisGratuit']);
            $commission = (new Commission())
                ->setMontantSignature($amounts['montantSignature'])
                ->setRetourAnnuel($amounts['retourAnnuel'])
                ->setAchatsAnnuel('0.00');
            $contract->setCommission($commission);
        }

        $lead->setStatus(LeadStatus::CONTRACT_SIGNED);
        $lead->setContract($contract);

        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        return $contract;
    }

    public function delete(Contract $contract): void
    {
        $lead = $contract->getLead();

        if ($lead !== null) {
            if (\in_array($lead->getStatus(), [
                LeadStatus::CONTRACT_SIGNED,
                LeadStatus::ACTIVE_CLIENT,
                LeadStatus::CONTRACT_CANCELLED,
            ], true)) {
                $lead->setStatus(LeadStatus::OFFER_SENT);
            }
        }

        // Commission is cascade-removed with the contract.
        $this->entityManager->remove($contract);

        if ($lead !== null) {
            $lead->detachContract();
        }

        $this->entityManager->flush();
    }
}
