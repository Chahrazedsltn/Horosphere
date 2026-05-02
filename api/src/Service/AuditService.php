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
            utilisateurEmail: $acteur?->getEmail(),
            cibleType: $cibleType,
            cibleId: $cibleId,
            details: $details,
            ipAddress: $ip,
        );

        $this->auditLogRepository->save($entry);
    }
}
