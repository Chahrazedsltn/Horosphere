<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
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

    public function save(Document $document, bool $flush = true): void
    {
        $this->getEntityManager()->persist($document);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
