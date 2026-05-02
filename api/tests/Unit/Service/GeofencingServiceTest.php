<?php

namespace App\Tests\Unit\Service;

use App\Entity\Site;
use App\Repository\SiteRepository;
use App\Service\GeofencingService;
use PHPUnit\Framework\TestCase;

class GeofencingServiceTest extends TestCase
{
    private GeofencingService $service;

    protected function setUp(): void
    {
        $siteRepository = $this->createMock(SiteRepository::class);
        $this->service  = new GeofencingService($siteRepository);
    }

    public function testCalculerDistanceMemePoint(): void
    {
        $distance = $this->service->calculerDistance(48.8698, 2.3309, 48.8698, 2.3309);
        $this->assertEqualsWithDelta(0.0, $distance, 0.01);
    }

    public function testCalculerDistanceParisMarseilleApprox(): void
    {
        // Paris → Marseille ≈ 660 km
        $distance = $this->service->calculerDistance(48.8566, 2.3522, 43.2965, 5.3698);
        $this->assertGreaterThan(650000, $distance);
        $this->assertLessThan(680000, $distance);
    }

    public function testEstDansZoneVrai(): void
    {
        $site = $this->createSite(48.8698, 2.3309, 200);

        // Même point → dans zone
        $this->assertTrue($this->service->estDansZone(48.8698, 2.3309, $site));
    }

    public function testEstDansZoneFaux(): void
    {
        $site = $this->createSite(48.8698, 2.3309, 200);

        // Lyon → hors zone de Paris
        $this->assertFalse($this->service->estDansZone(45.7602, 4.8596, $site));
    }

    public function testEstDansZoneGeofencingDesactive(): void
    {
        $site = $this->createSite(48.8698, 2.3309, 200);
        $site->setGeofencingActif(false);

        // Géofencing désactivé → toujours dans zone
        $this->assertTrue($this->service->estDansZone(45.7602, 4.8596, $site));
    }

    public function testEstDansZoneFrontiereRayon(): void
    {
        $site = $this->createSite(48.8698, 2.3309, 200);

        // Point à exactement ~180m (dans zone)
        $this->assertTrue($this->service->estDansZone(48.8698, 2.3335, $site));
    }

    public function testDistanceSymetrique(): void
    {
        $d1 = $this->service->calculerDistance(48.8698, 2.3309, 45.7602, 4.8596);
        $d2 = $this->service->calculerDistance(45.7602, 4.8596, 48.8698, 2.3309);

        $this->assertEqualsWithDelta($d1, $d2, 0.01);
    }

    public function testCalculerDistancePointsProches(): void
    {
        // ~111m (environ 0.001 degré en latitude)
        $distance = $this->service->calculerDistance(48.8698, 2.3309, 48.8708, 2.3309);
        $this->assertGreaterThan(50, $distance);
        $this->assertLessThan(200, $distance);
    }

    private function createSite(float $lat, float $lon, int $rayon): Site
    {
        $site = new Site();
        $site->setNom('Test Site');
        $site->setAdresse('Test Address');
        $site->setLatitude($lat);
        $site->setLongitude($lon);
        $site->setRayonMetres($rayon);
        $site->setGeofencingActif(true);

        return $site;
    }
}
