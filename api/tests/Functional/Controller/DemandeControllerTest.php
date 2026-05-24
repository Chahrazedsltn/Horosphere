<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Demande;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DemandeControllerTest extends WebTestCase
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

    /** Retourne l'ID d'une demande EN_ATTENTE appartenant à $ownerEmail */
    private function getDemandeEnAttenteId(string $ownerEmail): int
    {
        static::bootKernel();
        $em      = static::getContainer()->get('doctrine')->getManager();
        $owner   = $em->getRepository(User::class)->findOneBy(['email' => $ownerEmail]);
        $demande = $em->getRepository(Demande::class)->findOneBy([
            'utilisateur' => $owner,
            'statut'      => Demande::STATUT_EN_ATTENTE,
        ]);
        static::ensureKernelShutdown();
        return $demande->getId();
    }

    public function testListeDemandesRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/demandes');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListeDemandesAsAgent(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/demandes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        // L'agent ne voit que ses propres demandes
        foreach ($data['data'] as $demande) {
            $this->assertEquals('agent1@horosphere.fr', $demande['utilisateur']['email']);
        }
    }

    public function testListeDemandesAsRhVoitTout(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('GET', '/api/demandes', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        // La RH voit au moins les 3 demandes des fixtures
        $this->assertGreaterThanOrEqual(3, count($data['data']));
    }

    public function testEnAttenteRequiresRh(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/demandes/en-attente', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testEnAttenteAsRh(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('GET', '/api/demandes/en-attente', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        // Toutes les demandes retournées sont EN_ATTENTE
        foreach ($data['data'] as $demande) {
            $this->assertEquals(Demande::STATUT_EN_ATTENTE, $demande['statut']);
        }
    }

    public function testCreerDemandeAsAgent(): void
    {
        $token  = $this->getJwtToken('agent3@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('POST', '/api/demandes', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode([
                'type_demande' => Demande::TYPE_CONGE,
                'date_debut'   => date('Y-m-d', strtotime('+30 days')),
                'date_fin'     => date('Y-m-d', strtotime('+35 days')),
                'motif'        => 'Congés été CI',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(Demande::TYPE_CONGE, $data['data']['typeDemande']);
        $this->assertEquals(Demande::STATUT_EN_ATTENTE, $data['data']['statut']);
    }

    public function testCreerDemandeChampsManquants(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('POST', '/api/demandes', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['type_demande' => Demande::TYPE_CONGE]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTraiterDemandeApprouveeAsRh(): void
    {
        $demandeId = $this->getDemandeEnAttenteId('agent1@horosphere.fr');
        $token     = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client    = static::createClient();
        $client->request('PATCH', '/api/demandes/' . $demandeId . '/traiter', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['decision' => Demande::STATUT_APPROUVEE]),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(Demande::STATUT_APPROUVEE, $data['data']['statut']);
    }

    public function testTraiterDemandeRejeteeAsRh(): void
    {
        $demandeId = $this->getDemandeEnAttenteId('agent2@horosphere.fr');
        $token     = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client    = static::createClient();
        $client->request('PATCH', '/api/demandes/' . $demandeId . '/traiter', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['decision' => Demande::STATUT_REJETEE]),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(Demande::STATUT_REJETEE, $data['data']['statut']);
    }

    public function testTraiterDemandeDecisionInvalide(): void
    {
        $demandeId = $this->getDemandeEnAttenteId('agent1@horosphere.fr');
        $token     = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client    = static::createClient();
        $client->request('PATCH', '/api/demandes/' . $demandeId . '/traiter', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['decision' => 'INVALIDE']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTraiterDemandeRequiresRh(): void
    {
        $demandeId = $this->getDemandeEnAttenteId('agent1@horosphere.fr');
        $token     = $this->getJwtToken('agent2@horosphere.fr', 'Agent1234!');
        $client    = static::createClient();
        $client->request('PATCH', '/api/demandes/' . $demandeId . '/traiter', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['decision' => Demande::STATUT_APPROUVEE]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
