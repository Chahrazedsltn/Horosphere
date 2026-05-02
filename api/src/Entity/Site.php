<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'site')]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private ?string $adresse = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 8)]
    #[Assert\NotNull]
    #[Assert\Range(min: -90, max: 90)]
    private ?string $latitude = null;

    #[ORM\Column(type: 'decimal', precision: 11, scale: 8)]
    #[Assert\NotNull]
    #[Assert\Range(min: -180, max: 180)]
    private ?string $longitude = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true, 'default' => 200])]
    #[Assert\Positive]
    private int $rayonMetres = 200;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $geofencingActif = true;

    #[ORM\OneToMany(mappedBy: 'site', targetEntity: Pointage::class)]
    private Collection $pointages;

    public function __construct()
    {
        $this->pointages = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(string $adresse): static { $this->adresse = $adresse; return $this; }

    public function getLatitude(): ?float { return $this->latitude !== null ? (float) $this->latitude : null; }
    public function setLatitude(float|string $latitude): static { $this->latitude = (string) $latitude; return $this; }

    public function getLongitude(): ?float { return $this->longitude !== null ? (float) $this->longitude : null; }
    public function setLongitude(float|string $longitude): static { $this->longitude = (string) $longitude; return $this; }

    public function getRayonMetres(): int { return $this->rayonMetres; }
    public function setRayonMetres(int $rayonMetres): static { $this->rayonMetres = $rayonMetres; return $this; }

    public function isGeofencingActif(): bool { return $this->geofencingActif; }
    public function setGeofencingActif(bool $geofencingActif): static { $this->geofencingActif = $geofencingActif; return $this; }

    /** @return Collection<int, Pointage> */
    public function getPointages(): Collection { return $this->pointages; }
}
