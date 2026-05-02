<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PointageControllerTest extends WebTestCase
{
    private function getJwtToken(string $email, string $password): string
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password]),
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        return $data['token'] ?? '';
    }

    public function testLoginReturnsToken(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'agent1@horosphere.fr', 'password' => 'Agent1234!']),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }

    public function testLoginInvalidCredentials(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/auth/login', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'agent1@horosphere.fr', 'password' => 'mauvais_mot_de_passe']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMeReturnUser(): void
    {
        $token = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/auth/me', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Jean', $data['data']['prenom']);
        $this->assertEquals('AGENT', $data['data']['role']);
    }

    public function testArriverRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/pointages/arriver', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['latitude' => 48.8698, 'longitude' => 2.3309]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testArriverWithValidCoords(): void
    {
        $token = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();

        $client->request('POST', '/api/pointages/arriver', [], [],
            [
                'CONTENT_TYPE'      => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode(['latitude' => 48.8698, 'longitude' => 2.3309]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertNotNull($data['data']['id']);
    }

    public function testArriverMissingCoords(): void
    {
        $token = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();

        $client->request('POST', '/api/pointages/arriver', [], [],
            [
                'CONTENT_TYPE'      => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testMesPointagesReturnsList(): void
    {
        $token = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();

        $client->request('GET', '/api/pointages/mes-pointages', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testListePointagesRequiresRh(): void
    {
        $token = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();

        $client->request('GET', '/api/pointages', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListePointagesAsRh(): void
    {
        $token = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();

        $client->request('GET', '/api/pointages', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
    }
}
