<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Contract;
use App\Entity\Lead;
use App\Entity\Offer;
use App\Enum\LeadSource;
use App\Repository\OfferRepository;
use App\Service\CommissionCalculatorService;
use PHPUnit\Framework\TestCase;

final class CommissionCalculatorServiceTest extends TestCase
{
    public function testTzanetLeadGeneratesCommissionEqualToMonthlyRent(): void
    {
        $offer = (new Offer())
            ->setActive(true)
            ->setPremierMoisGratuit(true)
            ->setCommissionEqualsMonthlyRent(true)
            ->setCommissionSignature('99.00')
            ->setRetourAnnuel('2.00')
            ->setDateDebut(new \DateTimeImmutable('2026-01-01'));

        $offerRepository = $this->createMock(OfferRepository::class);
        $offerRepository->method('findActiveOffer')->willReturn($offer);

        $lead = (new Lead())->setSource(LeadSource::TZANET)->setNom('Test')->setEmail('t@test.com')->setTelephone('1')->setMachineRecherchee('Lave-vaisselle');
        $contract = (new Contract())->setLead($lead)->setLoyerMensuel('180.00');

        $service = new CommissionCalculatorService($offerRepository);
        $result = $service->calculateForContract($contract);

        self::assertNotNull($result);
        self::assertSame('180.00', $result['montantSignature']);
        self::assertSame('2.00', $result['retourAnnuel']);
        self::assertTrue($result['premierMoisGratuit']);
    }

    public function testDirectLeadGeneratesNoCommission(): void
    {
        $offerRepository = $this->createMock(OfferRepository::class);
        $lead = (new Lead())->setSource(LeadSource::DIRECT)->setNom('Test')->setEmail('t@test.com')->setTelephone('1')->setMachineRecherchee('Four');
        $contract = (new Contract())->setLead($lead)->setLoyerMensuel('180.00');

        $service = new CommissionCalculatorService($offerRepository);
        $result = $service->calculateForContract($contract);

        self::assertNull($result);
    }

    public function testAnnualReturnIsTwoPercentOfPurchases(): void
    {
        $offerRepository = $this->createMock(OfferRepository::class);
        $service = new CommissionCalculatorService($offerRepository);

        self::assertSame('180.00', $service->calculateRetourMontant('9000', '2'));
        self::assertSame('240.00', $service->calculateRetourMontant('12000', '2'));
        self::assertSame('400.00', $service->calculateRetourMontant('20000', '2'));
        self::assertSame('360.00', $service->calculateTotalTzanet('180', '9000', '2'));
        self::assertSame('420.00', $service->calculateTotalTzanet('180', '12000', '2'));
        self::assertSame('580.00', $service->calculateTotalTzanet('180', '20000', '2'));
    }

    public function testFixedCommissionIsUsedWhenEqualsRentIsDisabled(): void
    {
        $offer = (new Offer())
            ->setActive(true)
            ->setPremierMoisGratuit(true)
            ->setCommissionEqualsMonthlyRent(false)
            ->setCommissionSignature('100.00')
            ->setRetourAnnuel('2.00')
            ->setDateDebut(new \DateTimeImmutable('2026-01-01'));

        $offerRepository = $this->createMock(OfferRepository::class);
        $offerRepository->method('findActiveOffer')->willReturn($offer);

        $lead = (new Lead())->setSource(LeadSource::TZANET)->setNom('Test')->setEmail('t@test.com')->setTelephone('1')->setMachineRecherchee('Lave-vaisselle');
        $contract = (new Contract())->setLead($lead)->setLoyerMensuel('180.00');

        $service = new CommissionCalculatorService($offerRepository);
        $result = $service->calculateForContract($contract);

        self::assertNotNull($result);
        self::assertSame('100.00', $result['montantSignature']);
        self::assertSame('fixe', $result['mode']);
    }
}
