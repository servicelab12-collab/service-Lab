<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
#[ORM\Table(name: 'offres')]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private bool $premierMoisGratuit = true;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private string $commissionSignature = '0.00';

    /**
     * Annual return percentage (e.g. 2.00 = 2%).
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max: 100)]
    private string $retourAnnuel = '0.00';

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $dateDebut;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column]
    private bool $active = true;

    /**
     * When true, commission at signature equals one month of rent.
     */
    #[ORM\Column]
    private bool $commissionEqualsMonthlyRent = true;

    public function __construct()
    {
        $this->dateDebut = new \DateTimeImmutable('today');
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCommissionSignature(): string
    {
        return $this->commissionSignature;
    }

    public function setCommissionSignature(string|float|int $commissionSignature): static
    {
        $this->commissionSignature = number_format((float) $commissionSignature, 2, '.', '');

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

    public function getDateDebut(): \DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function isCommissionEqualsMonthlyRent(): bool
    {
        return $this->commissionEqualsMonthlyRent;
    }

    public function setCommissionEqualsMonthlyRent(bool $commissionEqualsMonthlyRent): static
    {
        $this->commissionEqualsMonthlyRent = $commissionEqualsMonthlyRent;

        return $this;
    }

    public function isCurrentlyValid(?\DateTimeImmutable $at = null): bool
    {
        if (!$this->active) {
            return false;
        }

        $at ??= new \DateTimeImmutable('today');

        if ($at < $this->dateDebut) {
            return false;
        }

        if ($this->dateFin !== null && $at > $this->dateFin) {
            return false;
        }

        return true;
    }
}
