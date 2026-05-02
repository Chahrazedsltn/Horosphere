<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\Demande;
use App\Entity\User;
use App\Repository\DemandeRepository;
use App\Service\AuditService;
use App\Service\DemandeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/demandes', name: 'api_demandes_')]
class DemandeController extends AbstractController
{
    public function __construct(
        private readonly DemandeService    $demandeService,
        private readonly DemandeRepository $demandeRepository,
        private readonly AuditService      $auditService,
    ) {}

    #[Route('', name: 'liste', methods: ['GET'])]
    public function liste(#[CurrentUser] User $user): JsonResponse
    {
        if (in_array('ROLE_RH', $user->getRoles(), true)) {
            $demandes = $this->demandeRepository->findAllWithUsers();
        } else {
            $demandes = $this->demandeRepository->findByUtilisateur($user);
        }

        return $this->json([
            'data'    => array_map([$this, 'serializeDemande'], $demandes),
            'message' => 'OK',
        ]);
    }

    #[Route('/en-attente', name: 'en_attente', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function enAttente(): JsonResponse
    {
        $demandes = $this->demandeRepository->findEnAttente();

        return $this->json([
            'data'    => array_map([$this, 'serializeDemande'], $demandes),
            'message' => 'OK',
        ]);
    }

    #[Route('', name: 'creer', methods: ['POST'])]
    public function creer(Request $request, #[CurrentUser] User $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['type_demande'], $data['date_debut'], $data['date_fin'])) {
            return $this->json(['message' => 'Champs obligatoires manquants.'], 422);
        }

        try {
            $demande = $this->demandeService->soumettre($user, $data);
        } catch (\Exception $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        return $this->json([
            'data'    => $this->serializeDemande($demande),
            'message' => 'Demande soumise avec succès.',
        ], 201);
    }

    #[Route('/{id}/traiter', name: 'traiter', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_RH')]
    public function traiter(Demande $demande, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $decision = $data['decision'] ?? null;

        if (!in_array($decision, [Demande::STATUT_APPROUVEE, Demande::STATUT_REJETEE], true)) {
            return $this->json(['message' => 'Décision invalide. Valeurs acceptées : APPROUVEE, REJETEE.'], 422);
        }

        /** @var User $acteur */
        $acteur = $this->getUser();

        try {
            if (Demande::STATUT_APPROUVEE === $decision) {
                $this->demandeService->approuver($demande);
                $action = AuditLog::ACTION_DEMANDE_APPROUVEE;
            } else {
                $this->demandeService->rejeter($demande);
                $action = AuditLog::ACTION_DEMANDE_REJETEE;
            }
        } catch (\LogicException $e) {
            return $this->json(['message' => $e->getMessage()], 422);
        }

        $this->auditService->log(
            $action,
            $acteur instanceof User ? $acteur : null,
            'Demande',
            $demande->getId(),
            [
                'employe_id'   => $demande->getUtilisateur()?->getId(),
                'employe_email'=> $demande->getUtilisateur()?->getEmail(),
                'type'         => $demande->getTypeDemande(),
                'date_debut'   => $demande->getDateDebut()?->format('Y-m-d'),
                'date_fin'     => $demande->getDateFin()?->format('Y-m-d'),
            ],
        );

        return $this->json([
            'data'    => $this->serializeDemande($demande),
            'message' => 'Demande traitée.',
        ]);
    }

    private function serializeDemande(Demande $d): array
    {
        return [
            'id'           => $d->getId(),
            'typeDemande'  => $d->getTypeDemande(),
            'statut'       => $d->getStatut(),
            'dateDebut'    => $d->getDateDebut()?->format('Y-m-d'),
            'dateFin'      => $d->getDateFin()?->format('Y-m-d'),
            'dureeJours'   => $d->getDureeJours(),
            'motif'        => $d->getMotif(),
            'dateCreation' => $d->getDateCreation()?->format('Y-m-d\TH:i:s'),
            'utilisateur'  => $d->getUtilisateur() ? [
                'id'     => $d->getUtilisateur()->getId(),
                'prenom' => $d->getUtilisateur()->getPrenom(),
                'nom'    => $d->getUtilisateur()->getNom(),
                'email'  => $d->getUtilisateur()->getEmail(),
            ] : null,
        ];
    }
}
