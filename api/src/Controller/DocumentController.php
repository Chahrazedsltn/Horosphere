<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Service\ExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_documents_')]
class DocumentController extends AbstractController
{
    public function __construct(
        private readonly ExportService      $exportService,
        private readonly DocumentRepository $documentRepository,
        #[Autowire('%app.exports_dir%')] private readonly string $exportsDir,
    ) {}

    #[Route('/documents', name: 'liste', methods: ['GET'])]
    public function liste(#[CurrentUser] User $user): JsonResponse
    {
        $documents = $this->documentRepository->findByUtilisateur($user);

        return $this->json([
            'data'    => array_map([$this, 'serializeDocument'], $documents),
            'message' => 'OK',
        ]);
    }

    #[Route('/documents/{id}/download', name: 'download', methods: ['GET'])]
    public function download(Document $document, #[CurrentUser] User $user): BinaryFileResponse|JsonResponse
    {
        if ($document->getUtilisateur()?->getId() !== $user->getId()
            && !in_array('ROLE_RH', $user->getRoles(), true)) {
            return $this->json(['message' => 'Accès refusé.'], 403);
        }

        $filepath    = $document->getCheminFichier();
        $realPath    = realpath((string) $filepath);
        $exportsReal = realpath($this->exportsDir);

        if (false === $realPath || false === $exportsReal
            || !str_starts_with($realPath, $exportsReal . DIRECTORY_SEPARATOR)
        ) {
            return $this->json(['message' => 'Fichier introuvable.'], 404);
        }

        $response = new BinaryFileResponse($realPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getFileName(),
        );

        return $response;
    }

    #[Route('/exports/csv', name: 'export_csv', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function exportCsv(Request $request): JsonResponse
    {
        return $this->export($request, Document::TYPE_CSV);
    }

    #[Route('/exports/pdf', name: 'export_pdf', methods: ['POST'])]
    #[IsGranted('ROLE_RH')]
    public function exportPdf(Request $request): JsonResponse
    {
        return $this->export($request, Document::TYPE_PDF);
    }

    private function export(Request $request, string $type): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['date_debut'], $data['date_fin'])) {
            return $this->json(['message' => 'date_debut et date_fin sont requis.'], 422);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $user = $currentUser;

        if (isset($data['utilisateur_id'])) {
            $em = $this->container->get('doctrine.orm.entity_manager');
            $targetUser = $em->find(User::class, (int) $data['utilisateur_id']);
            if (null === $targetUser) {
                return $this->json(['message' => 'Utilisateur introuvable.'], 404);
            }
            $user = $targetUser;
        }

        try {
            $debut = new \DateTime($data['date_debut']);
            $fin   = new \DateTime($data['date_fin']);
        } catch (\Exception) {
            return $this->json(['message' => 'Format de date invalide (YYYY-MM-DD).'], 422);
        }

        $document = match ($type) {
            Document::TYPE_CSV => $this->exportService->genererCsv($user, $debut, $fin),
            Document::TYPE_PDF => $this->exportService->genererPdf($user, $debut, $fin),
            default            => throw new \InvalidArgumentException('Type inconnu'),
        };

        return $this->json([
            'data'    => $this->serializeDocument($document),
            'message' => sprintf('%s généré avec succès.', $type),
        ], 201);
    }

    private function serializeDocument(Document $d): array
    {
        return [
            'id'           => $d->getId(),
            'typeDocument' => $d->getTypeDocument(),
            'fileName'     => $d->getFileName(),
            'dateCreation' => $d->getDateCreation()?->format('Y-m-d\TH:i:s'),
            'downloadUrl'  => '/api/documents/' . $d->getId() . '/download',
        ];
    }
}
