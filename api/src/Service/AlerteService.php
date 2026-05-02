<?php

namespace App\Service;

use App\Entity\Alerte;
use App\Entity\Pointage;
use App\Entity\User;
use App\Repository\AlerteRepository;
use App\Repository\PointageRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlerteService
{
    public function __construct(
        private readonly AlerteRepository  $alerteRepository,
        private readonly PointageRepository $pointageRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function creerAlerte(
        string $type,
        User $user,
        ?Pointage $pointage,
        string $message,
    ): Alerte {
        $alerte = new Alerte();
        $alerte->setTypeAlerte($type);
        $alerte->setUtilisateur($user);
        $alerte->setPointage($pointage);
        $alerte->setMessage($message);

        $this->alerteRepository->save($alerte);

        return $alerte;
    }

    /**
     * Tâche planifiée : détecte les pointages EN_COURS depuis > 10h et crée des alertes.
     * À appeler via Symfony Scheduler chaque soir à 23h30.
     */
    public function verifierOubliDepart(): int
    {
        $pointagesOublies = $this->pointageRepository->findEnCoursDepuisPlus(10);
        $count = 0;

        foreach ($pointagesOublies as $pointage) {
            if ($this->alerteRepository->existeAlertePointage($pointage->getId(), Alerte::TYPE_OUBLI_DEPART)) {
                continue; // Alerte déjà créée
            }

            // Clôturer le pointage en anomalie
            $pointage->setStatut(Pointage::STATUT_ANOMALIE);
            $pointage->setEstAnomalie(true);
            $this->em->persist($pointage);

            $heureArrivee = $pointage->getHeureArrivee()?->format('H:i');
            $this->creerAlerte(
                Alerte::TYPE_OUBLI_DEPART,
                $pointage->getUtilisateur(),
                $pointage,
                sprintf(
                    'Départ non enregistré : pointage du %s à %s toujours ouvert.',
                    $pointage->getDateJour()?->format('d/m/Y'),
                    $heureArrivee,
                ),
            );
            ++$count;
        }

        $this->em->flush();

        return $count;
    }

    public function creerAlerteHorsZone(User $user, Pointage $pointage): Alerte
    {
        return $this->creerAlerte(
            Alerte::TYPE_HORS_ZONE,
            $user,
            $pointage,
            sprintf(
                'Pointage hors zone le %s à %s. Vous n\'étiez pas dans la zone géographique d\'un site.',
                $pointage->getDateJour()?->format('d/m/Y'),
                $pointage->getHeureArrivee()?->format('H:i'),
            ),
        );
    }

    public function creerAlerteEcartHoraire(User $user, Pointage $pointage, int $dureeMinutes): Alerte
    {
        $heures = intdiv($dureeMinutes, 60);
        $minutes = $dureeMinutes % 60;

        return $this->creerAlerte(
            Alerte::TYPE_ECART_HORAIRE,
            $user,
            $pointage,
            sprintf(
                'Durée de présence anormale le %s : %dh%02d (au lieu de ~8h).',
                $pointage->getDateJour()?->format('d/m/Y'),
                $heures,
                $minutes,
            ),
        );
    }
}
