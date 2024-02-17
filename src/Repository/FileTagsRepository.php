<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FileTags;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FileTags>
 *
 * @method FileTags|null find($id, $lockMode = null, $lockVersion = null)
 * @method FileTags|null findOneBy(array $criteria, array $orderBy = null)
 * @method FileTags[]    findAll()
 * @method FileTags[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FileTagsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileTags::class);
    }
}
