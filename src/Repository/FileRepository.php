<?php

namespace App\Repository;

use App\Dto\Api\File\FileListQuery;
use App\Entity\File;
use App\Entity\Tag;
use App\Entity\User;
use App\Enum\FileCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<File>
 *
 * @method File|null find($id, $lockMode = null, $lockVersion = null)
 * @method File|null findOneBy(array $criteria, array $orderBy = null)
 * @method File[]    findAll()
 * @method File[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    /**
     * @return array{
     *     count_files: int|string,
     *     size_files: int|string|null,
     *     count_views: int|string|null,
     *     users: list<array{username: string, uploads: int|string}>
     * }
     */
    public function getStatistic(int $maxUsers = 10): array
    {
        $em = $this->getEntityManager();
        $countFiles = $em->createQuery('SELECT COUNT(f.id) FROM App\Entity\File f');
        $sizeFiles = $em->createQuery('SELECT SUM(f.fileSize) FROM App\Entity\File f');
        $countViews = $em->createQuery('SELECT SUM(f.countViews) FROM App\Entity\File f');

        $users = $em->createQuery('
            SELECT u.username, COUNT(f.id) AS uploads
            FROM App\Entity\File f
            INNER JOIN App\Entity\User u ON f.user = u
            WHERE u.enabled = 1
            GROUP BY u.id
            ORDER BY uploads DESC
        ');
        $users->setMaxResults($maxUsers);

        return [
            'count_files' => $countFiles->getSingleScalarResult(),
            'size_files' => $sizeFiles->getSingleScalarResult(),
            'count_views' => $countViews->getSingleScalarResult(),
            'users' => $users->getResult(),
        ];
    }

    public function countHidden(): int
    {
        return $this->count([
            'password' => null,
            'hidden' => true,
        ]);
    }

    public function countAll(): int
    {
        return $this->count([
            'password' => null,
            'hidden' => false,
        ]);
    }

    public function countDate(\DateTime $datetimeStart, ?\DateTime $datetimeEnd = null): int
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->andWhere('f.createdAt > :date_start');

        $queryBuilder->setParameter('date_start', $datetimeStart);

        if ($datetimeEnd) {
            $queryBuilder->andWhere('f.createdAt < :date_end');
            $queryBuilder->setParameter('date_end', $datetimeEnd);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function countCategory(string $category): int
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0');

        $this->addCategoryMime($queryBuilder, $category);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function countUser(User $user): int
    {
        return $this->count([
            'password' => null,
            'hidden' => false,
            'user' => $user,
        ]);
    }

    public function getListQuery(?\DateTime $datetimeStart = null, ?\DateTime $datetimeEnd = null, ?string $category = null): Query
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->orderBy('f.id', \SortDirection::Descending);

        if ($datetimeStart) {
            $queryBuilder->andWhere('f.createdAt > :date_start');
            $queryBuilder->setParameter('date_start', $datetimeStart);
        }
        if ($datetimeEnd) {
            $queryBuilder->andWhere('f.createdAt < :date_end');
            $queryBuilder->setParameter('date_end', $datetimeEnd);
        }

        $this->addCategoryMime($queryBuilder, $category);

        return $queryBuilder->getQuery();
    }

    public function getHiddenQuery(): Query
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->andWhere('f.hidden = 1')
            ->orderBy('f.id', \SortDirection::Descending);

        return $queryBuilder->getQuery();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPrevFile(int $id, ?string $category = null): ?File
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->where('f.id > :id')
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->setParameter('id', $id)
            ->orderBy('f.id', \SortDirection::Ascending)
            ->setMaxResults(1);

        $this->addCategoryMime($queryBuilder, $category);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNextFile(int $id, ?string $category = null): ?File
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->where('f.id < :id')
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->setParameter('id', $id)
            ->orderBy('f.id', \SortDirection::Descending)
            ->setMaxResults(1);

        $this->addCategoryMime($queryBuilder, $category);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function addCategoryMime(QueryBuilder $qb, ?string $category): void
    {
        if (null === $category) {
            return;
        }

        $parsed = FileCategory::tryFrom($category);

        if (null !== $parsed) {
            FileCategoryFilter::apply($qb, $parsed);
        }
    }

    public function getUserFilesQuery(User $user): Query
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->innerJoin('f.user', 'u')

            ->where('u = :user')
            ->setParameter('user', $user)
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')

            ->orderBy('f.id', \SortDirection::Descending)
        ;

        return $queryBuilder->getQuery();
    }

    public function getTagFilesQuery(Tag $tag): Query
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->innerJoin('f.fileTags', 'ft')

            ->where('ft.tag = :tag')
            ->setParameter('tag', $tag)
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')

            ->orderBy('f.id', \SortDirection::Descending)
        ;

        return $queryBuilder->getQuery();
    }

    /**
     * @param list<int> $ids
     *
     * @return File[]
     */
    public function findPublicByIds(array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $files = $this->createQueryBuilder('f')
            ->leftJoin('f.user', 'u')
            ->addSelect('u')
            ->where('f.id IN (:ids)')
            ->andWhere('f.hidden = 0')
            ->andWhere('f.password IS NULL')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];

        foreach ($files as $file) {
            $byId[(int) $file->getId()] = $file;
        }

        $ordered = [];

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }

        return $ordered;
    }

    public function getApiListQueryBuilder(FileListQuery $query, ?User $viewer): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->leftJoin('f.user', 'u')
            ->addSelect('u');

        $isAdmin = null !== $viewer && $viewer->isAdmin();

        if ($query->filter->mine) {
            $queryBuilder->andWhere('f.user = :mine')->setParameter('mine', $viewer);
        } elseif (!$isAdmin) {
            $queryBuilder
                ->andWhere('f.hidden = 0')
                ->andWhere('f.password IS NULL');
        }

        if (null !== $query->filter->hidden) {
            $queryBuilder->andWhere('f.hidden = :hidden')->setParameter('hidden', $query->filter->hidden);
        }

        if (null !== $query->filter->q && '' !== \trim($query->filter->q)) {
            $queryBuilder
                ->andWhere('f.originalFileName LIKE :q OR f.description LIKE :q')
                ->setParameter('q', '%'.\addcslashes(\trim($query->filter->q), '%_').'%');
        }

        if (null !== $query->filter->category) {
            $this->addCategoryMime($queryBuilder, $query->filter->category);
        }

        if (null !== $query->filter->tag && '' !== $query->filter->tag) {
            $queryBuilder
                ->innerJoin('f.fileTags', 'ft')
                ->innerJoin('ft.tag', 't')
                ->andWhere('t.name = :tag')
                ->setParameter('tag', $query->filter->tag);
        }

        if (null !== $query->filter->user && '' !== $query->filter->user) {
            $queryBuilder
                ->andWhere('u.username = :username')
                ->setParameter('username', $query->filter->user);
        }

        if ('today' === $query->filter->date) {
            $queryBuilder
                ->andWhere('f.createdAt >= :dateStart')
                ->setParameter('dateStart', new \DateTimeImmutable('today'));
        } elseif ('yesterday' === $query->filter->date) {
            $queryBuilder
                ->andWhere('f.createdAt >= :dateStart')
                ->andWhere('f.createdAt < :dateEnd')
                ->setParameter('dateStart', new \DateTimeImmutable('yesterday'))
                ->setParameter('dateEnd', new \DateTimeImmutable('today'));
        }

        match ($query->sort) {
            'oldest' => $queryBuilder->orderBy('f.id', \SortDirection::Ascending),
            'name' => $queryBuilder->orderBy('f.originalFileName', \SortDirection::Ascending)->addOrderBy('f.id', \SortDirection::Descending),
            'views' => $queryBuilder->orderBy('f.countViews', \SortDirection::Descending)->addOrderBy('f.id', \SortDirection::Descending),
            default => $queryBuilder->orderBy('f.id', \SortDirection::Descending),
        };

        return $queryBuilder;
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof File) {
            throw new \InvalidArgumentException('Expected an instance of '.File::class.'.');
        }

        $user->setPassword($newHashedPassword);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
