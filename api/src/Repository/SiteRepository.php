<?php

namespace App\Repository;

use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Site>
 */
class SiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Site::class);
    }

    public function findActifs(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.geofencingActif = true')
            ->orderBy('s.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Site $site, bool $flush = true): void
    {
        $this->getEntityManager()->persist($site);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Site $site, bool $flush = true): void
    {
        $this->getEntityManager()->remove($site);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
