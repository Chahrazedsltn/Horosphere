<?php

namespace App\Entity;

use App\Repository\PasswordResetTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PasswordResetTokenRepository::class)]
#[ORM\Table(name: 'password_reset_token')]
#[ORM\Index(columns: ['token'], name: 'idx_reset_token')]
class PasswordResetToken
{
    public const TTL_SECONDS = 3600; // 1 heure

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $token;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email)
    {
        $this->email     = $email;
        $this->token     = bin2hex(random_bytes(32));
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = new \DateTimeImmutable('+' . self::TTL_SECONDS . ' seconds');
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }

    public function getToken(): string { return $this->token; }

    public function getExpiresAt(): \DateTimeImmutable { return $this->expiresAt; }

    public function getUsedAt(): ?\DateTimeImmutable { return $this->usedAt; }

    public function markAsUsed(): void { $this->usedAt = new \DateTimeImmutable(); }

    public function isExpired(): bool { return new \DateTimeImmutable() > $this->expiresAt; }

    public function isUsed(): bool { return null !== $this->usedAt; }

    public function isValid(): bool { return !$this->isExpired() && !$this->isUsed(); }
}
