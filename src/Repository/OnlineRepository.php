<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Online;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Online>
 *
 * @method Online|null find($id, $lockMode = null, $lockVersion = null)
 * @method Online|null findOneBy(array $criteria, array $orderBy = null)
 * @method Online[]    findAll()
 * @method Online[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OnlineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Online::class);
    }

    public function cleanup(\DateTime $lifetime): void
    {
        $this->getEntityManager()->createQuery('DELETE FROM App\Entity\Online o WHERE o.datetime < :lifetime')
             ->setParameter('lifetime', $lifetime)
             ->execute();
    }

    public function findOneByIpAndBrowser(string $ip, string $browser): ?Online
    {
        return $this->createQueryBuilder('o')
           ->where('o.ip = :ip')
           ->andWhere('o.browser = :browser')
            ->setParameter('ip', $ip)
            ->setParameter('browser', $browser)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
