<?php

namespace App\Controller;

use App\Entity\Demande;
use App\Entity\User;
use App\Repository\DemandeRepository;
use App\Repository\PointageRepository;
use App\Service\GeofencingService;
use App\Service\JoursFeriesService;
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
        private readonly PointageService      $pointageService,
        private readonly PointageRepository   $pointageRepository,
        private readonly DemandeRepository    $demandeRepository,
        private readonly GeofencingService    $geofencingService,
        private readonly JoursFeriesService   $joursFeriesService,
    ) {}

    #[Route('/arriver', name: 'arriver', methods: ['POST'])]
    public function arriver(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $lat = isset($data['latitude'])  ? (float) $data['latitude']  : null;
        $lon = isset($data['longitude']) ? (float) $data['longitude'] : null;

        if (null === $lat || null === $lon) {
            return $this->json(['message' => 'Coordonnées GPS requises.'], 422);
        }

        if (!$this->geofencingService->validerCoordonnees($lat, $lon)) {
            return $this->json(['message' => 'Coordonnées GPS invalides.'], 422);
        }

        $pointage = $this->pointageService->pointer($user, $lat, $lon);

        $response = [
            'data'    => $this->serializePointage($pointage),
            'message' => 'Arrivée enregistrée.',
        ];

        if ($this->joursFeriesService->estJourFerie(new \DateTime())) {
            $nomJour = $this->joursFeriesService->getJourFerieNom(new \DateTime());
            $response['avertissement'] = sprintf(
                'Attention : vous pointez un jour férié (%s).',
                $nomJour,
            );
        }

        return $this->json($response, 201);
    }

    #[Route('/partir', name: 'partir', methods: ['POST'])]
    public function partir(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

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
        $dateDebut     = $request->query->get('date_debut');
        $dateFin       = $request->query->get('date_fin');
        $utilisateurId = $request->query->get('utilisateur_id');
        $page          = max(1, (int) $request->query->get('page', 1));
        $perPage       = min(100, max(10, (int) $request->query->get('per_page', 50)));

        if ($dateDebut && $dateFin) {
            try {
                $debut = new \DateTime($dateDebut);
                $fin   = new \DateTime($dateFin);
            } catch (\Exception) {
                return $this->json(['message' => 'Format de date invalide (YYYY-MM-DD).'], 422);
            }

            if ($fin->diff($debut)->days > 366) {
                return $this->json(['message' => 'La plage de dates ne peut pas dépasser 1 an.'], 422);
            }

            if ($utilisateurId) {
                $targetUser = $this->pointageRepository->getEntityManager()->find(User::class, (int) $utilisateurId);
                if (null === $targetUser) {
                    return $this->json(['message' => 'Utilisateur introuvable.'], 404);
                }
                $pointages = $this->pointageRepository->findByPeriode($targetUser, $debut, $fin);
            } else {
                $pointages = $this->pointageRepository->findAllWithUsers($debut, $fin);
            }
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

    #[Route('/manuel', name: 'manuel', methods: ['POST'])]
    public function manuel(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $dateJour      = trim((string) ($data['date_jour'] ?? ''));
        $heureArrivee  = trim((string) ($data['heure_arrivee'] ?? ''));
        $heureDepart   = trim((string) ($data['heure_depart'] ?? ''));
        $motif         = trim((string) ($data['motif'] ?? ''));

        if ('' === $dateJour || '' === $heureArrivee || '' === $heureDepart) {
            return $this->json(['message' => 'Date, heure d\'arrivée et heure de départ sont requis.'], 422);
        }

        try {
            $date   = new \DateTime($dateJour);
            $arrive = new \DateTime($dateJour . ' ' . $heureArrivee);
            $depart = new \DateTime($dateJour . ' ' . $heureDepart);
        } catch (\Exception) {
            return $this->json(['message' => 'Format de date ou heure invalide.'], 422);
        }

        $now = new \DateTime();
        if ($date > $now) {
            return $this->json(['message' => 'Impossible de créer un pointage pour une date future.'], 422);
        }

        $maxPast = (new \DateTime())->modify('-30 days');
        if ($date < $maxPast) {
            return $this->json(['message' => 'Impossible de créer un pointage pour une date de plus de 30 jours.'], 422);
        }

        if ($depart <= $arrive) {
            return $this->json(['message' => 'L\'heure de départ doit être après l\'heure d\'arrivée.'], 422);
        }

        $dureeMinutes = (int) (($depart->getTimestamp() - $arrive->getTimestamp()) / 60);
        if ($dureeMinutes > 720) {
            return $this->json(['message' => 'La durée ne peut pas dépasser 12 heures.'], 422);
        }

        // Vérifier qu'il n'y a pas déjà un pointage ce jour-là
        $existant = $this->pointageRepository->findByPeriode($user, $date, $date);
        if (!empty($existant)) {
            return $this->json(['message' => 'Un pointage existe déjà pour cette date.'], 422);
        }

        $pointage = new \App\Entity\Pointage();
        $pointage->setUtilisateur($user);
        $pointage->setDateJour($date);
        $pointage->setHeureArrivee($arrive);
        $pointage->setHeureDepart($depart);
        $pointage->setStatut(\App\Entity\Pointage::STATUT_VALIDE);
        $pointage->setCoordonneesGps('manuel');

        $this->pointageRepository->save($pointage);

        return $this->json([
            'data'    => $this->serializePointage($pointage),
            'message' => 'Pointage manuel enregistré.',
        ], 201);
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

    #[Route('/compteurs', name: 'compteurs', methods: ['GET'])]
    public function compteurs(#[CurrentUser] User $user): JsonResponse
    {
        $annee = (int) date('Y');

        $congesTotal    = $user->getSoldeConges();
        $congesPris     = $this->demandeRepository->countJoursApprouves($user, Demande::TYPE_CONGE, $annee);
        $absencesAnnee  = $this->demandeRepository->countAbsencesAnnee($user, $annee);

        return $this->json([
            'data' => [
                'conges_total'    => $congesTotal,
                'conges_pris'     => $congesPris,
                'conges_restants' => $congesTotal - $congesPris,
                'absences_annee'  => $absencesAnnee,
            ],
            'message' => 'OK',
        ]);
    }

    #[Route('/jours-feries', name: 'jours_feries', methods: ['GET'])]
    public function joursFeries(Request $request): JsonResponse
    {
        $annee = (int) $request->query->get('annee', (int) date('Y'));

        if ($annee < 2000 || $annee > 2100) {
            return $this->json(['message' => 'Année invalide (2000-2100).'], 422);
        }

        $joursFeries = $this->joursFeriesService->getJoursFeries($annee);

        $data = array_map(fn (\DateTime $date) => [
            'date' => $date->format('Y-m-d'),
            'nom'  => $this->joursFeriesService->getJourFerieNom($date),
        ], $joursFeries);

        return $this->json([
            'data'    => $data,
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
