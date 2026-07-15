<?php

namespace App\Service;

use App\Entity\Alerte;
use App\Entity\Pointage;
use App\Entity\User;
use App\Repository\PointageRepository;
use Doctrine\ORM\EntityManagerInterface;

class PointageService
{
    private const DUREE_NORMALE_MIN = 480;  // 8h en minutes
    private const DUREE_MAX_MIN     = 600;  // 10h en minutes

    public function __construct(
        private readonly PointageRepository $pointageRepository,
        private readonly GeofencingService  $geofencingService,
        private readonly AlerteService      $alerteService,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Enregistre une arrivée. Vérifie le géofencing et crée une alerte si hors zone.
     */
    public function pointer(User $user, float $lat, float $lon): Pointage
    {
        // Clôturer les anciens pointages encore ouverts (jours précédents)
        $this->cloturerAnciensPointages($user);

        // Vérifier s'il n'y a pas déjà un pointage en cours aujourd'hui
        $enCours = $this->pointageRepository->findEnCoursByUtilisateur($user);
        if (null !== $enCours) {
            return $enCours; // Retourner le pointage existant
        }

        $site = $this->geofencingService->trouverSiteLePlusProche($lat, $lon);
        $estDansZone = null !== $site;

        $pointage = new Pointage();
        $pointage->setUtilisateur($user);
        $pointage->setSite($site);
        $pointage->setHeureArrivee(new \DateTime());
        $pointage->setDateJour(new \DateTime('today'));
        $pointage->setCoordonneesGps(sprintf('%f,%f', $lat, $lon));

        if ($estDansZone) {
            $pointage->setStatut(Pointage::STATUT_EN_COURS);
        } else {
            $pointage->setStatut(Pointage::STATUT_HORS_ZONE);
            $pointage->setEstAnomalie(true);
        }

        $this->em->persist($pointage);
        $this->em->flush();

        if (!$estDansZone) {
            $this->alerteService->creerAlerteHorsZone($user, $pointage);
        }

        return $pointage;
    }

    /**
     * Enregistre un départ et détecte les anomalies.
     */
    public function terminerPointage(User $user, float $lat, float $lon): Pointage
    {
        $pointage = $this->pointageRepository->findEnCoursByUtilisateur($user);

        if (null === $pointage) {
            throw new \LogicException('Aucun pointage en cours pour cet utilisateur.');
        }

        $pointage->setHeureDepart(new \DateTime());

        $site = $this->geofencingService->trouverSiteLePlusProche($lat, $lon);
        $estDansZone = null !== $site;

        if (!$estDansZone && Pointage::STATUT_HORS_ZONE !== $pointage->getStatut()) {
            $pointage->setStatut(Pointage::STATUT_HORS_ZONE);
            $pointage->setEstAnomalie(true);
        } else {
            $pointage->setStatut(Pointage::STATUT_VALIDE);
        }

        $this->em->persist($pointage);
        $this->em->flush();

        $this->verifierAnomalies($pointage);

        return $pointage;
    }

    /**
     * Démarre une pause sur le pointage EN_COURS de l'utilisateur.
     */
    public function pauserPointage(User $user): Pointage
    {
        $pointage = $this->pointageRepository->findEnCoursByUtilisateur($user);

        if (null === $pointage) {
            throw new \LogicException('Aucun pointage en cours pour cet utilisateur.');
        }

        if ($pointage->isEnPause()) {
            throw new \LogicException('Le pointage est déjà en pause.');
        }

        $pointage->setStatut(Pointage::STATUT_EN_PAUSE);
        $pointage->setHeurePauseDebut(new \DateTime());

        $this->em->persist($pointage);
        $this->em->flush();

        return $pointage;
    }

    /**
     * Reprend un pointage EN_PAUSE et cumule la durée de pause.
     */
    public function reprendrePointage(User $user): Pointage
    {
        $pointage = $this->pointageRepository->findEnPauseByUtilisateur($user);

        if (null === $pointage) {
            throw new \LogicException('Aucun pointage en pause pour cet utilisateur.');
        }

        $pauseDebut = $pointage->getHeurePauseDebut();
        if (null !== $pauseDebut) {
            $dureeMinutes = (int) (((new \DateTime())->getTimestamp() - $pauseDebut->getTimestamp()) / 60);
            $pointage->setDureesPauseMinutes($pointage->getDureesPauseMinutes() + $dureeMinutes);
        }

        $pointage->setStatut(Pointage::STATUT_EN_COURS);
        $pointage->setHeurePauseDebut(null);

        $this->em->persist($pointage);
        $this->em->flush();

        return $pointage;
    }

    /**
     * Clôture les pointages des jours précédents restés ouverts.
     */
    private function cloturerAnciensPointages(User $user): void
    {
        $anciens = $this->pointageRepository->createQueryBuilder('p')
            ->where('p.utilisateur = :user')
            ->andWhere('p.statut IN (:statuts)')
            ->andWhere('p.dateJour < :today')
            ->setParameter('user', $user)
            ->setParameter('statuts', [Pointage::STATUT_EN_COURS, Pointage::STATUT_HORS_ZONE, Pointage::STATUT_EN_PAUSE])
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getResult();

        foreach ($anciens as $pointage) {
            $pointage->setStatut(Pointage::STATUT_ANOMALIE);
            $pointage->setEstAnomalie(true);
            if (null === $pointage->getHeureDepart()) {
                $fin = (clone $pointage->getHeureArrivee())->setTime(23, 59, 59);
                $pointage->setHeureDepart($fin);
            }
            $this->em->persist($pointage);
        }

        if (!empty($anciens)) {
            $this->em->flush();
        }
    }

    /**
     * Détecte les anomalies de durée sur un pointage terminé.
     */
    public function verifierAnomalies(Pointage $pointage): void
    {
        $duree = $pointage->getDureeMinutes();
        if (null === $duree) {
            return;
        }

        if ($duree > self::DUREE_MAX_MIN) {
            $pointage->setEstAnomalie(true);
            $this->em->persist($pointage);
            $this->em->flush();

            $this->alerteService->creerAlerteEcartHoraire(
                $pointage->getUtilisateur(),
                $pointage,
                $duree,
            );
        }
    }
}
