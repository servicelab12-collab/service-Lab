<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ensures only one partner offer is active at a time.
 * Creating / activating an offer deactivates the others and closes their validity.
 */
final class OfferManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OfferRepository $offerRepository,
    ) {
    }

    public function createDefaults(): Offer
    {
        return (new Offer())
            ->setPremierMoisGratuit(true)
            ->setCommissionEqualsMonthlyRent(true)
            ->setCommissionSignature('180.00')
            ->setRetourAnnuel('2.00')
            ->setDateDebut(new \DateTimeImmutable('today'))
            ->setDateFin(null)
            ->setActive(true);
    }

    public function save(Offer $offer): Offer
    {
        if ($offer->getDateFin() !== null && $offer->getDateFin() < $offer->getDateDebut()) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure ou égale à la date de début.');
        }

        if (!$offer->isCommissionEqualsMonthlyRent() && (float) $offer->getCommissionSignature() <= 0) {
            throw new \InvalidArgumentException('Indiquez une commission fixe supérieure à 0 $, ou cochez « commission = 1 mois de loyer ».');
        }

        if ($offer->isActive()) {
            $this->deactivateOtherOffers($offer);
        }

        $this->entityManager->persist($offer);
        $this->entityManager->flush();

        return $offer;
    }

    private function deactivateOtherOffers(Offer $current): void
    {
        $dayBeforeStart = $current->getDateDebut()->modify('-1 day');

        /** @var list<Offer> $others */
        $others = $this->offerRepository->createQueryBuilder('o')
            ->andWhere('o.active = true')
            ->getQuery()
            ->getResult();

        foreach ($others as $other) {
            if ($other === $current || ($current->getId() !== null && $other->getId() === $current->getId())) {
                continue;
            }

            $other->setActive(false);

            // Close previous open-ended (or overlapping) offers the day before the new one starts
            if ($other->getDateFin() === null || $other->getDateFin() >= $current->getDateDebut()) {
                if ($dayBeforeStart >= $other->getDateDebut()) {
                    $other->setDateFin($dayBeforeStart);
                } else {
                    $other->setDateFin($other->getDateDebut());
                }
            }
        }
    }
}
