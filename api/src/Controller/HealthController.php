<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(EntityManagerInterface $em): JsonResponse
    {
        $checks = [
            'status' => 'ok',
            'timestamp' => (new \DateTime())->format('c'),
            'version' => '1.0.0',
        ];

        // Vérifier la connexion DB
        try {
            $em->getConnection()->executeQuery('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'error';
            $checks['status'] = 'degraded';
        }

        // Vérifier le répertoire d'exports
        $exportsDir = $this->getParameter('app.exports_dir');
        $checks['storage'] = is_dir($exportsDir) && is_writable($exportsDir) ? 'ok' : 'error';
        if ($checks['storage'] === 'error') {
            $checks['status'] = 'degraded';
        }

        $statusCode = $checks['status'] === 'ok' ? 200 : 503;

        return $this->json($checks, $statusCode);
    }
}
