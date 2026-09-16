<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\CommissionStatus;
use App\Repository\CommissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommissionRepository::class)]
#[ORM\Table(name: 'commissions')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'IDX_COMMISSIONS_STATUT', columns: ['statut'])]
class Commission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'commission', targetEntity: Contract::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Contract $contract = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $montantSignature = '0.00';

    /**
     * Annual return rate percentage stored at signature (e.g. 2.00 = 2%).
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\Range(min: 0, max: 100)]
    private string $retourAnnuel = '0.00';

    /**
     * Total annual client purchases used to compute the 2% return.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $achatsAnnuel = '0.00';

    #[ORM\Column(length: 20, enumType: CommissionStatus::class)]
    private CommissionStatus $statut = CommissionStatus::A_PAYER;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $datePaiement = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        // createdAt is initialized in the constructor (fixtures may override it).
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): static
    {
        $this->contract = $contract;

        return $this;
    }

    public function getMontantSignature(): string
    {
        return $this->montantSignature;
    }

    public function setMontantSignature(string|float|int $montantSignature): static
    {
        $this->montantSignature = number_format((float) $montantSignature, 2, '.', '');

        return $this;
    }

    public function getRetourAnnuel(): string
    {
        return $this->retourAnnuel;
    }

    public function setRetourAnnuel(string|float|int $retourAnnuel): static
    {
        $this->retourAnnuel = number_format((float) $retourAnnuel, 2, '.', '');

        return $this;
    }

    public function getAchatsAnnuel(): string
    {
        return $this->achatsAnnuel;
    }

    public function setAchatsAnnuel(string|float|int $achatsAnnuel): static
    {
        $this->achatsAnnuel = number_format((float) $achatsAnnuel, 2, '.', '');

        return $this;
    }

    /**
     * Dollar amount of the annual return = achats × (taux / 100).
     */
    public function getMontantRetour(): string
    {
        $amount = (float) $this->achatsAnnuel * ((float) $this->retourAnnuel / 100);

        return number_format($amount, 2, '.', '');
    }

    /**
     * Total TZANET gain = commission signature + retour 2% on purchases.
     */
    public function getTotalTzanet(): string
    {
        $total = (float) $this->montantSignature + (float) $this->getMontantRetour();

        return number_format($total, 2, '.', '');
    }

    public function getStatut(): CommissionStatus
    {
        return $this->statut;
    }

    public function setStatut(CommissionStatus $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeImmutable
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeImmutable $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markAsPaid(?\DateTimeImmutable $date = null): static
    {
        $this->statut = CommissionStatus::PAYEE;
        $this->datePaiement = $date ?? new \DateTimeImmutable('today');

        return $this;
    }
}
