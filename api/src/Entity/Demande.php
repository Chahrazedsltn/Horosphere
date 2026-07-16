<?php

namespace App\Entity;

use App\Repository\DemandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DemandeRepository::class)]
#[ORM\Table(name: 'demande')]
#[ORM\HasLifecycleCallbacks]
class Demande
{
    public const TYPE_CONGE      = 'CONGE';
    public const TYPE_RTT        = 'RTT';
    public const TYPE_CORRECTION = 'CORRECTION';
    public const TYPE_ABSENCE    = 'ABSENCE';
    public const TYPE_AUTRE      = 'AUTRE';

    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_APPROUVEE  = 'APPROUVEE';
    public const STATUT_REJETEE    = 'REJETEE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'demandes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $utilisateur = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\Choice(choices: [self::TYPE_CONGE, self::TYPE_RTT, self::TYPE_CORRECTION, self::TYPE_ABSENCE, self::TYPE_AUTRE])]
    private ?string $typeDemande = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'EN_ATTENTE'])]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $justificatif = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (null === $this->dateCreation) {
            $this->dateCreation = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getUtilisateur(): ?User { return $this->utilisateur; }
    public function setUtilisateur(?User $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }

    public function getTypeDemande(): ?string { return $this->typeDemande; }
    public function setTypeDemande(string $typeDemande): static { $this->typeDemande = $typeDemande; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getMotif(): ?string { return $this->motif; }
    public function setMotif(?string $motif): static { $this->motif = $motif; return $this; }

    public function getJustificatif(): ?string { return $this->justificatif; }
    public function setJustificatif(?string $justificatif): static { $this->justificatif = $justificatif; return $this; }

    public function getDateCreation(): ?\DateTimeImmutable { return $this->dateCreation; }

    public function getDureeJours(): int
    {
        if (null === $this->dateDebut || null === $this->dateFin) {
            return 0;
        }
        return (int) $this->dateDebut->diff($this->dateFin)->days + 1;
    }

    public function isEnAttente(): bool { return self::STATUT_EN_ATTENTE === $this->statut; }
}
