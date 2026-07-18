<?php

namespace App\Controller;

use App\Entity\Site;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/sites', name: 'api_sites_')]
class SiteController extends AbstractController
{
    public function __construct(
        private readonly SiteRepository         $siteRepository,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface     $validator,
    ) {}

    #[Route('', name: 'liste', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function liste(): JsonResponse
    {
        $sites = $this->siteRepository->findAll();

        return $this->json([
            'data'    => array_map([$this, 'serializeSite'], $sites),
            'message' => 'OK',
        ]);
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function detail(Site $site): JsonResponse
    {
        return $this->json([
            'data'    => $this->serializeSite($site),
            'message' => 'OK',
        ]);
    }

    #[Route('', name: 'creer', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function creer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['rayon_metres'])) {
            $rayon = (int) $data['rayon_metres'];
            if ($rayon < 10 || $rayon > 50000) {
                return $this->json(['message' => 'Le rayon doit être compris entre 10 et 50 000 mètres.'], 422);
            }
        }

        $site = new Site();
        $this->hydrateSite($site, $data);

        $errors = $this->validator->validate($site);
        if (count($errors) > 0) {
            return $this->json(['message' => (string) $errors], 422);
        }

        $this->siteRepository->save($site);

        return $this->json([
            'data'    => $this->serializeSite($site),
            'message' => 'Site créé.',
        ], 201);
    }

    #[Route('/{id}', name: 'modifier', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function modifier(Site $site, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['rayon_metres'])) {
            $rayon = (int) $data['rayon_metres'];
            if ($rayon < 10 || $rayon > 50000) {
                return $this->json(['message' => 'Le rayon doit être compris entre 10 et 50 000 mètres.'], 422);
            }
        }

        $this->hydrateSite($site, $data);

        $errors = $this->validator->validate($site);
        if (count($errors) > 0) {
            return $this->json(['message' => (string) $errors], 422);
        }

        $this->em->flush();

        return $this->json([
            'data'    => $this->serializeSite($site),
            'message' => 'Site mis à jour.',
        ]);
    }

    #[Route('/{id}', name: 'supprimer', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function supprimer(Site $site): JsonResponse
    {
        $this->siteRepository->remove($site);

        return $this->json(['message' => 'Site supprimé.']);
    }

    private function hydrateSite(Site $site, array $data): void
    {
        if (isset($data['nom']))             $site->setNom($data['nom']);
        if (isset($data['adresse']))         $site->setAdresse($data['adresse']);
        if (isset($data['latitude']))        $site->setLatitude((float) $data['latitude']);
        if (isset($data['longitude']))       $site->setLongitude((float) $data['longitude']);
        if (isset($data['rayon_metres']))    $site->setRayonMetres((int) $data['rayon_metres']);
        if (isset($data['geofencing_actif'])) $site->setGeofencingActif((bool) $data['geofencing_actif']);
    }

    private function serializeSite(Site $s): array
    {
        return [
            'id'              => $s->getId(),
            'nom'             => $s->getNom(),
            'adresse'         => $s->getAdresse(),
            'latitude'        => $s->getLatitude(),
            'longitude'       => $s->getLongitude(),
            'rayonMetres'     => $s->getRayonMetres(),
            'geofencingActif' => $s->isGeofencingActif(),
        ];
    }
}
