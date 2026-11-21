<?php

declare(strict_types=1);

namespace App\Service\Search;

use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;

final readonly class SearchPager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entityClass
     *
     * @return Pagerfanta<T>
     */
    public function paginate(string $entityClass, SearchResult $result, int $page, int $perPage): Pagerfanta
    {
        $pager = new Pagerfanta(new FixedAdapter($result->total, $this->load($entityClass, $result->ids)));
        $pager->setMaxPerPage($perPage);
        $pager->setCurrentPage(\min(\max(1, $page), \max(1, $pager->getNbPages())));

        return $pager;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $entityClass
     * @param list<int>       $ids
     *
     * @return list<T>
     */
    private function load(string $entityClass, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }

        $metadata = $this->entityManager->getClassMetadata($entityClass);
        $idField = $metadata->getSingleIdentifierFieldName();

        $rows = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from($entityClass, 'r')
            ->where('r.'.$idField.' IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $metadata->getIdentifierValues($row)[$idField]] = $row;
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
