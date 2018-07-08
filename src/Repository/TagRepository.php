<?php
namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

class TagRepository extends EntityRepository
{
    /**
     * @param string $search
     * @param int|null $limit
     * @return Tag[]
     */
    public function findLikeName($search, $limit = 10)
    {
        $qb = $this->createQueryBuilder('t');

        $search = '%' . \addcslashes($search, '%_') . '%';

        $qb->where('t.name LIKE :search');
        $qb->setParameter('search', $search);

        $qb->orderBy('t.count', 'DESC');
        $qb->addOrderBy('t.name', 'ASC');

        $qb->setMaxResults($limit);

        return $qb->getQuery()->execute();
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function  getTagsQuery()
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.count', 'DESC')
            ->addOrderBy('t.name', 'ASC')
            ->getQuery();
    }


    /**
     * @param string $name
     *
     * @return Tag|null
     */
    public function getTagByName($name)
    {
        return $this->createQueryBuilder('t')

            ->where('t.name = :name')
            ->setParameter('name', $name)

            ->getQuery()
            ->getOneOrNullResult();
    }


    /**
     * @param array $names Array of tag names
     * @return ArrayCollection|null
     */
    public function makeTags(array $names)
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
    public function findEmptyTags()
    {
        return $this->createQueryBuilder('t')
            ->where('t.count = 0')
            ->getQuery()
            ->getResult();
    }
}
