<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Offer;
use App\Repository\OfferRepository;
use App\Service\OfferManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class OfferManagerTest extends TestCase
{
    public function testActivatingOfferDeactivatesPreviousOnes(): void
    {
        $old = (new Offer())
            ->setActive(true)
            ->setDateDebut(new \DateTimeImmutable('2026-01-01'))
            ->setDateFin(null)
            ->setPremierMoisGratuit(true)
            ->setCommissionEqualsMonthlyRent(true)
            ->setRetourAnnuel('2.00');

        $ref = new \ReflectionProperty(Offer::class, 'id');
        $ref->setValue($old, 1);

        $new = (new Offer())
            ->setActive(true)
            ->setDateDebut(new \DateTimeImmutable('2026-08-14'))
            ->setDateFin(null)
            ->setPremierMoisGratuit(true)
            ->setCommissionEqualsMonthlyRent(true)
            ->setRetourAnnuel('3.00');

        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$old]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repo = $this->createMock(OfferRepository::class);
        $repo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($new);
        $em->expects(self::once())->method('flush');

        $manager = new OfferManager($em, $repo);
        $manager->save($new);

        self::assertFalse($old->isActive());
        self::assertSame('2026-08-13', $old->getDateFin()?->format('Y-m-d'));
        self::assertTrue($new->isActive());
    }

    public function testCreateDefaultsMatchTzanetRules(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(OfferRepository::class);
        $manager = new OfferManager($em, $repo);

        $offer = $manager->createDefaults();

        self::assertTrue($offer->isPremierMoisGratuit());
        self::assertTrue($offer->isCommissionEqualsMonthlyRent());
        self::assertSame('2.00', $offer->getRetourAnnuel());
        self::assertTrue($offer->isActive());
    }
}
