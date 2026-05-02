<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['action'], name: 'idx_audit_action')]
#[ORM\Index(columns: ['utilisateur_id'], name: 'idx_audit_user')]
#[ORM\Index(columns: ['created_at'], name: 'idx_audit_date')]
class AuditLog
{
    // Actions standards
    public const ACTION_DEMANDE_APPROUVEE  = 'demande.approuvee';
    public const ACTION_DEMANDE_REJETEE    = 'demande.rejetee';
    public const ACTION_POINTAGE_MODIFIE   = 'pointage.modifie';
    public const ACTION_USER_CREE          = 'user.cree';
    public const ACTION_USER_MODIFIE       = 'user.modifie';
    public const ACTION_USER_SUPPRIME      = 'user.supprime';
    public const ACTION_RESET_PASSWORD     = 'auth.reset_password';
    public const ACTION_PAUSE_DEBUT        = 'pointage.pause_debut';
    public const ACTION_PAUSE_FIN          = 'pointage.pause_fin';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 60)]
    private string $action;

    /** Auteur de l'action (null = action système) */
    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $utilisateurId = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $utilisateurEmail = null;

    /** Type de l'entité cible (ex: "Demande", "Pointage") */
    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    private ?string $cibleType = null;

    /** ID de l'entité cible */
    #[ORM\Column(type: 'integer', nullable: true, options: ['unsigned' => true])]
    private ?int $cibleId = null;

    /** Données contextuelles sérialisées en JSON */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $details = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string  $action,
        ?int    $utilisateurId = null,
        ?string $utilisateurEmail = null,
        ?string $cibleType = null,
        ?int    $cibleId = null,
        ?array  $details = null,
        ?string $ipAddress = null,
    ) {
        $this->action           = $action;
        $this->utilisateurId    = $utilisateurId;
        $this->utilisateurEmail = $utilisateurEmail;
        $this->cibleType        = $cibleType;
        $this->cibleId          = $cibleId;
        $this->details          = $details;
        $this->ipAddress        = $ipAddress;
        $this->createdAt        = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getAction(): string { return $this->action; }
    public function getUtilisateurId(): ?int { return $this->utilisateurId; }
    public function getUtilisateurEmail(): ?string { return $this->utilisateurEmail; }
    public function getCibleType(): ?string { return $this->cibleType; }
    public function getCibleId(): ?int { return $this->cibleId; }
    public function getDetails(): ?array { return $this->details; }
    public function getIpAddress(): ?string { return $this->ipAddress; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
