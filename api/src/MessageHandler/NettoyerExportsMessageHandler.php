<?php

namespace App\MessageHandler;

use App\Message\NettoyerExportsMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class NettoyerExportsMessageHandler
{
    public function __construct(
        #[Autowire('%app.exports_dir%')] private readonly string $exportsDir,
    ) {}

    public function __invoke(NettoyerExportsMessage $message): void
    {
        $limite = time() - ($message->retentionJours * 86400);

        $fichiers = glob($this->exportsDir . '/*.{csv,pdf}', GLOB_BRACE);
        if (false === $fichiers) {
            return;
        }

        foreach ($fichiers as $fichier) {
            if (is_file($fichier) && filemtime($fichier) < $limite) {
                unlink($fichier);
            }
        }
    }
}
