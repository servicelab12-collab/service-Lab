<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\LeadCanal;
use App\Enum\LeadSource;
use App\Enum\LeadStatus;
use App\Repository\LeadRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LeadRepository::class)]
#[ORM\Table(name: 'leads')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'IDX_LEADS_SOURCE', columns: ['source'])]
#[ORM\Index(name: 'IDX_LEADS_STATUS', columns: ['status'])]
#[ORM\Index(name: 'IDX_LEADS_CREATED_AT', columns: ['created_at'])]
class Lead
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $nom = '';

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    private string $telephone = '';

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 150)]
    private string $email = '';

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private string $machineRecherchee = '';

    #[ORM\Column(length: 20, enumType: LeadSource::class)]
    private LeadSource $source = LeadSource::TZANET;

    #[ORM\Column(length: 30, enumType: LeadCanal::class)]
    private LeadCanal $canal = LeadCanal::SITE_ACCUEIL;

    #[ORM\Column(length: 30, enumType: LeadStatus::class)]
    private LeadStatus $status = LeadStatus::NEW;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    private ?string $codePromo = null;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(mappedBy: 'lead', targetEntity: Contract::class)]
    private ?Contract $contract = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ('' === $this->reference) {
            $prefix = $this->source === LeadSource::TZANET ? 'REF-TZ' : 'REF-DR';
            $this->reference = sprintf('%s-%04d', $prefix, random_int(1000, 9999));
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = strtolower(trim($email));

        return $this;
    }

    public function getMachineRecherchee(): string
    {
        return $this->machineRecherchee;
    }

    public function setMachineRecherchee(string $machineRecherchee): static
    {
        $this->machineRecherchee = $machineRecherchee;

        return $this;
    }

    public function getSource(): LeadSource
    {
        return $this->source;
    }

    public function setSource(LeadSource $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getCanal(): LeadCanal
    {
        return $this->canal;
    }

    public function setCanal(LeadCanal $canal): static
    {
        $this->canal = $canal;

        return $this;
    }

    public function getStatus(): LeadStatus
    {
        return $this->status;
    }

    public function setStatus(LeadStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCodePromo(): ?string
    {
        return $this->codePromo;
    }

    public function setCodePromo(?string $codePromo): static
    {
        $this->codePromo = $codePromo;

        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function setContract(?Contract $contract): static
    {
        if ($contract === null && $this->contract !== null) {
            $this->contract->setLead(null);
        }

        if ($contract !== null && $contract->getLead() !== $this) {
            $contract->setLead($this);
        }

        $this->contract = $contract;

        return $this;
    }

    /**
     * Clear inverse relation after the owning Contract row is deleted.
     */
    public function detachContract(): void
    {
        $this->contract = null;
    }

    public function isFromTzanet(): bool
    {
        return $this->source === LeadSource::TZANET;
    }
}
