<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Offer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offer>
 */
class OfferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offer::class);
    }

    public function findActiveOffer(?\DateTimeImmutable $at = null): ?Offer
    {
        $at ??= new \DateTimeImmutable('today');

        /** @var list<Offer> $offers */
        $offers = $this->createQueryBuilder('o')
            ->andWhere('o.active = true')
            ->andWhere('o.dateDebut <= :at')
            ->andWhere('o.dateFin IS NULL OR o.dateFin >= :at')
            ->setParameter('at', $at)
            ->orderBy('o.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return $offers[0] ?? null;
    }
}
