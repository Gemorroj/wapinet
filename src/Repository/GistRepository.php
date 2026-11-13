<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Gist;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gist>
 *
 * @method Gist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Gist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Gist[]    findAll()
 * @method Gist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class GistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gist::class);
    }

    public function countUser(?User $user = null): int
    {
        if ($user) {
            return $this->count(['user' => $user]);
        }

        return $this->count([]);
    }

    public function getApiListQueryBuilder(?User $user = null, string $sort = 'newest'): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('g')
            ->leftJoin('g.user', 'u')
            ->addSelect('u');

        if (null !== $user) {
            $queryBuilder->andWhere('g.user = :user')->setParameter('user', $user);
        }

        return $queryBuilder->orderBy('g.id', 'oldest' === $sort ? \SortDirection::Ascending : \SortDirection::Descending);
    }

    /**
     * @param list<int> $ids
     *
     * @return Gist[]
     */
    public function findByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $gists = $this->createQueryBuilder('g')
            ->leftJoin('g.user', 'u')
            ->addSelect('u')
            ->where('g.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];

        foreach ($gists as $gist) {
            $byId[(int) $gist->getId()] = $gist;
        }

        $ordered = [];

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }
}
