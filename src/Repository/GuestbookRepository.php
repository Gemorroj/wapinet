<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Guestbook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Guestbook>
 *
 * @method Guestbook|null find($id, $lockMode = null, $lockVersion = null)
 * @method Guestbook|null findOneBy(array $criteria, array $orderBy = null)
 * @method Guestbook[]    findAll()
 * @method Guestbook[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GuestbookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guestbook::class);
    }

    public function countAll(): int
    {
        return $this->count([]);
    }

    public function getListQuery(): Query
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.id', 'DESC')
            ->getQuery();
    }
}
