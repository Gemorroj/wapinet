<?php

namespace App\Service;

use App\Pagerfanta\Manticore\Bridge;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class Manticore
{
    private readonly Connection $connection;
    private int $maxPerPage = 10;

    public function __construct(private readonly EntityManagerInterface $entityManager, ParameterBagInterface $parameterBag)
    {
        $this->connection = DriverManager::getConnection([
            'user' => '',
            'password' => '',
            'host' => $parameterBag->get('manticore_host'),
            'port' => $parameterBag->get('manticore_port'),
            'driver' => 'pdo_mysql',
        ]);
    }

    /**
     * @param string[] $fields
     */
    public function getPage(string $entityClass, string $source, array $fields, string $search, int $page = 1, string $orderBy = 'WEIGHT()'): Pagerfanta
    {
        // "SELECT * FROM gist WHERE MATCH('(@(subject,body) test)') ORDER BY WEIGHT() DESC LIMIT 0, 10"
        // https://manual.manticoresearch.com/Searching/Full_text_matching/Operators

        $pkColumn = $this->entityManager->getClassMetadata($entityClass)->getSingleIdentifierColumnName();
        $result = $this->connection->createQueryBuilder()
            ->select($pkColumn)
            ->from($source)
            ->where('MATCH(\'(@('.\implode(',', $fields).') '.$this->escapeMatch($search).')\')')
            ->orderBy($orderBy, 'DESC')
            ->fetchAllAssociative()
        ;
        $meta = $this->connection->executeQuery('SHOW META LIKE \'total_found\'')->fetchAssociative();

        $bridge = new Bridge($this->entityManager, $entityClass);
        $bridge->setManticoreResult($result, (int) $meta['Value']);

        $pager = $bridge->getPager();
        $pager->setMaxPerPage($this->maxPerPage);
        $pager->setCurrentPage($page);

        return $pager;
    }

    private function escapeMatch(string $string): string
    {
        $escapeFullChars = [
            '\\' => '\\\\',
            '(' => '\(',
            ')' => '\)',
            '|' => '\|',
            '-' => '\-',
            '!' => '\!',
            '@' => '\@',
            '~' => '\~',
            '"' => '\"',
            '&' => '\&',
            '/' => '\/',
            '^' => '\^',
            '$' => '\$',
            '=' => '\=',
            '<' => '\<',
        ];

        return \mb_strtolower(\str_replace(\array_keys($escapeFullChars), \array_values($escapeFullChars), $string));
    }
}
