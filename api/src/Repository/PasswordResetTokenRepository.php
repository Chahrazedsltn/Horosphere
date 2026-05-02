<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function findValidByToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->andWhere('t.expiresAt > :now')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function invalidatePreviousTokens(string $email): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.usedAt', ':now')
            ->where('t.email = :email')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('email', $email)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function purgeExpired(): int
    {
        return (int) $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable('-24 hours'))
            ->getQuery()
            ->execute();
    }

    public function save(PasswordResetToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }
}
