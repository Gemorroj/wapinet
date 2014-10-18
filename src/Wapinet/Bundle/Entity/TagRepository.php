<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

class TagRepository extends EntityRepository
{
    /**
     * @param string $search
     * @param int $limit
     * @return Tag[]
     */
    public function findLikeName($search, $limit = 10)
    {
        $qb = $this->createQueryBuilder('t');

        $search = '%' . addcslashes($search, '%_') . '%';

        $qb->where('t.name LIKE :search');
        $qb->setParameter('search', $search);

        $qb->orderBy('t.count', 'DESC');
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
     * Loads or creates multiples tags from a list of tag names
     *
     * @param array  $names   Array of tag names
     * @param File $file
     * @return ArrayCollection
     */
    public function loadOrCreateTags(array $names, File $file)
    {
        if (empty($names)) {
            return array();
        }

        $names = \array_unique($names);

        $builder = $this->createQueryBuilder('t');

        $tags = $builder
            ->where($builder->expr()->in('t.name', $names))

            ->getQuery()
            ->getResult()
        ;

        $loadedNames = array();
        /** @var Tag $loadedTag */
        foreach ($tags as $loadedTag) {
            if (false === $loadedTag->getFiles()->contains($file)) {
                $loadedTag->getFiles()->add($file);

                if (null === $file->getPassword()) {
                    $loadedTag->setCount($loadedTag->getCount() + 1);
                } else {
                    $loadedTag->setCountPassword($loadedTag->getCountPassword() + 1);
                }
            }

            $loadedNames[] = $loadedTag->getName();
        }

        $missingNames = \array_udiff($names, $loadedNames, 'strcasecmp');
        if ($missingNames) {
            foreach ($missingNames as $name) {
                $tag = new Tag();
                $tag->setName($name);
                $tag->setFiles(new ArrayCollection(array($file)));
                if (null === $file->getPassword()) {
                    $tag->setCount(1);
                } else {
                    $tag->setCountPassword(1);
                }
                $tags[] = $tag;
            }
        }

        return new ArrayCollection($tags);
    }
}
