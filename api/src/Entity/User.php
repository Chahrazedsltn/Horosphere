<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_AGENT = 'AGENT';
    public const ROLE_RH    = 'RH';
    public const ROLE_ADMIN = 'ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $motDePasse = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'AGENT'])]
    #[Assert\Choice(choices: [self::ROLE_AGENT, self::ROLE_RH, self::ROLE_ADMIN])]
    private string $role = self::ROLE_AGENT;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $departement = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateCreation = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $consentementRgpd = false;

    #[ORM\Column(type: 'integer', options: ['default' => 25])]
    private int $soldeConges = 25;

    #[ORM\Column(type: 'integer', options: ['default' => 10])]
    private int $soldeRtt = 10;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Pointage::class, orphanRemoval: true)]
    private Collection $pointages;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Demande::class, orphanRemoval: true)]
    private Collection $demandes;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Document::class, orphanRemoval: true)]
    private Collection $documents;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Alerte::class, orphanRemoval: true)]
    private Collection $alertes;

    public function __construct()
    {
        $this->pointages = new ArrayCollection();
        $this->demandes  = new ArrayCollection();
        $this->documents = new ArrayCollection();
        $this->alertes   = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (null === $this->dateCreation) {
            $this->dateCreation = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        return match ($this->role) {
            self::ROLE_ADMIN => ['ROLE_ADMIN', 'ROLE_RH', 'ROLE_USER'],
            self::ROLE_RH    => ['ROLE_RH', 'ROLE_USER'],
            default          => ['ROLE_USER'],
        };
    }

    public function getPassword(): ?string { return $this->motDePasse; }
    public function setPassword(string $password): static { $this->motDePasse = $password; return $this; }

    /** @deprecated use getPassword() */
    public function getMotDePasse(): ?string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): static { $this->motDePasse = $motDePasse; return $this; }

    public function eraseCredentials(): void {}

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function getDepartement(): ?string { return $this->departement; }
    public function setDepartement(?string $departement): static { $this->departement = $departement; return $this; }

    public function getDateCreation(): ?\DateTimeImmutable { return $this->dateCreation; }
    public function setDateCreation(\DateTimeImmutable $dateCreation): static { $this->dateCreation = $dateCreation; return $this; }

    public function isConsentementRgpd(): bool { return $this->consentementRgpd; }
    public function setConsentementRgpd(bool $consentementRgpd): static { $this->consentementRgpd = $consentementRgpd; return $this; }

    public function getSoldeConges(): int { return $this->soldeConges; }
    public function setSoldeConges(int $soldeConges): static { $this->soldeConges = $soldeConges; return $this; }

    public function getSoldeRtt(): int { return $this->soldeRtt; }
    public function setSoldeRtt(int $soldeRtt): static { $this->soldeRtt = $soldeRtt; return $this; }

    public function getFullName(): string { return trim($this->prenom . ' ' . $this->nom); }

    public function getInitials(): string
    {
        return strtoupper(substr($this->prenom ?? '', 0, 1) . substr($this->nom ?? '', 0, 1));
    }

    /** @return Collection<int, Pointage> */
    public function getPointages(): Collection { return $this->pointages; }

    /** @return Collection<int, Demande> */
    public function getDemandes(): Collection { return $this->demandes; }

    /** @return Collection<int, Document> */
    public function getDocuments(): Collection { return $this->documents; }

    /** @return Collection<int, Alerte> */
    public function getAlertes(): Collection { return $this->alertes; }
}
