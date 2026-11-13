<?php

namespace App\Repository;

use App\Entity\ApiToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\ClockInterface;

/**
 * @extends ServiceEntityRepository<ApiToken>
 */
final class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly ClockInterface $clock)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function findActiveByHash(string $tokenHash): ?ApiToken
    {
        return $this->createQueryBuilder('t')
            ->addSelect('u')
            ->join('t.user', 'u')
            ->where('t.tokenHash = :hash')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('hash', $tokenHash)
            ->setParameter('now', $this->clock->now())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ApiToken[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', $this->clock->now())
            ->orderBy('t.createdAt', \SortDirection::Descending)
            ->addOrderBy('t.id', \SortDirection::Descending)
            ->getQuery()
            ->getResult();
    }
}
