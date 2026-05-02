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

    public function save(Demande $demande, bool $flush = true): void
    {
        $this->getEntityManager()->persist($demande);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
