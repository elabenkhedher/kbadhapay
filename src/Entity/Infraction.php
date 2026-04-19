<?php

namespace App\Entity;

use App\Repository\InfractionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InfractionRepository::class)]
class Infraction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type_infraction = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3)]
    private ?string $montant_amende = null;

    #[ORM\Column(length: 255)]
    private ?string $lieu = null;

    #[ORM\Column(length: 20)]
    private ?string $plaque_immat = null;

    #[ORM\Column]
    private ?\DateTime $date_infraction = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_echeance = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    // ── Relation : citoyen verbalisé ──────────────────────────────
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    // ── Relation : agent de police constatateur ───────────────────
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'agent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $agent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeInfraction(): ?string
    {
        return $this->type_infraction;
    }

    public function setTypeInfraction(string $type_infraction): static
    {
        $this->type_infraction = $type_infraction;

        return $this;
    }

    public function getMontantAmende(): ?string
    {
        return $this->montant_amende;
    }

    public function setMontantAmende(string|float|int $montant_amende): static
    {
        $this->montant_amende = (string) $montant_amende;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getPlaqueImmat(): ?string
    {
        return $this->plaque_immat;
    }

    public function setPlaqueImmat(string $plaque_immat): static
    {
        $this->plaque_immat = $plaque_immat;

        return $this;
    }

    public function getDateInfraction(): ?\DateTime
    {
        return $this->date_infraction;
    }

    public function setDateInfraction(\DateTime $date_infraction): static
    {
        $this->date_infraction = $date_infraction;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    // ── Getters / Setters relations ───────────────────────────────

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getDateEcheance(): ?\DateTimeInterface
    {
        return $this->date_echeance;
    }

    public function setDateEcheance(?\DateTimeInterface $date_echeance): static
    {
        $this->date_echeance = $date_echeance;

        return $this;
    }

    public function getPenalite(): float
    {
        if ($this->statut === 'paye' || !$this->date_echeance) {
            return 0.0;
        }

        $now = new \DateTime();
        if ($now > $this->date_echeance) {
            $diff = $now->diff($this->date_echeance)->days;
            $montant = (float) $this->montant_amende;
            
            if ($diff > 7) {
                return $montant * 0.20; // 20% after 7 days
            } elseif ($diff > 0) {
                return $montant * 0.10; // 10% after 1 day
            }
        }

        return 0.0;
    }

    public function getMontantTotal(): float
    {
        return (float) $this->montant_amende + $this->getPenalite();
    }
}

