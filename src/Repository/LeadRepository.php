<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Lead;
use App\Enum\LeadCanal;
use App\Enum\LeadSource;
use App\Enum\LeadStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lead>
 */
class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    public function countBySource(?LeadSource $source = null): int
    {
        $qb = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)');

        if ($source !== null) {
            $qb->andWhere('l.source = :source')
                ->setParameter('source', $source);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countByStatus(LeadStatus $status): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Lead>
     */
    public function findLatest(int $limit = 10, ?LeadSource $source = LeadSource::TZANET): array
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($source !== null) {
            $qb->andWhere('l.source = :source')->setParameter('source', $source);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Lead>
     */
    public function search(?LeadSource $source = null, ?LeadStatus $status = null, ?string $q = null): array
    {
        $qb = $this->createQueryBuilder('l')
            ->leftJoin('l.contract', 'c')
            ->addSelect('c')
            ->leftJoin('c.commission', 'cm')
            ->addSelect('cm')
            ->orderBy('l.createdAt', 'DESC');

        if ($source !== null) {
            $qb->andWhere('l.source = :source')->setParameter('source', $source);
        }

        if ($status !== null) {
            $qb->andWhere('l.status = :status')->setParameter('status', $status);
        }

        if ($q !== null && trim($q) !== '') {
            $qb->andWhere('l.nom LIKE :q OR l.email LIKE :q OR l.reference LIKE :q OR l.telephone LIKE :q')
                ->setParameter('q', '%'.trim($q).'%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Lead>
     */
    public function findWithoutContract(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.contract', 'c')
            ->andWhere('c.id IS NULL')
            ->andWhere('l.source = :tzanet')
            ->andWhere('l.status NOT IN (:excluded)')
            ->setParameter('tzanet', LeadSource::TZANET)
            ->setParameter('excluded', [LeadStatus::LOST, LeadStatus::CONTRACT_CANCELLED])
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, int>
     */
    public function countGroupedByStatus(): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('l.status AS status, COUNT(l.id) AS total')
            ->groupBy('l.status')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $status = $row['status'] instanceof LeadStatus ? $row['status']->value : (string) $row['status'];
            $out[$status] = (int) $row['total'];
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    public function countGroupedBySource(): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('l.source AS source, COUNT(l.id) AS total')
            ->groupBy('l.source')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $source = $row['source'] instanceof LeadSource ? $row['source']->value : (string) $row['source'];
            $out[$source] = (int) $row['total'];
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    public function countGroupedByCanal(): array
    {
        $rows = $this->createQueryBuilder('l')
            ->select('l.canal AS canal, COUNT(l.id) AS total')
            ->groupBy('l.canal')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $canal = $row['canal'] instanceof LeadCanal ? $row['canal']->value : (string) $row['canal'];
            $out[$canal] = (int) $row['total'];
        }

        return $out;
    }

    /**
     * Last N months of lead counts (YYYY-MM => count).
     *
     * @return array<string, int>
     */
    public function countByMonth(int $months = 6): array
    {
        $start = (new \DateTimeImmutable('first day of this month'))->modify('-'.($months - 1).' months');

        /** @var list<Lead> $leads */
        $leads = $this->createQueryBuilder('l')
            ->andWhere('l.createdAt >= :start')
            ->setParameter('start', $start)
            ->getQuery()
            ->getResult();

        $out = [];
        for ($i = 0; $i < $months; ++$i) {
            $out[$start->modify("+{$i} months")->format('Y-m')] = 0;
        }

        foreach ($leads as $lead) {
            $key = $lead->getCreatedAt()->format('Y-m');
            if (\array_key_exists($key, $out)) {
                ++$out[$key];
            }
        }

        return $out;
    }
}
