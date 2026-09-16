<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Commission;
use App\Entity\Contract;
use App\Entity\Lead;
use App\Entity\Offer;
use App\Entity\User;
use App\Enum\CommissionStatus;
use App\Enum\ContractStatus;
use App\Enum\LeadCanal;
use App\Enum\LeadSource;
use App\Enum\LeadStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setNom('Admin ServiceLab')
            ->setEmail('admin@servicelab.ca')
            ->setRoles([User::ROLE_ADMIN]);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin123!'));
        $manager->persist($admin);

        $commercial = (new User())
            ->setNom('Commercial ServiceLab')
            ->setEmail('commercial@servicelab.ca')
            ->setRoles([User::ROLE_COMMERCIAL]);
        $commercial->setPassword($this->passwordHasher->hashPassword($commercial, 'Commercial123!'));
        $manager->persist($commercial);

        $offer = (new Offer())
            ->setPremierMoisGratuit(true)
            ->setCommissionSignature('180.00')
            ->setCommissionEqualsMonthlyRent(true)
            ->setRetourAnnuel('2.00')
            ->setDateDebut(new \DateTimeImmutable('2026-01-01'))
            ->setDateFin(null)
            ->setActive(true);
        $manager->persist($offer);

        $samples = [
            // nom, tel, email, machine, status, when, loyer, withContract, paid, achatsAnnuel
            ['Karim Haddad', '438-555-0108', 'karim@mezzebar.ca', 'Lave-vaisselle sous comptoir haute température (240 V - 40 Amp)', LeadStatus::CONTRACT_SIGNED, '-5 months', 180.00, true, false, 9000.00],
            ['Jean Dupuis', '819-555-0106', 'jean@aubergeOutaouais.ca', 'Lave-vaisselle vertical basse température (120 V - 20 Amp)', LeadStatus::ACTIVE_CLIENT, '-4 months', 180.00, true, false, 12000.00],
            ['Mohamed Said', '514-555-0111', 'mohamed@cuisinePro.ca', 'Lave-vaisselle vertical haute température (240 V - 40 Amp)', LeadStatus::CONTRACT_SIGNED, '-3 months', 180.00, true, true, 20000.00],
            ['Sophie Gagnon', '418-555-0103', 'sophie@hotelcapitale.ca', 'Lave-vaisselle sous comptoir haute température (240 V - 40 Amp)', LeadStatus::CONTRACT_SIGNED, '-2 months', 180.00, true, false, 7500.00],
            ['Marc Tremblay', '514-555-0102', 'marc@cafequebec.ca', 'Lave-verre rotatif (240 V - 40 Amp)', LeadStatus::OFFER_SENT, '-4 months', null, false, false, null],
            ['Nadia Benali', '514-555-0105', 'nadia@restoPlateau.ca', 'Lave-vaisselle sous comptoir basse température (120 V - 20 Amp)', LeadStatus::NEED_ANALYSIS, '-3 months', null, false, false, null],
            ['Camille Roy', '514-555-0107', 'camille@foodlab.ca', 'Lave-verre rotatif (240 V - 40 Amp)', LeadStatus::NEW, '-1 months', null, false, false, null],
            ['Isabelle Côté', '514-555-0109', 'isabelle@cuisinePro.ca', 'Lave-vaisselle vertical haute température (240 V - 40 Amp)', LeadStatus::LOST, '-10 days', null, false, false, null],
            ['Antoine Pelletier', '581-555-0110', 'antoine@bistroLevis.ca', 'Lave-vaisselle sous comptoir basse température (120 V - 20 Amp)', LeadStatus::NEW, '-2 days', null, false, false, null],
            ['Josée Bouchard', '514-555-0101', 'josee@bistro.ca', 'Lave-vaisselle vertical basse température (120 V - 20 Amp)', LeadStatus::CONTACTED, '-20 days', null, false, false, null],
        ];

        foreach ($samples as $i => [$nom, $tel, $email, $machine, $status, $when, $loyer, $withContract, $paid, $achats]) {
            $lead = (new Lead())
                ->setNom($nom)
                ->setTelephone($tel)
                ->setEmail($email)
                ->setMachineRecherchee($machine)
                ->setSource(LeadSource::TZANET)
                ->setCanal(LeadCanal::TZANET)
                ->setStatus($status)
                ->setCodePromo('TZANET')
                ->setReference(sprintf('REF-TZ-%04d', 2000 + $i));

            $createdAt = new \DateTimeImmutable($when);
            $this->setPrivate($lead, 'createdAt', $createdAt);
            $this->setPrivate($lead, 'updatedAt', $createdAt);

            $manager->persist($lead);

            if ($withContract && $loyer !== null) {
                $contract = (new Contract())
                    ->setLead($lead)
                    ->setLoyerMensuel($loyer)
                    ->setDureeMois(12)
                    ->setDateSignature($createdAt)
                    ->setPremierMoisGratuit(true)
                    ->setOffer($offer)
                    ->setStatut(ContractStatus::ACTIVE);
                $this->setPrivate($contract, 'createdAt', $createdAt);
                $lead->setContract($contract);

                $commission = (new Commission())
                    ->setMontantSignature($loyer)
                    ->setRetourAnnuel('2.00')
                    ->setAchatsAnnuel($achats ?? 0);
                if ($paid) {
                    $commission->markAsPaid($createdAt->modify('+7 days'));
                } else {
                    $commission->setStatut(CommissionStatus::A_PAYER);
                }
                $this->setPrivate($commission, 'createdAt', $createdAt);
                $contract->setCommission($commission);

                $manager->persist($contract);
            }
        }

        $manager->flush();
    }

    private function setPrivate(object $object, string $property, mixed $value): void
    {
        $ref = new \ReflectionProperty($object, $property);
        $ref->setValue($object, $value);
    }
}
