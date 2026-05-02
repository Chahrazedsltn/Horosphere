<?php

namespace App\Repository;

use App\Entity\Alerte;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Alerte>
 */
class AlerteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alerte::class);
    }

    public function findByUtilisateur(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.pointage', 'p')
            ->addSelect('p')
            ->where('a.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('a.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNonLuesByUtilisateur(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.utilisateur = :user')
            ->andWhere('a.estLue = false')
            ->setParameter('user', $user)
            ->orderBy('a.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countNonLues(User $user): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.utilisateur = :user')
            ->andWhere('a.estLue = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllRecentes(): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.utilisateur', 'u')
            ->addSelect('u')
            ->orderBy('a.dateCreation', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();
    }

    public function existeAlertePointage(int $pointageId, string $type): bool
    {
        $count = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.pointage = :pid')
            ->andWhere('a.typeAlerte = :type')
            ->setParameter('pid', $pointageId)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function save(Alerte $alerte, bool $flush = true): void
    {
        $this->getEntityManager()->persist($alerte);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
