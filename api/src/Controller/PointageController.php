<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PointageRepository;
use App\Service\GeofencingService;
use App\Service\PointageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/pointages', name: 'api_pointages_')]
class PointageController extends AbstractController
{
    public function __construct(
        private readonly PointageService    $pointageService,
        private readonly PointageRepository $pointageRepository,
        private readonly GeofencingService  $geofencingService,
    ) {}

    #[Route('/arriver', name: 'arriver', methods: ['POST'])]
    public function arriver(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $lat = isset($data['latitude'])  ? (float) $data['latitude']  : null;
        $lon = isset($data['longitude']) ? (float) $data['longitude'] : null;

        if (null === $lat || null === $lon) {
            return $this->json(['message' => 'Coordonnées GPS requises.'], 422);
        }

        if (!$this->geofencingService->validerCoordonnees($lat, $lon)) {
            return $this->json(['message' => 'Coordonnées GPS invalides.'], 422);
        }

        $pointage = $this->pointageService->pointer($user, $lat, $lon);

        return $this->json([
            'data'    => $this->serializePointage($pointage),
            'message' => 'Arrivée enregistrée.',
        ], 201);
    }

    #[Route('/partir', name: 'partir', methods: ['POST'])]
    public function partir(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $lat = isset($data['latitude'])  ? (float) $data['latitude']  : null;
        $lon = isset($data['longitude']) ? (float) $data['longitude'] : null;

        if (null === $lat || null === $lon) {
            return $this->json(['message' => 'Coordonnées GPS requises.'], 422);
        }

        if (!$this->geofencingService->validerCoordonnees($lat, $lon)) {
            return $this->json(['message' => 'Coordonnées GPS invalides.'], 422);
        }

        try {
            $pointage = $this->pointageService->terminerPointage($user, $lat, $lon);
        } catch (\LogicException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json([
            'data'    => $this->serializePointage($pointage),
            'message' => 'Départ enregistré.',
        ]);
    }

    #[Route('/mes-pointages', name: 'mes_pointages', methods: ['GET'])]
    public function mesPointages(#[CurrentUser] User $user): JsonResponse
    {
        $pointages = $this->pointageRepository->findByUtilisateur($user);

        return $this->json([
            'data'    => array_map([$this, 'serializePointage'], $pointages),
            'message' => 'OK',
        ]);
    }

    #[Route('/en-cours', name: 'en_cours', methods: ['GET'])]
    public function enCours(#[CurrentUser] User $user): JsonResponse
    {
        $pointage = $this->pointageRepository->findEnCoursByUtilisateur($user);

        return $this->json([
            'data'    => $pointage ? $this->serializePointage($pointage) : null,
            'message' => 'OK',
        ]);
    }

    #[Route('', name: 'liste', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function liste(Request $request): JsonResponse
    {
        $dateDebut = $request->query->get('date_debut');
        $dateFin   = $request->query->get('date_fin');
        $page      = max(1, (int) $request->query->get('page', 1));
        $perPage   = min(100, max(10, (int) $request->query->get('per_page', 50)));

        if ($dateDebut && $dateFin) {
            $pointages = $this->pointageRepository->findAllWithUsers(
                new \DateTime($dateDebut),
                new \DateTime($dateFin),
            );
        } else {
            $pointages = $this->pointageRepository->findTodayAll();
        }

        $total  = count($pointages);
        $offset = ($page - 1) * $perPage;
        $slice  = array_slice($pointages, $offset, $perPage);

        return $this->json([
            'data'       => array_map([$this, 'serializePointage'], $slice),
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
            'message' => 'OK',
        ]);
    }

    #[Route('/pause', name: 'pause', methods: ['POST'])]
    public function pause(#[CurrentUser] User $user): JsonResponse
    {
        try {
            $pointage = $this->pointageService->pauserPointage($user);
        } catch (\LogicException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json([
            'data'    => $this->serializePointage($pointage),
            'message' => 'Pause démarrée.',
        ]);
    }

    #[Route('/reprise', name: 'reprise', methods: ['POST'])]
    public function reprise(#[CurrentUser] User $user): JsonResponse
    {
        try {
            $pointage = $this->pointageService->reprendrePointage($user);
        } catch (\LogicException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json([
            'data'    => $this->serializePointage($pointage),
            'message' => 'Reprise enregistrée.',
        ]);
    }

    #[Route('/stats', name: 'stats', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function stats(): JsonResponse
    {
        $todayPointages = $this->pointageRepository->findTodayAll();
        $anomalies      = $this->pointageRepository->findAnomalies();

        $presentsAujourdhui = array_filter(
            $todayPointages,
            fn ($p) => in_array($p->getStatut(), ['EN_COURS', 'VALIDE'], true),
        );

        return $this->json([
            'data' => [
                'presents_aujourd_hui' => count($presentsAujourdhui),
                'anomalies_en_cours'   => count(array_filter($anomalies, fn ($p) => !$p->isComplete())),
                'total_pointages_jour' => count($todayPointages),
            ],
            'message' => 'OK',
        ]);
    }

    private function serializePointage(\App\Entity\Pointage $p): array
    {
        return [
            'id'              => $p->getId(),
            'dateJour'        => $p->getDateJour()?->format('Y-m-d'),
            'heureArrivee'    => $p->getHeureArrivee()?->format('Y-m-d\TH:i:s'),
            'heureDepart'     => $p->getHeureDepart()?->format('Y-m-d\TH:i:s'),
            'statut'          => $p->getStatut(),
            'coordonneesGps'  => $p->getCoordonneesGps(),
            'estAnomalie'     => $p->isEstAnomalie(),
            'dureeMinutes'       => $p->getDureeMinutes(),
            'dureesPauseMinutes' => $p->getDureesPauseMinutes(),
            'heurePauseDebut'    => $p->getHeurePauseDebut()?->format('Y-m-d\TH:i:s'),
            'site'            => $p->getSite() ? [
                'id'    => $p->getSite()->getId(),
                'nom'   => $p->getSite()->getNom(),
            ] : null,
            'utilisateur'     => $p->getUtilisateur() ? [
                'id'     => $p->getUtilisateur()->getId(),
                'prenom' => $p->getUtilisateur()->getPrenom(),
                'nom'    => $p->getUtilisateur()->getNom(),
            ] : null,
        ];
    }
}
