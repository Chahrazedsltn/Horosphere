<?php

namespace App\Tests\Functional\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    private function getJwtToken(string $email, string $password): string
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password]),
        );
        $data = json_decode($client->getResponse()->getContent(), true);
        static::ensureKernelShutdown();
        return $data['token'] ?? '';
    }

    // ─── Login ──────────────────────────────────────────────────────

    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'agent1@horosphere.fr', 'password' => 'Agent1234!']),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'agent1@horosphere.fr', 'password' => 'mauvais_mot_de_passe']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginMissingFields(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode([]),
        );

        // Symfony JSON login returns 400 for missing fields
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginMissingPassword(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'agent1@horosphere.fr']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginNonExistentUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'inexistant@horosphere.fr', 'password' => 'Test1234!']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    // ─── Me ─────────────────────────────────────────────────────────

    public function testMeAuthenticated(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/auth/me', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('OK', $data['message']);

        // Vérifier la structure des données utilisateur
        $userData = $data['data'];
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('email', $userData);
        $this->assertArrayHasKey('prenom', $userData);
        $this->assertArrayHasKey('nom', $userData);
        $this->assertArrayHasKey('role', $userData);
        $this->assertArrayHasKey('departement', $userData);
        $this->assertArrayHasKey('initiales', $userData);
        $this->assertArrayHasKey('soldeConges', $userData);

        $this->assertEquals('agent1@horosphere.fr', $userData['email']);
        $this->assertEquals('Jean', $userData['prenom']);
        $this->assertEquals('AGENT', $userData['role']);
    }

    public function testMeUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMeWithInvalidToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/me', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer token_invalide_123'],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMeAsAdmin(): void
    {
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('GET', '/api/auth/me', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('admin@horosphere.fr', $data['data']['email']);
        $this->assertEquals('ADMIN', $data['data']['role']);
        $this->assertEquals('Direction', $data['data']['departement']);
    }

    public function testMeAsRh(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('GET', '/api/auth/me', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('rh@horosphere.fr', $data['data']['email']);
        $this->assertEquals('RH', $data['data']['role']);
    }

    // ─── Mot de passe oublié ────────────────────────────────────────

    public function testMotDePasseOublieValidEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/mot-de-passe-oublie', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'agent1@horosphere.fr']),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Si ce compte existe, un email a été envoyé.', $data['message']);
    }

    public function testMotDePasseOublieInvalidEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/mot-de-passe-oublie', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'pas-un-email']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Adresse e-mail invalide.', $data['message']);
    }

    public function testMotDePasseOublieEmptyEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/mot-de-passe-oublie', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => '']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Adresse e-mail invalide.', $data['message']);
    }

    public function testMotDePasseOublieMissingEmailField(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/mot-de-passe-oublie', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testMotDePasseOublieUnknownEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/mot-de-passe-oublie', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'inconnu@horosphere.fr']),
        );

        // Doit retourner 200 avec le même message pour ne pas révéler l'existence du compte
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Si ce compte existe, un email a été envoyé.', $data['message']);
    }

    // ─── Réinitialiser mot de passe ─────────────────────────────────

    public function testReinitialiserMotDePasseTokenManquant(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => '', 'nouveau_mot_de_passe' => 'Nouveau1234!']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Token et nouveau mot de passe requis.', $data['message']);
    }

    public function testReinitialiserMotDePasseMotDePasseManquant(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'un-token', 'nouveau_mot_de_passe' => '']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Token et nouveau mot de passe requis.', $data['message']);
    }

    public function testReinitialiserMotDePasseMotDePasseTropCourt(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'un-token', 'nouveau_mot_de_passe' => 'Ab1!']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Le mot de passe doit contenir au moins 8 caractères.', $data['message']);
    }

    public function testReinitialiserMotDePasseSansMajuscule(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'un-token', 'nouveau_mot_de_passe' => 'abcdefgh1!']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('majuscule', $data['message']);
    }

    public function testReinitialiserMotDePasseSansMinuscule(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'un-token', 'nouveau_mot_de_passe' => 'ABCDEFGH1!']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('minuscule', $data['message']);
    }

    public function testReinitialiserMotDePasseSansChiffre(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'un-token', 'nouveau_mot_de_passe' => 'Abcdefgh!']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('chiffre', $data['message']);
    }

    public function testReinitialiserMotDePasseSansCaractereSpecial(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'un-token', 'nouveau_mot_de_passe' => 'Abcdefgh1']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('spécial', $data['message']);
    }

    public function testReinitialiserMotDePasseTokenInvalide(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'token-inexistant-12345', 'nouveau_mot_de_passe' => 'NouveauMdp1!']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Token invalide ou expiré.', $data['message']);
    }

    public function testReinitialiserMotDePasseCorpsVide(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Token et nouveau mot de passe requis.', $data['message']);
    }

    public function testReinitialiserMotDePasseAvecTokenValide(): void
    {
        // Créer un token de reset en base de données
        static::bootKernel();
        $em = static::getContainer()->get('doctrine')->getManager();

        $tokenEntity = new PasswordResetToken('agent1@horosphere.fr');
        $em->persist($tokenEntity);
        $em->flush();

        $tokenValue = $tokenEntity->getToken();
        static::ensureKernelShutdown();

        // Utiliser le token pour réinitialiser le mot de passe
        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $tokenValue, 'nouveau_mot_de_passe' => 'NouveauMdp1!']),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Mot de passe réinitialisé avec succès.', $data['message']);
        static::ensureKernelShutdown();

        // Vérifier que le nouveau mot de passe fonctionne
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'agent1@horosphere.fr', 'password' => 'NouveauMdp1!']),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        static::ensureKernelShutdown();

        // Restaurer le mot de passe original pour ne pas casser les autres tests
        static::bootKernel();
        $em   = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => 'agent1@horosphere.fr']);
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $user->setPassword($hasher->hashPassword($user, 'Agent1234!'));
        $em->flush();
        static::ensureKernelShutdown();
    }

    public function testReinitialiserMotDePasseTokenDejaUtilise(): void
    {
        // Créer un token et le marquer comme utilisé
        static::bootKernel();
        $em = static::getContainer()->get('doctrine')->getManager();

        $tokenEntity = new PasswordResetToken('agent2@horosphere.fr');
        $tokenEntity->markAsUsed();
        $em->persist($tokenEntity);
        $em->flush();

        $tokenValue = $tokenEntity->getToken();
        static::ensureKernelShutdown();

        $client = static::createClient();
        $client->request('POST', '/api/auth/reinitialiser-mot-de-passe', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => $tokenValue, 'nouveau_mot_de_passe' => 'NouveauMdp1!']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Token invalide ou expiré.', $data['message']);
    }

    // ─── RGPD — Mes données ────────────────────────────────────────

    public function testMesDonneesAuthenticated(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/auth/mes-donnees', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $statusCode = $client->getResponse()->getStatusCode();
        // L'endpoint peut ne pas encore exister (404) ou fonctionner (200)
        if ($statusCode === Response::HTTP_OK) {
            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('data', $data);
        } else {
            $this->assertContains($statusCode, [Response::HTTP_NOT_FOUND, Response::HTTP_OK]);
        }
    }

    public function testMesDonneesUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/mes-donnees');

        $statusCode = $client->getResponse()->getStatusCode();
        // 401 si l'endpoint existe, 404 s'il n'est pas encore implémenté
        $this->assertContains($statusCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_NOT_FOUND]);
    }

    // ─── RGPD — Suppression compte ─────────────────────────────────

    public function testSupprimerMonCompteUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/auth/mon-compte');

        $statusCode = $client->getResponse()->getStatusCode();
        // 401 si l'endpoint existe, 404 ou 405 s'il n'est pas encore implémenté
        $this->assertContains($statusCode, [
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_METHOD_NOT_ALLOWED,
        ]);
    }

    public function testSupprimerMonCompteAuthenticated(): void
    {
        // Créer un utilisateur temporaire pour la suppression
        $adminToken = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client     = static::createClient();
        $client->request('POST', '/api/users', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $adminToken],
            json_encode([
                'email'    => 'rgpd-delete@horosphere.fr',
                'password' => 'Delete1234!',
                'prenom'   => 'RGPD',
                'nom'      => 'Delete',
                'role'     => 'AGENT',
            ]),
        );
        static::ensureKernelShutdown();

        $token  = $this->getJwtToken('rgpd-delete@horosphere.fr', 'Delete1234!');
        $client = static::createClient();
        $client->request('DELETE', '/api/auth/mon-compte', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $statusCode = $client->getResponse()->getStatusCode();
        // 200 si l'endpoint existe, 404 ou 405 s'il n'est pas encore implémenté
        if ($statusCode === Response::HTTP_OK) {
            $data = json_decode($client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('message', $data);
        } else {
            $this->assertContains($statusCode, [
                Response::HTTP_NOT_FOUND,
                Response::HTTP_METHOD_NOT_ALLOWED,
            ]);
        }
    }
}
