<?php

namespace App\Entity;

use App\Repository\PaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaiementRepository::class)]
class Paiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sujet = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $date_soumission = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    // ── Référence unique du paiement (ex: PAY-XXXX) ───────────────
    #[ORM\Column(length: 50, unique: true, nullable: true)]
    private ?string $reference = null;

    // ── Mode de paiement : en_ligne | especes | cheque ────────────
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mode_paiement = null;

    // ── Montant réel du paiement ──────────────────────────────────
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3, nullable: true)]
    private ?string $montant = null;

    // ── Date effective du paiement ────────────────────────────────
    #[ORM\Column(nullable: true)]
    private ?\DateTime $date_paiement = null;

    // ── Relation : citoyen qui effectue le paiement ───────────────
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    // ── Relation : taxe associée au paiement (optionnel) ──────────
    #[ORM\ManyToOne(targetEntity: Taxe::class)]
    #[ORM\JoinColumn(name: 'taxe_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Taxe $taxe = null;

    // ── Relation : infraction associée au paiement (optionnel) ────
    #[ORM\ManyToOne(targetEntity: Infraction::class)]
    #[ORM\JoinColumn(name: 'infraction_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Infraction $infraction = null;

    // ── Relation : agent qui a encaissé le paiement (optionnel) ──
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'encaisse_par_id', referencedColumnName: 'id', nullable: true)]
    private ?User $encaissePar = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(string $sujet): static
    {
        $this->sujet = $sujet;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateSoumission(): ?\DateTime
    {
        return $this->date_soumission;
    }

    public function setDateSoumission(\DateTime $date_soumission): static
    {
        $this->date_soumission = $date_soumission;

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

    // ── Getters / Setters des relations ───────────────────────────

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTaxe(): ?Taxe
    {
        return $this->taxe;
    }

    public function setTaxe(?Taxe $taxe): static
    {
        $this->taxe = $taxe;

        return $this;
    }

    public function getInfraction(): ?Infraction
    {
        return $this->infraction;
    }

    public function setInfraction(?Infraction $infraction): static
    {
        $this->infraction = $infraction;

        return $this;
    }

    public function getEncaissePar(): ?User
    {
        return $this->encaissePar;
    }

    public function setEncaissePar(?User $encaissePar): static
    {
        $this->encaissePar = $encaissePar;

        return $this;
    }

    // ── Getters / Setters nouveaux champs ─────────────────────────

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->mode_paiement;
    }

    public function setModePaiement(?string $mode_paiement): static
    {
        $this->mode_paiement = $mode_paiement;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getDatePaiement(): ?\DateTime
    {
        return $this->date_paiement;
    }

    public function setDatePaiement(?\DateTime $date_paiement): static
    {
        $this->date_paiement = $date_paiement;

        return $this;
    }
}


