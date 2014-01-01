<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Wapinet\UserBundle\Entity\User;

class FileRepository extends EntityRepository
{
    /**
     * @param int $maxUsers
     * @return array
     */
    public function getStatistic($maxUsers = 10)
    {
        $em = $this->getEntityManager();
        $countFiles = $em->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f');
        $countViews = $em->createQuery('SELECT SUM(f.countViews) FROM Wapinet\Bundle\Entity\File f');

        $users = $em->createQuery('
            SELECT u.username, COUNT(f.id) AS uploads
            FROM Wapinet\Bundle\Entity\File f
            INNER JOIN Wapinet\UserBundle\Entity\User u WITH f.user = u
            WHERE u.enabled = 1 AND u.locked = 0 AND u.expired = 0
            GROUP BY u.id
            ORDER BY uploads DESC
        ');
        $users->setMaxResults($maxUsers);

        return array(
            'count_files' => $countFiles->getSingleScalarResult(),
            'count_views' => $countViews->getSingleScalarResult(),
            'users' => $users->getResult(),
        );
    }


    /**
     * @return int
     */
    public function countAll()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f WHERE f.password IS NULL');
        return $q->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function countToday()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f WHERE f.password IS NULL AND f.createdAt > :date');
        $q->setParameter('date', new \DateTime('today'));
        return $q->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function countYesterday()
    {
        $q = $this->getEntityManager()->createQuery('SELECT COUNT(f.id) FROM Wapinet\Bundle\Entity\File f WHERE f.password IS NULL AND f.createdAt > :date_start AND f.createdAt < :date_end');
        $q->setParameter('date_start', new \DateTime('yesterday'));
        $q->setParameter('date_end', new \DateTime('today'));
        return $q->getSingleScalarResult();
    }

    /**
     * @param string $category
     * @return int
     */
    public function countCategory($category)
    {
        $q = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.password IS NULL');

        $this->addCategoryMime($q, $category);
        $q = $q->getQuery();

        return $q->getSingleScalarResult();
    }

    /**
     * @param User $user
     * @return int
     */
    public function countUser(User $user)
    {
        $q = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.password IS NULL')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery();

        return $q->getSingleScalarResult();
    }

    /**
     * @param string $date
     * @param string $category
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery($date = null, $category = null)
    {
        $q = $this->createQueryBuilder('f')
            ->where('f.password IS NULL')
            ->orderBy('f.id', 'DESC');

        switch ($date) {
            case 'today':
                $q->andWhere('f.createdAt > :date');
                $q->setParameter('date', new \DateTime('today'));
                break;

            case 'yesterday':
                $q->andWhere('f.createdAt > :date_start');
                $q->andWhere('f.createdAt < :date_end');
                $q->setParameter('date_start', new \DateTime('yesterday'));
                $q->setParameter('date_end', new \DateTime('today'));
                break;
        }

        $this->addCategoryMime($q, $category);

        return $q->getQuery();
    }


    /**
     * @param QueryBuilder $q
     * @param string $mimeType
     */
    private function addCategoryMime(QueryBuilder $q, $mimeType)
    {
        switch ($mimeType) {
            case 'video':
            case 'audio':
            case 'image':
            case 'text':
                $mimeType = addcslashes($mimeType, '%_') . '/%';
                $q->andWhere('f.mimeType LIKE :mime_type');
                $q->setParameter('mime_type', $mimeType);
                break;
            case 'office':
                $q->andWhere('f.mimeType IN(:pdf, :doc, :docx, :xls, :xlsx)');
                $q->setParameter('pdf', 'application/pdf');
                $q->setParameter('doc', 'application/msword');
                $q->setParameter('docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                $q->setParameter('xls', 'application/vnd.ms-excel');
                $q->setParameter('xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                break;
            case 'archive':
                $q->andWhere('f.mimeType IN(:zip, :rar, :bz, :bz2, :p7z, :tar, :cab, :iso, :gz, :ace, :lzh)');
                $q->setParameter('zip', 'application/zip');
                $q->setParameter('rar', 'application/x-rar-compressed');
                $q->setParameter('bz', 'application/x-bzip');
                $q->setParameter('bz2', 'application/x-bzip2');
                $q->setParameter('p7z', 'application/x-7z-compressed'); //7z
                $q->setParameter('tar', 'application/x-tar');
                $q->setParameter('cab', 'application/vnd.ms-cab-compressed');
                $q->setParameter('iso', 'application/x-iso9660-image');
                $q->setParameter('gz', 'application/x-gzip');
                $q->setParameter('ace', 'application/x-ace-compressed');
                $q->setParameter('lzh', 'application/x-lzh-compressed');
                break;
            case 'android':
                $q->andWhere('f.mimeType = :mime_type');
                $q->setParameter('mime_type', 'application/vnd.android.package-archive');
                break;
            case 'java':
                $q->andWhere('f.mimeType = :mime_type');
                $q->setParameter('mime_type', 'application/java-archive');
                break;
        }
    }


    /**
     * @param string|null $search
     * @return \Doctrine\ORM\Query
     */
    public function getSearchQuery($search = null)
    {
        $q = $this->createQueryBuilder('f');

        $search = '%' . addcslashes($search, '%_') . '%';

        $q->where('f.originalFileName LIKE :search');
        $q->orWhere('f.description LIKE :search');
        $q->setParameter('search', $search);

        $q->andWhere('f.password IS NULL');
        $q->orderBy('f.id', 'DESC');

        return $q->getQuery();
    }


    /**
     * @return \Doctrine\ORM\Query
     */
    public function getTagsQuery()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('t')
            ->from('Wapinet\Bundle\Entity\Tag', 't')
            ->where('t.count > 0')
            ->orderBy('t.count', 'DESC')
            ->getQuery()
            ;
    }

    /**
     * @param string $name
     *
     * @return Tag|null
     */
    public function getTagByName($name)
    {
        return $this->getEntityManager()->createQueryBuilder()

            ->select('t')
            ->from('Wapinet\Bundle\Entity\Tag', 't')

            ->where('t.name = :name')
            ->setParameter('name', $name)
            ->andWhere('t.count > 0')

            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * @param User $user
     *
     * @return \Doctrine\ORM\Query
     */
    public function getUserFilesQuery(User $user)
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('f')
            ->from('Wapinet\Bundle\Entity\File', 'f')
            ->innerJoin('f.user', 'u')

            ->where('u = :user')
            ->setParameter('user', $user)
            ->andWhere('f.password IS NULL')

            ->orderBy('f.id', 'DESC')

            ->getQuery()
        ;
        return $q;
    }

    /**
     * @param Tag $tag
     *
     * @return \Doctrine\ORM\Query
     */
    public function getTagFilesQuery(Tag $tag)
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('f')
            ->from('Wapinet\Bundle\Entity\File', 'f')
            ->innerJoin('f.tags', 't')

            ->where('t = :tag')
            ->setParameter('tag', $tag)
            ->andWhere('f.password IS NULL')

            ->orderBy('f.id', 'DESC')

            ->getQuery()
            ;
        return $q;
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

        $names = array_unique($names);

        $builder = $this->getEntityManager()->createQueryBuilder();

        $tags = $builder
            ->select('t')
            ->from('Wapinet\Bundle\Entity\Tag', 't')

            ->where($builder->expr()->in('t.name', $names))

            ->getQuery()
            ->getResult()
        ;

        $loadedNames = array();
        /** @var Tag $loadedTag */
        foreach ($tags as &$loadedTag) {
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

        $missingNames = array_udiff($names, $loadedNames, 'strcasecmp');
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
