<?php

namespace App\Pagerfanta\Manticore;

use Doctrine\ORM\EntityManagerInterface;
use Foolz\SphinxQL\Drivers\ResultSetInterface;
use Pagerfanta\Pagerfanta;

/**
 * Pagerfanta Bridge.
 *
 * @author Stephane PY <py.stephane1@gmail.com>
 * @author Nikola Petkanski <nikola@petkanski.com>
 * @author Gemorroj <wapinet@mail.ru>
 */
final class Bridge
{
    /**
     * The results obtained from Manticore.
     */
    private ?array $results = null;

    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly string $entityClass)
    {
    }

    private function extractPksFromResults(): array
    {
        $matches = $this->results['matches'] ?? [];
        $pks = [];

        foreach ($matches as $match) {
            $pks[] = $match['id'];
        }

        return $pks;
    }

    public function setManticoreResult(ResultSetInterface $result, ResultSetInterface $resultMeta): self
    {
        $this->results = [
            'matches' => $result->fetchAllAssoc(),
            'meta' => $this->parseMeta($resultMeta),
        ];

        return $this;
    }

    private function parseMeta(ResultSetInterface $resultMeta): array
    {
        $data = [];

        foreach ($resultMeta as $value) {
            $data[$value['Variable_name']] = $value['Value'];
        }

        return $data;
    }

    public function getPager(): Pagerfanta
    {
        if (null === $this->results) {
            throw new \RuntimeException('You should define manticore results on '.self::class);
        }

        $adapter = new Adapter($this->results['meta']['total_found'] ?? 0, $this->getResults());

        return new Pagerfanta($adapter);
    }

    private function getResults(): array
    {
        $pks = $this->extractPksFromResults();

        $results = [];

        if ($pks) {
            $pkColumn = $this->entityManager->getClassMetadata($this->entityClass)->getSingleIdentifierColumnName();

            $qb = $this->entityManager->createQueryBuilder();
            $q = $qb->select('r')
                ->from($this->entityClass, 'r INDEX BY r.'.$pkColumn)
                ->where($qb->expr()->in('r.'.$pkColumn, $pks))
                // ->addOrderBy('FIELD(r.id,...)', 'ASC')
                ->getQuery();

            $unorderedResults = $q->getResult();

            foreach ($pks as $pk) {
                if (isset($unorderedResults[$pk])) {
                    $results[$pk] = $unorderedResults[$pk];
                }
            }
        }

        return $results;
    }
}
