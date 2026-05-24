<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DocumentControllerTest extends WebTestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        // Garantit l'existence du répertoire d'exports pour les tests
        $exportsDir = realpath(__DIR__ . '/../../../') . '/public/exports';
        if (!is_dir($exportsDir)) {
            mkdir($exportsDir, 0777, true);
        }
    }

    public function testListeDocumentsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/documents');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListeDocumentsAsAgent(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('GET', '/api/documents', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    public function testExportCsvRequiresRh(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('POST', '/api/exports/csv', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['date_debut' => '2026-01-01', 'date_fin' => '2026-01-31']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testExportCsvDatesManquantes(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('POST', '/api/exports/csv', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testExportCsvAsRh(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('POST', '/api/exports/csv', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode([
                'date_debut' => date('Y-m-d', strtotime('-30 days')),
                'date_fin'   => date('Y-m-d'),
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('CSV', $data['data']['typeDocument']);
        $this->assertArrayHasKey('downloadUrl', $data['data']);
    }

    public function testExportCsvAvecUtilisateurCible(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');

        // Récupérer l'ID d'agent1
        $client = static::createClient();
        $client->request('GET', '/api/users', [], [], ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]);
        $users  = json_decode($client->getResponse()->getContent(), true)['data'];
        $agent1 = array_values(array_filter($users, fn ($u) => $u['email'] === 'agent1@horosphere.fr'))[0];
        static::ensureKernelShutdown();

        $client = static::createClient();
        $client->request('POST', '/api/exports/csv', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode([
                'date_debut'     => date('Y-m-d', strtotime('-30 days')),
                'date_fin'       => date('Y-m-d'),
                'utilisateur_id' => $agent1['id'],
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    public function testTelechargerDocumentAsOwner(): void
    {
        // Générer un document d'abord
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('POST', '/api/exports/csv', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode([
                'date_debut' => date('Y-m-d', strtotime('-7 days')),
                'date_fin'   => date('Y-m-d'),
            ]),
        );
        $documentId = json_decode($client->getResponse()->getContent(), true)['data']['id'];
        static::ensureKernelShutdown();

        $client = static::createClient();
        $client->request('GET', '/api/documents/' . $documentId . '/download', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
        // BinaryFileResponse peut retourner text/plain ou text/csv selon le serveur
        $contentType = $client->getResponse()->headers->get('Content-Type') ?? '';
        $this->assertMatchesRegularExpression('#text/(csv|plain)#', $contentType);
    }

    public function testTelechargerDocumentAutreUtilisateurForbidden(): void
    {
        // Créer un document en tant que RH
        $rhToken = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client  = static::createClient();
        $client->request('POST', '/api/exports/csv', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $rhToken],
            json_encode([
                'date_debut' => date('Y-m-d', strtotime('-5 days')),
                'date_fin'   => date('Y-m-d'),
            ]),
        );
        $documentId = json_decode($client->getResponse()->getContent(), true)['data']['id'];
        static::ensureKernelShutdown();

        // Tenter de le télécharger en tant qu'agent
        $agentToken = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client     = static::createClient();
        $client->request('GET', '/api/documents/' . $documentId . '/download', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $agentToken],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
