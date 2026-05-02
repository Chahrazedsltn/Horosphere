<?php

namespace App\MessageHandler;

use App\Message\VerifierOubliDepartMessage;
use App\Service\AlerteService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class VerifierOubliDepartMessageHandler
{
    public function __construct(
        private readonly AlerteService  $alerteService,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(VerifierOubliDepartMessage $message): void
    {
        $this->logger->info('[Scheduler] Début vérification oublis de départ.');

        try {
            $count = $this->alerteService->verifierOubliDepart();
            $this->logger->info('[Scheduler] Vérification terminée.', ['alertes_créées' => $count]);
        } catch (\Throwable $e) {
            $this->logger->error('[Scheduler] Erreur vérification oubli départ.', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
