<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Commission;
use App\Enum\CommissionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commission>
 */
class CommissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commission::class);
    }

    public function sumByStatus(?CommissionStatus $status = null): string
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.montantSignature), 0)');

        if ($status !== null) {
            $qb->andWhere('c.statut = :status')
                ->setParameter('status', $status);
        }

        return (string) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return list<Commission>
     */
    public function findAllWithRelations(?CommissionStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.contract', 'ct')
            ->addSelect('ct')
            ->leftJoin('ct.lead', 'l')
            ->addSelect('l')
            ->orderBy('c.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('c.statut = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<string, float>
     */
    public function sumByMonth(int $months = 6): array
    {
        $start = (new \DateTimeImmutable('first day of this month'))->modify('-'.($months - 1).' months');

        /** @var list<Commission> $commissions */
        $commissions = $this->createQueryBuilder('c')
            ->andWhere('c.createdAt >= :start')
            ->setParameter('start', $start)
            ->getQuery()
            ->getResult();

        $out = [];
        for ($i = 0; $i < $months; ++$i) {
            $out[$start->modify("+{$i} months")->format('Y-m')] = 0.0;
        }

        foreach ($commissions as $commission) {
            $key = $commission->getCreatedAt()->format('Y-m');
            if (\array_key_exists($key, $out)) {
                $out[$key] += (float) $commission->getMontantSignature();
            }
        }

        return $out;
    }
}
