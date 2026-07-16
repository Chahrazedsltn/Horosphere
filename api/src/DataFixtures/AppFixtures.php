<?php

namespace App\DataFixtures;

use App\Entity\Alerte;
use App\Entity\Demande;
use App\Entity\Pointage;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ─── Sites ──────────────────────────────────────────────
        $site1 = new Site();
        $site1->setNom('Siège Social Paris');
        $site1->setAdresse('1 Rue de la Paix, 75001 Paris');
        $site1->setLatitude(48.8698);
        $site1->setLongitude(2.3309);
        $site1->setRayonMetres(300);
        $site1->setGeofencingActif(true);
        $manager->persist($site1);

        $site2 = new Site();
        $site2->setNom('Agence Lyon Part-Dieu');
        $site2->setAdresse('3 Rue du Docteur Bouchut, 69003 Lyon');
        $site2->setLatitude(45.7602);
        $site2->setLongitude(4.8596);
        $site2->setRayonMetres(200);
        $site2->setGeofencingActif(true);
        $manager->persist($site2);

        $site3 = new Site();
        $site3->setNom('Data Center Marseille');
        $site3->setAdresse('Zone Industrielle, 13015 Marseille');
        $site3->setLatitude(43.3462);
        $site3->setLongitude(5.3827);
        $site3->setRayonMetres(500);
        $site3->setGeofencingActif(false);
        $manager->persist($site3);

        // ─── Admin ──────────────────────────────────────────────
        $admin = new User();
        $admin->setEmail('admin@horosphere.fr');
        $admin->setPrenom('Admin');
        $admin->setNom('Horosphere');
        $admin->setRole(User::ROLE_ADMIN);
        $admin->setDepartement('Direction');
        $admin->setConsentementRgpd(true);
        $admin->setMotDePasse($this->passwordHasher->hashPassword($admin, 'Admin1234!'));
        $admin->setSoldeConges(25);
        $manager->persist($admin);

        // ─── RH ─────────────────────────────────────────────────
        $rh = new User();
        $rh->setEmail('rh@horosphere.fr');
        $rh->setPrenom('Marie');
        $rh->setNom('Dupont');
        $rh->setRole(User::ROLE_RH);
        $rh->setDepartement('Ressources Humaines');
        $rh->setConsentementRgpd(true);
        $rh->setMotDePasse($this->passwordHasher->hashPassword($rh, 'Rh1234!'));
        $rh->setSoldeConges(25);
        $manager->persist($rh);

        // ─── Agents ─────────────────────────────────────────────
        $agents = [];

        $agent1 = new User();
        $agent1->setEmail('agent1@horosphere.fr');
        $agent1->setPrenom('Jean');
        $agent1->setNom('Martin');
        $agent1->setRole(User::ROLE_AGENT);
        $agent1->setDepartement('Développement');
        $agent1->setConsentementRgpd(true);
        $agent1->setMotDePasse($this->passwordHasher->hashPassword($agent1, 'Agent1234!'));
        $agent1->setSoldeConges(25);
        $manager->persist($agent1);
        $agents[] = $agent1;

        $agent2 = new User();
        $agent2->setEmail('agent2@horosphere.fr');
        $agent2->setPrenom('Sophie');
        $agent2->setNom('Bernard');
        $agent2->setRole(User::ROLE_AGENT);
        $agent2->setDepartement('Marketing');
        $agent2->setConsentementRgpd(true);
        $agent2->setMotDePasse($this->passwordHasher->hashPassword($agent2, 'Agent1234!'));
        $agent2->setSoldeConges(25);
        $manager->persist($agent2);
        $agents[] = $agent2;

        $agent3 = new User();
        $agent3->setEmail('agent3@horosphere.fr');
        $agent3->setPrenom('Lucas');
        $agent3->setNom('Petit');
        $agent3->setRole(User::ROLE_AGENT);
        $agent3->setDepartement('Support');
        $agent3->setConsentementRgpd(true);
        $agent3->setMotDePasse($this->passwordHasher->hashPassword($agent3, 'Agent1234!'));
        $agent3->setSoldeConges(25);
        $manager->persist($agent3);
        $agents[] = $agent3;

        $manager->flush(); // Flush pour avoir les IDs

        // ─── Pointages des 30 derniers jours ────────────────────
        $sites = [$site1, $site2];

        foreach ($agents as $agent) {
            for ($i = 30; $i >= 1; $i--) {
                $date = new \DateTime("-{$i} days");
                $weekday = (int) $date->format('N'); // 1=Lun, 7=Dim

                if ($weekday >= 6) continue; // Pas de weekend

                $site = $sites[array_rand($sites)];
                $heureArrivee = clone $date;
                $heureArrivee->setTime(8 + random_int(0, 1), random_int(0, 59));

                $heureDepart = clone $heureArrivee;
                $heureDepart->modify('+' . (7 + random_int(0, 2)) . ' hours');
                $heureDepart->modify('+' . random_int(0, 59) . ' minutes');

                $isAnomalie = (random_int(1, 10) === 1); // 10% de chance d'anomalie

                $pointage = new Pointage();
                $pointage->setUtilisateur($agent);
                $pointage->setSite($site);
                $pointage->setDateJour(clone $date);
                $pointage->setHeureArrivee($heureArrivee);
                $pointage->setHeureDepart($heureDepart);
                $pointage->setStatut($isAnomalie ? Pointage::STATUT_ANOMALIE : Pointage::STATUT_VALIDE);
                $pointage->setEstAnomalie($isAnomalie);
                $pointage->setCoordonneesGps(
                    sprintf('%f,%f', $site->getLatitude() + (random_int(-5, 5) / 10000), $site->getLongitude() + (random_int(-5, 5) / 10000))
                );
                $manager->persist($pointage);

                if ($isAnomalie) {
                    $alerte = new Alerte();
                    $alerte->setUtilisateur($agent);
                    $alerte->setPointage($pointage);
                    $alerte->setTypeAlerte(Alerte::TYPE_ECART_HORAIRE);
                    $alerte->setMessage(sprintf(
                        'Durée de présence anormale le %s.',
                        $date->format('d/m/Y'),
                    ));
                    $manager->persist($alerte);
                }
            }
        }

        // ─── Demandes ───────────────────────────────────────────
        $demande1 = new Demande();
        $demande1->setUtilisateur($agent1);
        $demande1->setTypeDemande(Demande::TYPE_CONGE);
        $demande1->setDateDebut(new \DateTime('+7 days'));
        $demande1->setDateFin(new \DateTime('+14 days'));
        $demande1->setMotif('Vacances d\'été');
        $manager->persist($demande1);

        $demande2 = new Demande();
        $demande2->setUtilisateur($agent2);
        $demande2->setTypeDemande(Demande::TYPE_CORRECTION);
        $demande2->setDateDebut(new \DateTime('-3 days'));
        $demande2->setDateFin(new \DateTime('-3 days'));
        $demande2->setMotif('Oubli de pointer le départ le 31/03');
        $manager->persist($demande2);

        $demande3 = new Demande();
        $demande3->setUtilisateur($agent3);
        $demande3->setTypeDemande(Demande::TYPE_ABSENCE);
        $demande3->setStatut(Demande::STATUT_APPROUVEE);
        $demande3->setDateDebut(new \DateTime('-10 days'));
        $demande3->setDateFin(new \DateTime('-8 days'));
        $demande3->setMotif('Raison personnelle');
        $manager->persist($demande3);

        // ─── Alerte hors zone ───────────────────────────────────
        $alerteHz = new Alerte();
        $alerteHz->setUtilisateur($agent1);
        $alerteHz->setTypeAlerte(Alerte::TYPE_HORS_ZONE);
        $alerteHz->setMessage('Pointage hors zone détecté le ' . (new \DateTime('-5 days'))->format('d/m/Y') . '.');
        $manager->persist($alerteHz);

        $manager->flush();

        echo "✓ Fixtures chargées : 3 sites, 5 utilisateurs (1 admin, 1 RH, 3 agents), pointages sur 30 jours, demandes et alertes.\n";
    }
}
