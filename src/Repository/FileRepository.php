<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class FileRepository extends EntityRepository
{
    /**
     * @param int $maxUsers
     *
     * @return array
     */
    public function getStatistic(int $maxUsers = 10): array
    {
        $em = $this->getEntityManager();
        $countFiles = $em->createQuery('SELECT COUNT(f.id) FROM App\Entity\File f');
        $sizeFiles = $em->createQuery('SELECT SUM(f.fileSize) FROM App\Entity\File f');
        $countViews = $em->createQuery('SELECT SUM(f.countViews) FROM App\Entity\File f');

        $users = $em->createQuery('
            SELECT u.username, COUNT(f.id) AS uploads
            FROM App\Entity\File f
            INNER JOIN App\Entity\User u WITH f.user = u
            WHERE u.enabled = 1
            GROUP BY u.id
            ORDER BY uploads DESC
        ');
        $users->setMaxResults($maxUsers);

        return [
            'count_files' => $countFiles->getSingleScalarResult(),
            'size_files' => $sizeFiles->getSingleScalarResult(),
            'count_views' => $countViews->getSingleScalarResult(),
            'users' => $users->getResult(),
        ];
    }

    /**
     * @return int
     */
    public function countHidden(): int
    {
        return $this->getEntityManager()->createQuery(
            'SELECT COUNT(f.id) FROM App\Entity\File f WHERE f.password IS NULL AND f.hidden = 1'
        )->getSingleScalarResult();
    }

    /**
     * @return int
     */
    public function countAll(): int
    {
        return $this->getEntityManager()->createQuery(
            'SELECT COUNT(f.id) FROM App\Entity\File f WHERE f.password IS NULL AND f.hidden = 0'
        )->getSingleScalarResult();
    }

    /**
     * @param \DateTime      $datetimeStart
     * @param \DateTime|null $datetimeEnd
     *
     * @return int
     */
    public function countDate(\DateTime $datetimeStart, ?\DateTime $datetimeEnd = null): int
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->andWhere('f.createdAt > :date_start');

        $queryBuilder->setParameter('date_start', $datetimeStart);

        if (null !== $datetimeEnd) {
            $queryBuilder->andWhere('f.createdAt < :date_end');
            $queryBuilder->setParameter('date_end', $datetimeEnd);
        }

        $q = $queryBuilder->getQuery();

        return $q->getSingleScalarResult();
    }

    /**
     * @param string $category
     *
     * @return int
     */
    public function countCategory(string $category): int
    {
        $q = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0');

        $this->addCategoryMime($q, $category);
        $q = $q->getQuery();

        return $q->getSingleScalarResult();
    }

    /**
     * @param User $user
     *
     * @return int
     */
    public function countUser(User $user): int
    {
        $q = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->andWhere('f.user = :user')
            ->setParameter('user', $user)
            ->getQuery();

        return $q->getSingleScalarResult();
    }

    /**
     * @param \DateTime|null $datetimeStart
     * @param \DateTime|null $datetimeEnd
     * @param string|null    $category
     *
     * @return \Doctrine\ORM\Query
     */
    public function getListQuery(?\DateTime $datetimeStart = null, ?\DateTime $datetimeEnd = null, ?string $category = null): \Doctrine\ORM\Query
    {
        $q = $this->createQueryBuilder('f')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->orderBy('f.id', 'DESC');

        if (null !== $datetimeStart) {
            $q->andWhere('f.createdAt > :date_start');
            $q->setParameter('date_start', $datetimeStart);
        }
        if (null !== $datetimeEnd) {
            $q->andWhere('f.createdAt < :date_end');
            $q->setParameter('date_end', $datetimeEnd);
        }

        $this->addCategoryMime($q, $category);

        return $q->getQuery();
    }

    /**
     * @return \Doctrine\ORM\Query
     */
    public function getHiddenQuery(): \Doctrine\ORM\Query
    {
        $q = $this->createQueryBuilder('f')
            ->andWhere('f.hidden = 1')
            ->orderBy('f.id', 'DESC');

        return $q->getQuery();
    }

    /**
     * @param int         $id
     * @param string|null $category
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return File|null
     */
    public function getPrevFile(int $id, ?string $category = null): ?File
    {
        $q = $this->createQueryBuilder('f')
            ->where('f.id > :id')
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->setParameter('id', $id)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(1);

        $this->addCategoryMime($q, $category);

        return $q->getQuery()->getOneOrNullResult();
    }

    /**
     * @param int         $id
     * @param string|null $category
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return File|null
     */
    public function getNextFile(int $id, ?string $category = null): ?File
    {
        $q = $this->createQueryBuilder('f')
            ->where('f.id < :id')
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->setParameter('id', $id)
            ->orderBy('f.id', 'DESC')
            ->setMaxResults(1);

        $this->addCategoryMime($q, $category);

        return $q->getQuery()->getOneOrNullResult();
    }

    /**
     * @param QueryBuilder $q
     * @param null|string  $mimeType
     */
    private function addCategoryMime(QueryBuilder $q, ?string $mimeType): void
    {
        switch ($mimeType) {
            case 'video':
            case 'audio':
            case 'image':
            case 'text':
                $mimeType = \addcslashes($mimeType, '%_').'/%';
                $q->andWhere('f.mimeType LIKE :mime_type');
                $q->setParameter('mime_type', $mimeType);
                break;
            case 'office':
                $q->andWhere('f.mimeType IN(:pdf, :doc, :docx, :xls, :xlsx, :rtf)');
                $q->setParameter('pdf', 'application/pdf');
                $q->setParameter('doc', 'application/msword');
                $q->setParameter('docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                $q->setParameter('xls', 'application/vnd.ms-excel');
                $q->setParameter('xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $q->setParameter('rtf', 'application/rtf');
                break;
            case 'archive':
                $q->andWhere('f.mimeType IN(:zip, :rar, :bz, :bz2, :p7z, :tar, :cab, :iso, :gz_old, :gz_new, :ace, :lzh)');
                $q->setParameter('zip', 'application/zip');
                $q->setParameter('rar', 'application/x-rar-compressed');
                $q->setParameter('bz', 'application/x-bzip');
                $q->setParameter('bz2', 'application/x-bzip2');
                $q->setParameter('p7z', 'application/x-7z-compressed'); //7z
                $q->setParameter('tar', 'application/x-tar');
                $q->setParameter('cab', 'application/vnd.ms-cab-compressed');
                $q->setParameter('iso', 'application/x-iso9660-image');
                $q->setParameter('gz_old', 'application/x-gzip');
                $q->setParameter('gz_new', 'application/gzip');
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
     * @param User $user
     *
     * @return \Doctrine\ORM\Query
     */
    public function getUserFilesQuery(User $user): \Doctrine\ORM\Query
    {
        $q = $this->createQueryBuilder('f')
            ->innerJoin('f.user', 'u')

            ->where('u = :user')
            ->setParameter('user', $user)
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')

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
    public function getTagFilesQuery(Tag $tag): \Doctrine\ORM\Query
    {
        $q = $this->createQueryBuilder('f')
            ->innerJoin('f.fileTags', 'ft')

            ->where('ft.tag = :tag')
            ->setParameter('tag', $tag)
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')

            ->orderBy('f.id', 'DESC')

            ->getQuery()
            ;

        return $q;
    }
}