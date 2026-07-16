<?php

namespace App\Repository;

use App\Entity\Demande;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Demande>
 */
class DemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demande::class);
    }

    public function findByUtilisateur(User $user): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('d.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findEnAttente(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.utilisateur', 'u')
            ->addSelect('u')
            ->where('d.statut = :statut')
            ->setParameter('statut', Demande::STATUT_EN_ATTENTE)
            ->orderBy('d.dateCreation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithUsers(): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.utilisateur', 'u')
            ->addSelect('u')
            ->orderBy('d.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count approved days for a given user, type, and year.
     */
    public function countJoursApprouves(User $user, string $type, int $annee): int
    {
        $debut = new \DateTime("$annee-01-01");
        $fin   = new \DateTime("$annee-12-31");

        $demandes = $this->createQueryBuilder('d')
            ->where('d.utilisateur = :user')
            ->andWhere('d.typeDemande = :type')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.dateDebut >= :debut')
            ->andWhere('d.dateDebut <= :fin')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('statut', Demande::STATUT_APPROUVEE)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();

        $total = 0;
        foreach ($demandes as $demande) {
            $total += $demande->getDureeJours();
        }

        return $total;
    }

    /**
     * Count all approved absence days (CONGE + RTT + ABSENCE) for a user in a given year.
     */
    public function countAbsencesAnnee(User $user, int $annee): int
    {
        $debut = new \DateTime("$annee-01-01");
        $fin   = new \DateTime("$annee-12-31");

        $demandes = $this->createQueryBuilder('d')
            ->where('d.utilisateur = :user')
            ->andWhere('d.typeDemande IN (:types)')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.dateDebut >= :debut')
            ->andWhere('d.dateDebut <= :fin')
            ->setParameter('user', $user)
            ->setParameter('types', [Demande::TYPE_CONGE, Demande::TYPE_RTT, Demande::TYPE_ABSENCE])
            ->setParameter('statut', Demande::STATUT_APPROUVEE)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();

        $total = 0;
        foreach ($demandes as $demande) {
            $total += $demande->getDureeJours();
        }

        return $total;
    }

    public function save(Demande $demande, bool $flush = true): void
    {
        $this->getEntityManager()->persist($demande);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
