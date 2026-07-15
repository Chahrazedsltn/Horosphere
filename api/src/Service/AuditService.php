<?php

namespace App\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Repository\AuditLogRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditService
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
        private readonly RequestStack       $requestStack,
    ) {}

    public function log(
        string  $action,
        ?User   $acteur = null,
        ?string $cibleType = null,
        ?int    $cibleId = null,
        ?array  $details = null,
    ): void {
        $ip = $this->requestStack->getCurrentRequest()?->getClientIp();

        $entry = new AuditLog(
            action: $action,
            utilisateurId: $acteur?->getId(),
            utilisateurEmail: self::maskEmail($acteur?->getEmail()),
            cibleType: $cibleType,
            cibleId: $cibleId,
            details: $details,
            ipAddress: $ip,
        );

        $this->auditLogRepository->save($entry);
    }

    /**
     * Masque un email pour le stockage en audit : "adm***@horosphere.fr"
     */
    public static function maskEmail(?string $email): ?string
    {
        if (null === $email || '' === $email) {
            return $email;
        }

        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return '***';
        }

        $local = $parts[0];
        $domain = $parts[1];
        $visible = min(3, strlen($local));

        return substr($local, 0, $visible) . '***@' . $domain;
    }
}
