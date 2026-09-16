<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contract;
use App\Enum\ContractStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contract>
 */
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    /**
     * @return list<Contract>
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.lead', 'l')
            ->addSelect('l')
            ->leftJoin('c.commission', 'co')
            ->addSelect('co')
            ->leftJoin('c.offer', 'o')
            ->addSelect('o')
            ->orderBy('c.dateSignature', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByStatus(?ContractStatus $status = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)');

        if ($status !== null) {
            $qb->andWhere('c.statut = :status')->setParameter('status', $status);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return array<string, int>
     */
    public function countGroupedByStatus(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('c.statut AS status, COUNT(c.id) AS total')
            ->groupBy('c.statut')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $status = $row['status'] instanceof ContractStatus ? $row['status']->value : (string) $row['status'];
            $out[$status] = (int) $row['total'];
        }

        return $out;
    }
}
