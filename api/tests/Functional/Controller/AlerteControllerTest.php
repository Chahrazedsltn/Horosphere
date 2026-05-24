<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Alerte;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AlerteControllerTest extends WebTestCase
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

    /** Retourne une alerte appartenant à $ownerEmail */
    private function getAlerteId(string $ownerEmail): int
    {
        static::bootKernel();
        $em    = static::getContainer()->get('doctrine')->getManager();
        $owner = $em->getRepository(User::class)->findOneBy(['email' => $ownerEmail]);
        $alerte = $em->getRepository(Alerte::class)->findOneBy(['utilisateur' => $owner]);
        static::ensureKernelShutdown();
        return $alerte->getId();
    }

    public function testMesAlertesRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/alertes');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMesAlertesAsAgent(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/alertes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('non_lues', $data);
        $this->assertIsArray($data['data']);
    }

    public function testToutesAlertesRequiresRh(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/alertes/toutes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testToutesAlertesAsRh(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('GET', '/api/alertes/toutes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testMarquerLueAsOwner(): void
    {
        $alerteId = $this->getAlerteId('agent1@horosphere.fr');
        $token    = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client   = static::createClient();
        $client->request('PATCH', '/api/alertes/' . $alerteId . '/lire', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['data']['estLue']);
    }

    public function testMarquerLueRefuseAutreUtilisateur(): void
    {
        // Alerte appartenant à agent1, tentative par agent2
        $alerteId = $this->getAlerteId('agent1@horosphere.fr');
        $token    = $this->getJwtToken('agent2@horosphere.fr', 'Agent1234!');
        $client   = static::createClient();
        $client->request('PATCH', '/api/alertes/' . $alerteId . '/lire', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMarquerToutLu(): void
    {
        $token  = $this->getJwtToken('agent2@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('PATCH', '/api/alertes/tout-lire', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function testMarquerToutLuRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('PATCH', '/api/alertes/tout-lire');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
