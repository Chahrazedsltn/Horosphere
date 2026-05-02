<?php

namespace App\Entity;

use App\Repository\AlerteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlerteRepository::class)]
#[ORM\Table(name: 'alerte')]
#[ORM\HasLifecycleCallbacks]
class Alerte
{
    public const TYPE_OUBLI_DEPART   = 'OUBLI_DEPART';
    public const TYPE_HORS_ZONE      = 'HORS_ZONE';
    public const TYPE_ECART_HORAIRE  = 'ECART_HORAIRE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'alertes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'alertes')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Pointage $pointage = null;

    #[ORM\Column(type: 'string', length: 30)]
    private ?string $typeAlerte = null;

    #[ORM\Column(type: 'text')]
    private ?string $message = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $estLue = false;

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

    public function getPointage(): ?Pointage { return $this->pointage; }
    public function setPointage(?Pointage $pointage): static { $this->pointage = $pointage; return $this; }

    public function getTypeAlerte(): ?string { return $this->typeAlerte; }
    public function setTypeAlerte(string $typeAlerte): static { $this->typeAlerte = $typeAlerte; return $this; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }

    public function getDateCreation(): ?\DateTimeImmutable { return $this->dateCreation; }

    public function isEstLue(): bool { return $this->estLue; }
    public function setEstLue(bool $estLue): static { $this->estLue = $estLue; return $this; }

    public function marquerLue(): void { $this->estLue = true; }

    public function isRecente(): bool
    {
        if (null === $this->dateCreation) {
            return false;
        }
        return $this->dateCreation > new \DateTimeImmutable('-24 hours');
    }
}
