<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(length: 20)]
    private ?string $canal = null; // sms, email, push

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_planifiee = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null; // planifie, envoye, ignore

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type_lien = null; // infraction, taxe

    #[ORM\Column(nullable: true)]
    private ?int $id_lien = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getCanal(): ?string
    {
        return $this->canal;
    }

    public function setCanal(string $canal): static
    {
        $this->canal = $canal;

        return $this;
    }

    public function getDatePlanifiee(): ?\DateTimeInterface
    {
        return $this->date_planifiee;
    }

    public function setDatePlanifiee(\DateTimeInterface $date_planifiee): static
    {
        $this->date_planifiee = $date_planifiee;

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

    public function getTypeLien(): ?string
    {
        return $this->type_lien;
    }

    public function setTypeLien(?string $type_lien): static
    {
        $this->type_lien = $type_lien;

        return $this;
    }

    public function getIdLien(): ?int
    {
        return $this->id_lien;
    }

    public function setIdLien(?int $id_lien): static
    {
        $this->id_lien = $id_lien;

        return $this;
    }
}
