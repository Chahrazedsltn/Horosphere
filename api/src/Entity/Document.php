<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'document')]
#[ORM\HasLifecycleCallbacks]
class Document
{
    public const TYPE_CSV = 'CSV';
    public const TYPE_PDF = 'PDF';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $utilisateur = null;

    #[ORM\Column(type: 'string', length: 10)]
    private ?string $typeDocument = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $cheminFichier = null;

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

    public function getTypeDocument(): ?string { return $this->typeDocument; }
    public function setTypeDocument(string $typeDocument): static { $this->typeDocument = $typeDocument; return $this; }

    public function getCheminFichier(): ?string { return $this->cheminFichier; }
    public function setCheminFichier(string $cheminFichier): static { $this->cheminFichier = $cheminFichier; return $this; }

    public function getDateCreation(): ?\DateTimeImmutable { return $this->dateCreation; }

    public function getFileName(): string
    {
        return basename($this->cheminFichier ?? '');
    }
}
