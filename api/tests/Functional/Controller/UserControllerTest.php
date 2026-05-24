<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
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

    private function getUserId(string $email): int
    {
        static::bootKernel();
        $em   = static::getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        static::ensureKernelShutdown();
        return $user->getId();
    }

    public function testListeUsersRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListeUsersAsAgentForbidden(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListeUsersAsRh(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('GET', '/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertGreaterThanOrEqual(5, count($data['data']));
    }

    public function testDetailUserAsRh(): void
    {
        $userId = $this->getUserId('agent1@horosphere.fr');
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('GET', '/api/users/' . $userId, [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('agent1@horosphere.fr', $data['data']['email']);
        $this->assertEquals('Jean', $data['data']['prenom']);
    }

    public function testCreerUserRequiresAdmin(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('POST', '/api/users', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['email' => 'new@horosphere.fr', 'password' => 'Test1234!', 'prenom' => 'New', 'nom' => 'User']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreerUserChampsManquants(): void
    {
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('POST', '/api/users', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['email' => 'incomplete@horosphere.fr']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreerUserAsAdmin(): void
    {
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('POST', '/api/users', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode([
                'email'      => 'nouveau.agent@horosphere.fr',
                'password'   => 'Nouveau1234!',
                'prenom'     => 'Nouveau',
                'nom'        => 'Agent',
                'role'       => User::ROLE_AGENT,
                'departement'=> 'IT',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('nouveau.agent@horosphere.fr', $data['data']['email']);
        $this->assertEquals('AGENT', $data['data']['role']);
    }

    public function testModifierUserAsAdmin(): void
    {
        $userId = $this->getUserId('agent1@horosphere.fr');
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('PATCH', '/api/users/' . $userId, [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['departement' => 'R&D']),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('R&D', $data['data']['departement']);
    }

    public function testSupprimerUserAsAdmin(): void
    {
        // Créer un utilisateur temporaire à supprimer
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('POST', '/api/users', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['email' => 'todelete@horosphere.fr', 'password' => 'Delete1234!', 'prenom' => 'Delete', 'nom' => 'Me']),
        );
        $userId = json_decode($client->getResponse()->getContent(), true)['data']['id'];
        static::ensureKernelShutdown();

        $client = static::createClient();
        $client->request('DELETE', '/api/users/' . $userId, [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
    }

    public function testSupprimerPropreCompteForbidden(): void
    {
        $adminId = $this->getUserId('admin@horosphere.fr');
        $token   = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client  = static::createClient();
        $client->request('DELETE', '/api/users/' . $adminId, [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testStatsDashboardAsAgent(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/users/stats/dashboard', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testStatsDashboardAsRh(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('GET', '/api/users/stats/dashboard', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('total_employes', $data['data']);
        $this->assertArrayHasKey('presents_aujourd_hui', $data['data']);
        $this->assertArrayHasKey('demandes_en_attente', $data['data']);
        $this->assertArrayHasKey('taux_presence', $data['data']);
    }
}
