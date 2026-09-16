<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ContractStatus;
use App\Repository\ContractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\Table(name: 'contrats')]
#[ORM\HasLifecycleCallbacks]
class Contract
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'contract', targetEntity: Lead::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Lead $lead = null;

    /**
     * Partner offer applied at signature (snapshot of commercial rules).
     */
    #[ORM\ManyToOne(targetEntity: Offer::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Offer $offer = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private string $loyerMensuel = '0.00';

    #[ORM\Column]
    #[Assert\Positive]
    private int $dureeMois = 12;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $dateSignature;

    #[ORM\Column(length: 20, enumType: ContractStatus::class)]
    private ContractStatus $statut = ContractStatus::ACTIVE;

    /**
     * Snapshot: first month free when signed via TZANET offer.
     */
    #[ORM\Column]
    private bool $premierMoisGratuit = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToOne(mappedBy: 'contract', targetEntity: Commission::class, cascade: ['persist', 'remove'])]
    private ?Commission $commission = null;

    public function __construct()
    {
        $this->dateSignature = new \DateTimeImmutable('today');
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

    public function getLead(): ?Lead
    {
        return $this->lead;
    }

    public function setLead(?Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function setOffer(?Offer $offer): static
    {
        $this->offer = $offer;

        return $this;
    }

    public function getLoyerMensuel(): string
    {
        return $this->loyerMensuel;
    }

    public function setLoyerMensuel(string|float|int $loyerMensuel): static
    {
        $this->loyerMensuel = number_format((float) $loyerMensuel, 2, '.', '');

        return $this;
    }

    public function getDureeMois(): int
    {
        return $this->dureeMois;
    }

    public function setDureeMois(int $dureeMois): static
    {
        $this->dureeMois = $dureeMois;

        return $this;
    }

    public function getDateSignature(): \DateTimeImmutable
    {
        return $this->dateSignature;
    }

    public function setDateSignature(\DateTimeImmutable $dateSignature): static
    {
        $this->dateSignature = $dateSignature;

        return $this;
    }

    public function getStatut(): ContractStatus
    {
        return $this->statut;
    }

    public function setStatut(ContractStatus $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isPremierMoisGratuit(): bool
    {
        return $this->premierMoisGratuit;
    }

    public function setPremierMoisGratuit(bool $premierMoisGratuit): static
    {
        $this->premierMoisGratuit = $premierMoisGratuit;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCommission(): ?Commission
    {
        return $this->commission;
    }

    public function setCommission(?Commission $commission): static
    {
        if ($commission === null && $this->commission !== null) {
            $this->commission->setContract(null);
        }

        if ($commission !== null && $commission->getContract() !== $this) {
            $commission->setContract($this);
        }

        $this->commission = $commission;

        return $this;
    }
}
