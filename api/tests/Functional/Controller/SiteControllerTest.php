<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Site;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SiteControllerTest extends WebTestCase
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

    private function getSiteId(string $nom): int
    {
        static::bootKernel();
        $em   = static::getContainer()->get('doctrine')->getManager();
        $site = $em->getRepository(Site::class)->findOneBy(['nom' => $nom]);
        static::ensureKernelShutdown();
        return $site->getId();
    }

    public function testListeSitesPublique(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/sites');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertGreaterThanOrEqual(3, count($data['data']));
    }

    public function testDetailSite(): void
    {
        $siteId = $this->getSiteId('Siège Social Paris');
        $client = static::createClient();
        $client->request('GET', '/api/sites/' . $siteId);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Siège Social Paris', $data['data']['nom']);
    }

    public function testCreerSiteRequiresAdmin(): void
    {
        $token  = $this->getJwtToken('agent1@horosphere.fr', 'Agent1234!');
        $client = static::createClient();
        $client->request('POST', '/api/sites', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['nom' => 'Site Interdit', 'latitude' => 48.0, 'longitude' => 2.0, 'rayon_metres' => 100]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreerSiteRhForbidden(): void
    {
        $token  = $this->getJwtToken('rh@horosphere.fr', 'Rh1234!');
        $client = static::createClient();
        $client->request('POST', '/api/sites', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['nom' => 'Site RH', 'latitude' => 48.0, 'longitude' => 2.0, 'rayon_metres' => 100]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreerSiteAsAdmin(): void
    {
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('POST', '/api/sites', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode([
                'nom'             => 'Site Test CI',
                'adresse'         => '1 Rue du Test',
                'latitude'        => 48.85,
                'longitude'       => 2.35,
                'rayon_metres'    => 150,
                'geofencing_actif'=> true,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Site Test CI', $data['data']['nom']);
        $this->assertEquals(150, $data['data']['rayonMetres']);
    }

    public function testCreerSiteRayonTropPetit(): void
    {
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('POST', '/api/sites', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['nom' => 'Bad', 'latitude' => 48.0, 'longitude' => 2.0, 'rayon_metres' => 5]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreerSiteRayonTropGrand(): void
    {
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('POST', '/api/sites', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['nom' => 'Bad', 'latitude' => 48.0, 'longitude' => 2.0, 'rayon_metres' => 100000]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testModifierSiteAsAdmin(): void
    {
        $siteId = $this->getSiteId('Siège Social Paris');
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('PATCH', '/api/sites/' . $siteId, [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['nom' => 'Siège Paris (maj)']),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Siège Paris (maj)', $data['data']['nom']);
    }

    public function testSupprimerSiteAsAdmin(): void
    {
        // Créer un site temporaire à supprimer
        $token  = $this->getJwtToken('admin@horosphere.fr', 'Admin1234!');
        $client = static::createClient();
        $client->request('POST', '/api/sites', [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token],
            json_encode(['nom' => 'Site à supprimer', 'latitude' => 43.0, 'longitude' => 5.0, 'rayon_metres' => 50]),
        );
        $siteId = json_decode($client->getResponse()->getContent(), true)['data']['id'];
        static::ensureKernelShutdown();

        $client = static::createClient();
        $client->request('DELETE', '/api/sites/' . $siteId, [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token],
        );

        $this->assertResponseIsSuccessful();
    }
}
