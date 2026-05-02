<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\PointageRepository;
use App\Repository\UserRepository;
use App\Repository\DemandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository              $userRepository,
        private readonly PointageRepository         $pointageRepository,
        private readonly DemandeRepository          $demandeRepository,
        private readonly EntityManagerInterface     $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface         $validator,
    ) {}

    #[Route('', name: 'liste', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function liste(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        return $this->json([
            'data'    => array_map([$this, 'serializeUser'], $users),
            'message' => 'OK',
        ]);
    }

    #[Route('/{id}', name: 'detail', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function detail(User $user): JsonResponse
    {
        return $this->json([
            'data'    => $this->serializeUser($user),
            'message' => 'OK',
        ]);
    }

    #[Route('', name: 'creer', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function creer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['prenom'], $data['nom'])) {
            return $this->json(['message' => 'Champs obligatoires : email, password, prenom, nom.'], 422);
        }

        $user = new User();
        $this->hydrateUser($user, $data);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setMotDePasse($hashedPassword);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(['message' => (string) $errors], 422);
        }

        $this->userRepository->save($user);

        return $this->json([
            'data'    => $this->serializeUser($user),
            'message' => 'Utilisateur créé.',
        ], 201);
    }

    #[Route('/{id}', name: 'modifier', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_ADMIN')]
    public function modifier(User $user, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->hydrateUser($user, $data);

        if (isset($data['password']) && !empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setMotDePasse($hashedPassword);
        }

        $this->em->flush();

        return $this->json([
            'data'    => $this->serializeUser($user),
            'message' => 'Utilisateur mis à jour.',
        ]);
    }

    #[Route('/{id}', name: 'supprimer', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function supprimer(User $user, #[CurrentUser] User $currentUser): JsonResponse
    {
        if ($user->getId() === $currentUser->getId()) {
            return $this->json(['message' => 'Impossible de supprimer votre propre compte.'], 422);
        }

        $this->userRepository->remove($user);

        return $this->json(['message' => 'Utilisateur supprimé.']);
    }

    #[Route('/stats/dashboard', name: 'stats_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_RH')]
    public function statsDashboard(): JsonResponse
    {
        $totalEmployes     = count($this->userRepository->findByRole(User::ROLE_AGENT));
        $presentsAujourdhui = count($this->pointageRepository->findTodayAll());
        $anomalies         = count($this->pointageRepository->findAnomalies());
        $demandesEnAttente = count($this->demandeRepository->findEnAttente());

        return $this->json([
            'data' => [
                'total_employes'       => $totalEmployes,
                'presents_aujourd_hui' => $presentsAujourdhui,
                'anomalies_en_cours'   => $anomalies,
                'demandes_en_attente'  => $demandesEnAttente,
                'taux_presence'        => $totalEmployes > 0
                    ? round(($presentsAujourdhui / $totalEmployes) * 100, 1)
                    : 0,
            ],
            'message' => 'OK',
        ]);
    }

    private function hydrateUser(User $user, array $data): void
    {
        if (isset($data['email']))       $user->setEmail($data['email']);
        if (isset($data['prenom']))      $user->setPrenom($data['prenom']);
        if (isset($data['nom']))         $user->setNom($data['nom']);
        if (isset($data['role']))        $user->setRole($data['role']);
        if (isset($data['departement'])) $user->setDepartement($data['departement']);
        if (isset($data['consentement_rgpd'])) $user->setConsentementRgpd((bool) $data['consentement_rgpd']);
    }

    private function serializeUser(User $u): array
    {
        return [
            'id'          => $u->getId(),
            'email'       => $u->getEmail(),
            'prenom'      => $u->getPrenom(),
            'nom'         => $u->getNom(),
            'nomComplet'  => $u->getFullName(),
            'initiales'   => $u->getInitials(),
            'role'        => $u->getRole(),
            'departement' => $u->getDepartement(),
            'dateCreation' => $u->getDateCreation()?->format('Y-m-d'),
        ];
    }
}
