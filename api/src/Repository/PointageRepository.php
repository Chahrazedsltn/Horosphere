<?php

namespace App\Repository;

use App\Entity\Pointage;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pointage>
 */
class PointageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pointage::class);
    }

    public function findByUtilisateur(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.site', 's')
            ->addSelect('s')
            ->where('p.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('p.heureArrivee', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findEnCoursByUtilisateur(User $user): ?Pointage
    {
        return $this->createQueryBuilder('p')
            ->where('p.utilisateur = :user')
            ->andWhere('p.statut IN (:statuts)')
            ->andWhere('p.dateJour = :today')
            ->setParameter('user', $user)
            ->setParameter('statuts', [Pointage::STATUT_EN_COURS, Pointage::STATUT_HORS_ZONE])
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findByPeriode(User $user, \DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.site', 's')
            ->addSelect('s')
            ->where('p.utilisateur = :user')
            ->andWhere('p.dateJour >= :debut')
            ->andWhere('p.dateJour <= :fin')
            ->setParameter('user', $user)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('p.heureArrivee', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findEnPauseByUtilisateur(User $user): ?Pointage
    {
        return $this->createQueryBuilder('p')
            ->where('p.utilisateur = :user')
            ->andWhere('p.statut = :statut')
            ->andWhere('p.dateJour = :today')
            ->setParameter('user', $user)
            ->setParameter('statut', Pointage::STATUT_EN_PAUSE)
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findEnCoursDepuisPlus(int $heures = 10): array
    {
        $limit = new \DateTime("-{$heures} hours");

        return $this->createQueryBuilder('p')
            ->where('p.statut IN (:statuts)')
            ->andWhere('p.heureArrivee < :limit')
            ->setParameter('statuts', [Pointage::STATUT_EN_COURS, Pointage::STATUT_HORS_ZONE])
            ->setParameter('limit', $limit)
            ->getQuery()
            ->getResult();
    }

    public function findAnomalies(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.utilisateur', 'u')
            ->addSelect('u')
            ->where('p.estAnomalie = true')
            ->orderBy('p.heureArrivee', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    public function findAllWithUsers(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.utilisateur', 'u')
            ->leftJoin('p.site', 's')
            ->addSelect('u', 's')
            ->where('p.dateJour >= :debut')
            ->andWhere('p.dateJour <= :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('p.heureArrivee', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTodayAll(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.utilisateur', 'u')
            ->leftJoin('p.site', 's')
            ->addSelect('u', 's')
            ->where('p.dateJour = :today')
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('p.heureArrivee', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(Pointage $pointage, bool $flush = true): void
    {
        $this->getEntityManager()->persist($pointage);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
