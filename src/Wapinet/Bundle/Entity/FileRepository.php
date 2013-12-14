<?php
namespace Wapinet\Bundle\Entity;

use Doctrine\ORM\EntityRepository;

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
     * @param string $date
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery($date = null)
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

        return $q->getQuery();
    }

    /**
     * @param string $search
     * @param bool $useDescription
     * @param array|null $mimeType
     * @param \DateTime|null $createdAfter
     * @param \DateTime|null $createdBefore
     * @return \Doctrine\ORM\Query
     */
    public function getSearchQuery($search, $useDescription = true, array $mimeType = null, \DateTime $createdAfter = null, \DateTime $createdBefore = null)
    {
        $q = $this->createQueryBuilder('f');

        $search = '%' . addcslashes($search, '%_') . '%';
        // 15 символов - это результат uniqid + _
        $q->where('SUBSTRING(f.fileName, 15) LIKE :search');
        if (true === $useDescription) {
            $q->orWhere('f.description LIKE :search');
        }
        $q->setParameter('search', $search);

        foreach ($mimeType as $mimeTypeValue) {
            switch ($mimeTypeValue) {
                case 'video':
                case 'audio':
                case 'image':
                case 'text':
                    $mimeTypeValue = addcslashes($mimeTypeValue, '%_') . '/%';
                    $q->andWhere('f.mimeType LIKE :mime_type');
                    $q->setParameter('mime_type', $mimeTypeValue);
                    break;
                case 'archive':
                    $q->andWhere('f.mimeType IN(:zip, :rar, :bz, :bz2, :7z, :tar, :cab, :iso, :gz, :ace, :lzh)');
                    $q->setParameter('zip', 'application/zip');
                    $q->setParameter('rar', 'application/x-rar-compressed');
                    $q->setParameter('bz', 'application/x-bzip');
                    $q->setParameter('bz2', 'application/x-bzip2');
                    $q->setParameter('7z', 'application/x-7z-compressed');
                    $q->setParameter('tar', 'application/x-tar');
                    $q->setParameter('cab', 'application/vnd.ms-cab-compressed');
                    $q->setParameter('iso', 'application/x-iso9660-image');
                    $q->setParameter('gz', 'application/x-gzip');
                    $q->setParameter('ace', 'application/x-ace-compressed');
                    $q->setParameter('lzh', 'application/x-lzh-compressed');
                    break;
                case 'android_app':
                    $q->andWhere('f.mimeType = :mime_type');
                    $q->setParameter('mime_type', 'application/vnd.android.package-archive');
                    break;
                case 'java_app':
                    $q->andWhere('f.mimeType = :mime_type');
                    $q->setParameter('mime_type', 'application/java-archive');
                    break;
            }
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
