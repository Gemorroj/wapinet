<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;
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
        $q->setMaxResults(100); //

        return $q->getQuery();
    }
}
