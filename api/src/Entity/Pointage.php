<?php

namespace App\Entity;

use App\Repository\PointageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PointageRepository::class)]
#[ORM\Table(name: 'pointage')]
#[ORM\HasLifecycleCallbacks]
class Pointage
{
    public const STATUT_EN_COURS  = 'EN_COURS';
    public const STATUT_EN_PAUSE  = 'EN_PAUSE';
    public const STATUT_VALIDE    = 'VALIDE';
    public const STATUT_HORS_ZONE = 'HORS_ZONE';
    public const STATUT_ANOMALIE  = 'ANOMALIE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pointages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'pointages')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Site $site = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateJour = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $heureArrivee = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $heureDepart = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'EN_COURS'])]
    private string $statut = self::STATUT_EN_COURS;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $coordonneesGps = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $estAnomalie = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $heurePauseDebut = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0, 'unsigned' => true])]
    private int $dureesPauseMinutes = 0;

    #[ORM\OneToMany(mappedBy: 'pointage', targetEntity: Alerte::class)]
    private Collection $alertes;

    public function __construct()
    {
        $this->alertes = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (null === $this->heureArrivee) {
            $this->heureArrivee = new \DateTime();
        }
        if (null === $this->dateJour) {
            $this->dateJour = new \DateTime('today');
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getUtilisateur(): ?User { return $this->utilisateur; }
    public function setUtilisateur(?User $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }

    public function getSite(): ?Site { return $this->site; }
    public function setSite(?Site $site): static { $this->site = $site; return $this; }

    public function getDateJour(): ?\DateTimeInterface { return $this->dateJour; }
    public function setDateJour(\DateTimeInterface $dateJour): static { $this->dateJour = $dateJour; return $this; }

    public function getHeureArrivee(): ?\DateTimeInterface { return $this->heureArrivee; }
    public function setHeureArrivee(\DateTimeInterface $heureArrivee): static { $this->heureArrivee = $heureArrivee; return $this; }

    public function getHeureDepart(): ?\DateTimeInterface { return $this->heureDepart; }
    public function setHeureDepart(?\DateTimeInterface $heureDepart): static { $this->heureDepart = $heureDepart; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getCoordonneesGps(): ?string { return $this->coordonneesGps; }
    public function setCoordonneesGps(?string $coordonneesGps): static { $this->coordonneesGps = $coordonneesGps; return $this; }

    public function isEstAnomalie(): bool { return $this->estAnomalie; }
    public function setEstAnomalie(bool $estAnomalie): static { $this->estAnomalie = $estAnomalie; return $this; }

    public function getDureeMinutes(): ?int
    {
        if (null === $this->heureArrivee || null === $this->heureDepart) {
            return null;
        }
        return (int) (($this->heureDepart->getTimestamp() - $this->heureArrivee->getTimestamp()) / 60);
    }

    public function getHeurePauseDebut(): ?\DateTimeInterface { return $this->heurePauseDebut; }
    public function setHeurePauseDebut(?\DateTimeInterface $h): static { $this->heurePauseDebut = $h; return $this; }

    public function getDureesPauseMinutes(): int { return $this->dureesPauseMinutes; }
    public function setDureesPauseMinutes(int $m): static { $this->dureesPauseMinutes = $m; return $this; }

    public function isComplete(): bool { return null !== $this->heureDepart; }
    public function isEnCours(): bool { return self::STATUT_EN_COURS === $this->statut; }
    public function isEnPause(): bool { return self::STATUT_EN_PAUSE === $this->statut; }

    /** @return Collection<int, Alerte> */
    public function getAlertes(): Collection { return $this->alertes; }
}
