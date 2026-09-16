<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Lead;
use App\Enum\ContractStatus;
use App\Enum\LeadCanal;
use App\Enum\LeadSource;
use App\Enum\LeadStatus;
use Doctrine\ORM\EntityManagerInterface;

final class LeadManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function createLead(
        string $nom,
        string $telephone,
        string $email,
        string $machineRecherchee,
        LeadSource $source,
        LeadCanal $canal,
        ?string $codePromo = null,
    ): Lead {
        $lead = (new Lead())
            ->setNom($nom)
            ->setTelephone($telephone)
            ->setEmail($email)
            ->setMachineRecherchee($machineRecherchee)
            ->setSource($source)
            ->setCanal($canal)
            ->setStatus(LeadStatus::NEW)
            ->setCodePromo($codePromo);

        if ($source === LeadSource::TZANET && $codePromo === null) {
            $lead->setCodePromo('TZANET');
        }

        $this->entityManager->persist($lead);
        $this->entityManager->flush();

        return $lead;
    }

    public function updateStatus(Lead $lead, LeadStatus $status): Lead
    {
        $lead->setStatus($status);

        if ($status === LeadStatus::CONTRACT_CANCELLED && $lead->getContract() !== null) {
            $lead->getContract()->setStatut(ContractStatus::CANCELLED);
        }

        $this->entityManager->flush();

        return $lead;
    }
}
