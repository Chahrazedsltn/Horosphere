<?php

namespace App\Service;

class JoursFeriesService
{
    /**
     * Liste des jours feries fixes (mois => jour => nom).
     */
    private const JOURS_FIXES = [
        1  => [1  => 'Jour de l\'An'],
        5  => [1  => 'Fete du Travail', 8 => 'Victoire 1945'],
        7  => [14 => 'Fete Nationale'],
        8  => [15 => 'Assomption'],
        11 => [1  => 'Toussaint', 11 => 'Armistice'],
        12 => [25 => 'Noel'],
    ];

    /**
     * Retourne tous les jours feries francais pour une annee donnee.
     *
     * @return \DateTime[]
     */
    public function getJoursFeries(int $annee): array
    {
        $joursFeries = [];

        // Jours feries fixes
        foreach (self::JOURS_FIXES as $mois => $jours) {
            foreach ($jours as $jour => $nom) {
                $joursFeries[] = new \DateTime(sprintf('%d-%02d-%02d', $annee, $mois, $jour));
            }
        }

        // Jours feries mobiles bases sur Paques
        $paques = $this->calculerPaques($annee);

        // Lundi de Paques : Paques + 1 jour
        $lundiPaques = (clone $paques)->modify('+1 day');
        $joursFeries[] = $lundiPaques;

        // Ascension : Paques + 39 jours
        $ascension = (clone $paques)->modify('+39 days');
        $joursFeries[] = $ascension;

        // Lundi de Pentecote : Paques + 50 jours
        $lundiPentecote = (clone $paques)->modify('+50 days');
        $joursFeries[] = $lundiPentecote;

        // Trier par date
        usort($joursFeries, fn (\DateTime $a, \DateTime $b) => $a <=> $b);

        return $joursFeries;
    }

    /**
     * Verifie si une date est un jour ferie.
     */
    public function estJourFerie(\DateTimeInterface $date): bool
    {
        return null !== $this->getJourFerieNom($date);
    }

    /**
     * Retourne le nom du jour ferie ou null si la date n'en est pas un.
     */
    public function getJourFerieNom(\DateTimeInterface $date): ?string
    {
        $mois = (int) $date->format('m');
        $jour = (int) $date->format('d');
        $annee = (int) $date->format('Y');

        // Verifier les jours fixes
        if (isset(self::JOURS_FIXES[$mois][$jour])) {
            return self::JOURS_FIXES[$mois][$jour];
        }

        // Verifier les jours mobiles
        $paques = $this->calculerPaques($annee);
        $dateFormatted = $date->format('Y-m-d');

        $mobiles = [
            (clone $paques)->modify('+1 day')->format('Y-m-d')   => 'Lundi de Paques',
            (clone $paques)->modify('+39 days')->format('Y-m-d') => 'Ascension',
            (clone $paques)->modify('+50 days')->format('Y-m-d') => 'Lundi de Pentecote',
        ];

        return $mobiles[$dateFormatted] ?? null;
    }

    /**
     * Calcule la date de Paques pour une annee donnee (algorithme de Meeus/Jones/Butcher).
     * Ne necessite pas l'extension calendar PHP.
     */
    private function calculerPaques(int $annee): \DateTime
    {
        $a = $annee % 19;
        $b = intdiv($annee, 100);
        $c = $annee % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mois = intdiv($h + $l - 7 * $m + 114, 31);
        $jour = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTime(sprintf('%d-%02d-%02d', $annee, $mois, $jour));
    }
}
