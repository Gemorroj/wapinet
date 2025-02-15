<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserPanel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPanel>
 *
 * @method UserPanel|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPanel|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPanel[]    findAll()
 * @method UserPanel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
final class UserPanelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPanel::class);
    }
}
