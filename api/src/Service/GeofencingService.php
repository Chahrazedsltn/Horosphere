<?php

namespace App\Service;

use App\Entity\Site;
use App\Repository\SiteRepository;

class GeofencingService
{
    public function __construct(
        private readonly SiteRepository $siteRepository,
    ) {}

    /**
     * Valide qu'une coordonnée GPS est dans les bornes légales.
     */
    public function validerCoordonnees(float $lat, float $lon): bool
    {
        return $lat >= -90.0 && $lat <= 90.0 && $lon >= -180.0 && $lon <= 180.0;
    }

    /**
     * Calcule la distance entre deux coordonnées GPS via la formule haversine.
     * Retourne la distance en mètres.
     */
    public function calculerDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // en mètres

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Vérifie si les coordonnées sont dans la zone du site.
     */
    public function estDansZone(float $lat, float $lon, Site $site): bool
    {
        if (!$site->isGeofencingActif()) {
            return true; // Géofencing désactivé = toujours autorisé
        }

        $distance = $this->calculerDistance(
            $lat,
            $lon,
            $site->getLatitude(),
            $site->getLongitude(),
        );

        return $distance <= $site->getRayonMetres();
    }

    /**
     * Trouve le site actif le plus proche des coordonnées données.
     * Retourne null si aucun site n'est dans le rayon.
     */
    public function trouverSiteLePlusProche(float $lat, float $lon): ?Site
    {
        $sites = $this->siteRepository->findActifs();
        $siteProche = null;
        $distanceMin = PHP_FLOAT_MAX;

        foreach ($sites as $site) {
            $distance = $this->calculerDistance(
                $lat,
                $lon,
                $site->getLatitude(),
                $site->getLongitude(),
            );

            if ($distance < $distanceMin && $distance <= $site->getRayonMetres()) {
                $distanceMin = $distance;
                $siteProche = $site;
            }
        }

        return $siteProche;
    }

    /**
     * Vérifie si des coordonnées sont dans la zone d'au moins un site actif.
     */
    public function estDansUneSiteActif(float $lat, float $lon): bool
    {
        return null !== $this->trouverSiteLePlusProche($lat, $lon);
    }
}
