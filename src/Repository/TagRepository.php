<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return Tag[]
     */
    public function findLikeName(string $search, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('t');

        $search = '%'.\addcslashes($search, '%_').'%';

        $qb->where('t.name LIKE :search');
        $qb->setParameter('search', $search);

        $qb->orderBy('t.count', 'DESC');
        $qb->addOrderBy('t.name', 'ASC');

        $qb->setMaxResults($limit);

        return $qb->getQuery()->execute();
    }

    public function getTagsQuery(): \Doctrine\ORM\Query
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.count', 'DESC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery();
    }

    public function getTagByName(string $name): ?Tag
    {
        return $this->createQueryBuilder('t')

            ->where('t.name = :name')
            ->setParameter('name', $name)

            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array $names Array of tag names
     */
    public function makeTags(array $names): ?ArrayCollection
    {
        if (!$names) {
            return null;
        }

        $names = \array_unique($names);

        $tags = $this->findBy(['name' => $names]);

        $loadedNames = [];
        /** @var Tag $loadedTag */
        foreach ($tags as $loadedTag) {
            $loadedNames[] = $loadedTag->getName();
        }

        $missingNames = \array_udiff($names, $loadedNames, 'strcasecmp');
        if ($missingNames) {
            foreach ($missingNames as $name) {
                $tag = new Tag();
                $tag->setName($name);

                $tags[] = $tag;
            }
        }

        return new ArrayCollection($tags);
    }

    /**
     * @return Tag[]
     */
    public function findEmptyTags(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.count = 0')
            ->getQuery()
            ->getResult();
    }
}
