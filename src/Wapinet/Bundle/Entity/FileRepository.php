<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class FileRepository extends EntityRepository
{
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

        //TODO: кэширование
        //$q->setResultCacheDriver(new \Doctrine\Common\Cache\PhpFileCache(\AppKernel::getTmpDir() . '/doctrine'));
        //$q->useResultCache(true, 300, 'category_' . $category);

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
     * @param string $search
     * @param bool $useDescription
     * @param array|null $categories
     * @param \DateTime|null $createdAfter
     * @param \DateTime|null $createdBefore
     * @return \Doctrine\ORM\Query
     */
    public function getSearchQuery($search, $useDescription = true, array $categories = null, \DateTime $createdAfter = null, \DateTime $createdBefore = null)
    {
        $q = $this->createQueryBuilder('f');

        $search = '%' . addcslashes($search, '%_') . '%';
        // 15 символов - это результат uniqid + _
        $q->where('SUBSTRING(f.fileName, 15) LIKE :search');
        if (true === $useDescription) {
            $q->orWhere('f.description LIKE :search');
        }
        $q->setParameter('search', $search);

        foreach ($categories as $category) {
            $this->addCategoryMime($q, $category);
        }

        if (null !== $createdAfter) {
            $q->andWhere('f.createdAt >= :created_after');
            $q->setParameter('created_after', $createdAfter);
        }
        if (null !== $createdBefore) {
            $q->andWhere('f.createdAt <= :created_before');
            $q->setParameter('created_before', $createdBefore);
        }

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
            ->select('f')
            ->from('Wapinet\Bundle\Entity\Tag', 'f')
            // ->orderBy('t.name', 'ASC')
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

            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    /**
     * @param Tag $tag
     *
     * @return \Doctrine\ORM\Query
     */
    public function getFilesQuery(Tag $tag)
    {
        $q = $this->createQueryBuilder('f')

            //  AND t.file_id = f.id
            ->innerJoin('Wapinet\Bundle\Entity\Tag', 't')
            ->where('t = :tag')
            ->setParameter('tag', $tag)

            // ->orderBy('f.createdAt', 'DESC')

            ->getQuery()
            ;
        var_dump($q->getDQL());

        return $q;


        return $this->createQueryBuilder('f')

            //  AND t.file_id = f.id
            //->innerJoin('Wapinet\Bundle\Entity\Tag', 't', Join::WITH, 't = :tag')
            ->innerJoin('file_tag', 'ft', Join::WITH, 'ft.tag = :tag')
            //->where('f.tags = :tag')
            ->setParameter('tag', $tag->getId())

            // ->orderBy('f.createdAt', 'DESC')

            ->getQuery()
            ;
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
        foreach ($tags as $tag) {
            $loadedNames[] = $tag->getName();
        }

        $missingNames = array_udiff($names, $loadedNames, 'strcasecmp');
        if (sizeof($missingNames)) {
            foreach ($missingNames as $name) {
                $tag = $this->createTag($name, $file);
                //$this->getEntityManager()->persist($tag);
                //$this->getEntityManager()->flush($tag);
                $tags[] = $tag;
            }

            //$this->getEntityManager()->flush();
        }

        return new ArrayCollection($tags);
    }

    /**
     * Creates a new Tag object
     *
     * @param string    $name   Tag name
     * @param File $file
     * @return Tag
     */
    protected function createTag($name, File $file)
    {
        $tag = new Tag();
        $tag->setName($name);
        $tag->setFiles(new ArrayCollection(array($file)));

        return $tag;
    }


    /**
     * Splits an string into an array of valid tag names
     *
     * @param string    $names      String of tag names
     * @param string    $separator  Tag name separator
     * @return array
     */
    public function splitTagNames($names, $separator = ',')
    {
        $tags = explode($separator, $names);
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags, function ($value) { return !empty($value); });

        return array_values($tags);
    }

}
