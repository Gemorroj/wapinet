<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tag>
 *
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
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
     * @param string[] $names Array of tag names
     *
     * @return ArrayCollection<Tag>
     */
    public function makeTags(array $names): ArrayCollection
    {
        if (!$names) {
            return new ArrayCollection();
        }

        $names = \array_unique($names);

        /** @var Tag[] $tags */
        $tags = $this->findBy(['name' => $names]);

        $loadedNames = [];
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
