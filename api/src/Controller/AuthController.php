<?php

namespace App\Controller;

use App\Entity\AuditLog;
use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UserRepository;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly UserRepository               $userRepository,
        private readonly PasswordResetTokenRepository $resetTokenRepository,
        private readonly UserPasswordHasherInterface  $passwordHasher,
        private readonly MailerInterface              $mailer,
        private readonly AuditService                 $auditService,
        private readonly EntityManagerInterface       $em,
    ) {}

    /**
     * POST /api/auth/login est géré par LexikJWT via security.yaml
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        throw new \LogicException('Ce endpoint est intercepté par le firewall JWT.');
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        return $this->json([
            'data' => [
                'id'          => $user->getId(),
                'email'       => $user->getEmail(),
                'prenom'      => $user->getPrenom(),
                'nom'         => $user->getNom(),
                'role'        => $user->getRole(),
                'departement' => $user->getDepartement(),
                'initiales'   => $user->getInitials(),
            ],
            'message' => 'OK',
        ]);
    }

    /**
     * POST /api/auth/mot-de-passe-oublie
     * Body : { "email": "..." }
     * Toujours retourne 200 pour ne pas révéler l'existence du compte.
     */
    #[Route('/mot-de-passe-oublie', name: 'mot_de_passe_oublie', methods: ['POST'])]
    public function motDePasseOublie(Request $request): JsonResponse
    {
        $data  = json_decode($request->getContent(), true) ?? [];
        $email = trim((string) ($data['email'] ?? ''));

        if ('' === $email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['message' => 'Adresse e-mail invalide.'], 422);
        }

        $user = $this->userRepository->findOneByEmail($email);

        if (null !== $user) {
            // Invalider les tokens précédents
            $this->resetTokenRepository->invalidatePreviousTokens($email);

            $tokenEntity = new PasswordResetToken($email);
            $this->resetTokenRepository->save($tokenEntity);

            $resetUrl = $this->generateUrl(
                'app_reset_password_frontend',
                ['token' => $tokenEntity->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );

            // On reconstruit l'URL frontend (pas Symfony) depuis APP_FRONTEND_URL
            $frontendUrl = $_SERVER['APP_FRONTEND_URL'] ?? $_ENV['APP_FRONTEND_URL'] ?? 'http://localhost';
            $resetUrl    = rtrim($frontendUrl, '/') . '/reinitialiser-mot-de-passe?token=' . $tokenEntity->getToken();

            $email_message = (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject('Horosphere — Réinitialisation de votre mot de passe')
                ->htmlTemplate('email/reset_password.html.twig')
                ->context([
                    'prenom'    => $user->getPrenom(),
                    'reset_url' => $resetUrl,
                ]);

            $this->mailer->send($email_message);

            $this->auditService->log(
                AuditLog::ACTION_RESET_PASSWORD,
                null,
                'User',
                $user->getId(),
                ['email' => $user->getEmail(), 'action' => 'demande_reset'],
            );
        }

        return $this->json(['message' => 'Si ce compte existe, un email a été envoyé.']);
    }

    /**
     * POST /api/auth/reinitialiser-mot-de-passe
     * Body : { "token": "...", "nouveau_mot_de_passe": "..." }
     */
    #[Route('/reinitialiser-mot-de-passe', name: 'reinitialiser_mot_de_passe', methods: ['POST'])]
    public function reinitialiserMotDePasse(Request $request): JsonResponse
    {
        $data              = json_decode($request->getContent(), true) ?? [];
        $token             = trim((string) ($data['token'] ?? ''));
        $nouveauMotDePasse = (string) ($data['nouveau_mot_de_passe'] ?? '');

        if ('' === $token || '' === $nouveauMotDePasse) {
            return $this->json(['message' => 'Token et nouveau mot de passe requis.'], 422);
        }

        if (strlen($nouveauMotDePasse) < 8) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins 8 caractères.'], 422);
        }

        if (!preg_match('/[A-Z]/', $nouveauMotDePasse)
            || !preg_match('/[a-z]/', $nouveauMotDePasse)
            || !preg_match('/[0-9]/', $nouveauMotDePasse)
            || !preg_match('/[\W_]/', $nouveauMotDePasse)
        ) {
            return $this->json([
                'message' => 'Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.',
            ], 422);
        }

        $tokenEntity = $this->resetTokenRepository->findValidByToken($token);

        if (null === $tokenEntity) {
            return $this->json(['message' => 'Token invalide ou expiré.'], 400);
        }

        $user = $this->userRepository->findOneByEmail($tokenEntity->getEmail());
        if (null === $user) {
            return $this->json(['message' => 'Compte introuvable.'], 404);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $nouveauMotDePasse));
        $tokenEntity->markAsUsed();

        $this->em->flush();

        $this->auditService->log(
            AuditLog::ACTION_RESET_PASSWORD,
            $user,
            'User',
            $user->getId(),
            ['action' => 'reset_effectue'],
        );

        // Nettoyer les anciens tokens
        $this->resetTokenRepository->purgeExpired();

        return $this->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }
}
