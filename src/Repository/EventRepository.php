<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @return Event[]
     */
    public function findNeedEmail(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.needEmail = 1')
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findEventsQuery(User $user): Query
    {
        return $this->createQueryBuilder('e')
            ->where('e.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.id', 'DESC')
            ->getQuery();
    }

    public function removeEvents(\DateTimeInterface $dateTime): int
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'e')
            ->where('e.createdAt <= :date')
            ->setParameter('date', $dateTime)
            ->getQuery()
            ->execute();
    }
}
