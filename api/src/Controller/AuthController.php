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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
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
        private readonly RateLimiterFactory           $authLoginLimiter,
        private readonly RateLimiterFactory           $passwordResetLimiter,
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
                'soldeConges' => $user->getSoldeConges(),
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
        $limiter = $this->passwordResetLimiter->create($request->getClientIp() ?? 'unknown');
        if (false === $limiter->consume()->isAccepted()) {
            return $this->json(['message' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.'], 429);
        }

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

            $frontendUrl = $_SERVER['APP_FRONTEND_URL'] ?? $_ENV['APP_FRONTEND_URL'] ?? 'http://localhost';
            $resetUrl    = rtrim($frontendUrl, '/') . '/reinitialiser-mot-de-passe?token=' . $tokenEntity->getToken();

            $email_message = (new TemplatedEmail())
                ->from('noreply@horosphere.fr')
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
        $limiter = $this->passwordResetLimiter->create($request->getClientIp() ?? 'unknown');
        if (false === $limiter->consume()->isAccepted()) {
            return $this->json(['message' => 'Trop de tentatives. Veuillez réessayer dans quelques minutes.'], 429);
        }

        $data              = json_decode($request->getContent(), true) ?? [];
        $token             = trim((string) ($data['token'] ?? ''));
        $nouveauMotDePasse = (string) ($data['nouveau_mot_de_passe'] ?? '');

        if ('' === $token || '' === $nouveauMotDePasse) {
            return $this->json(['message' => 'Token et nouveau mot de passe requis.'], 422);
        }

        if (strlen($nouveauMotDePasse) < 12) {
            return $this->json(['message' => 'Le mot de passe doit contenir au moins 12 caractères.'], 422);
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

    /* ------------------------------------------------------------------ */
    /*  RGPD — Droit à la portabilité (Article 20)                        */
    /* ------------------------------------------------------------------ */

    /**
     * GET /api/auth/mes-donnees
     * Retourne toutes les données personnelles de l'utilisateur connecté.
     */
    #[Route('/mes-donnees', name: 'mes_donnees', methods: ['GET'])]
    public function mesDonnees(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        $this->auditService->log(
            AuditLog::ACTION_RGPD_EXPORT,
            $user,
            'User',
            $user->getId(),
            ['action' => 'export_donnees_personnelles'],
        );

        return $this->json([
            'data' => [
                'utilisateur' => [
                    'id'           => $user->getId(),
                    'email'        => $user->getEmail(),
                    'prenom'       => $user->getPrenom(),
                    'nom'          => $user->getNom(),
                    'role'         => $user->getRole(),
                    'departement'  => $user->getDepartement(),
                    'dateCreation' => $user->getDateCreation()?->format('Y-m-d H:i:s'),
                ],
                'pointages' => array_map(static fn ($p) => [
                    'id'            => $p->getId(),
                    'date'          => $p->getDateJour()?->format('Y-m-d'),
                    'heureArrivee'  => $p->getHeureArrivee()?->format('H:i:s'),
                    'heureDepart'   => $p->getHeureDepart()?->format('H:i:s'),
                    'dureeMinutes'  => $p->getDureeMinutes(),
                    'site'          => $p->getSite()?->getNom(),
                    'statut'        => $p->getStatut(),
                ], $user->getPointages()->toArray()),
                'demandes' => array_map(static fn ($d) => [
                    'id'           => $d->getId(),
                    'type'         => $d->getTypeDemande(),
                    'dateDebut'    => $d->getDateDebut()?->format('Y-m-d'),
                    'dateFin'      => $d->getDateFin()?->format('Y-m-d'),
                    'motif'        => $d->getMotif(),
                    'statut'       => $d->getStatut(),
                    'dateCreation' => $d->getDateCreation()?->format('Y-m-d H:i:s'),
                ], $user->getDemandes()->toArray()),
                'alertes' => array_map(static fn ($a) => [
                    'id'           => $a->getId(),
                    'type'         => $a->getTypeAlerte(),
                    'message'      => $a->getMessage(),
                    'dateCreation' => $a->getDateCreation()?->format('Y-m-d H:i:s'),
                ], $user->getAlertes()->toArray()),
            ],
            'message' => 'Export RGPD — données personnelles.',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  RGPD — Droit à l'effacement (Article 17)                          */
    /* ------------------------------------------------------------------ */

    /**
     * DELETE /api/auth/mon-compte
     * Anonymise le compte et supprime les données personnelles associées.
     */
    #[Route('/mon-compte', name: 'mon_compte_supprimer', methods: ['DELETE'])]
    public function supprimerMonCompte(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        $userId    = $user->getId();
        $userEmail = $user->getEmail();

        // 1. Supprimer les alertes
        foreach ($user->getAlertes() as $alerte) {
            $this->em->remove($alerte);
        }

        // 2. Supprimer les documents (entité + fichier physique)
        $filesystem = new Filesystem();
        foreach ($user->getDocuments() as $document) {
            $chemin = $document->getCheminFichier();
            if (null !== $chemin && $filesystem->exists($chemin)) {
                $filesystem->remove($chemin);
            }
            $this->em->remove($document);
        }

        // 3. Anonymiser l'utilisateur (conservation pour intégrité référentielle)
        $user->setEmail('anonyme_' . $userId . '@deleted.horosphere.fr');
        $user->setPrenom('Utilisateur');
        $user->setNom('Supprimé');
        $user->setPassword(bin2hex(random_bytes(32)));
        $user->setDepartement(null);
        $user->setConsentementRgpd(false);
        $user->setRole(User::ROLE_AGENT);

        $this->em->flush();

        // 4. Tracer dans l'audit log (après flush pour garantir la persistance)
        $this->auditService->log(
            AuditLog::ACTION_RGPD_EFFACEMENT,
            null, // l'utilisateur est anonymisé, on log sans acteur
            'User',
            $userId,
            ['email_origine' => AuditService::maskEmail($userEmail), 'action' => 'effacement_compte'],
        );

        return $this->json(['message' => 'Compte supprimé conformément au RGPD.']);
    }
}
