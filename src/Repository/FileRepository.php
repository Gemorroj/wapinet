<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

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

    public function countHidden(): int
    {
        return $this->getEntityManager()->createQuery(
            'SELECT COUNT(f.id) FROM App\Entity\File f WHERE f.password IS NULL AND f.hidden = 1'
        )->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return $this->getEntityManager()->createQuery(
            'SELECT COUNT(f.id) FROM App\Entity\File f WHERE f.password IS NULL AND f.hidden = 0'
        )->getSingleScalarResult();
    }

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

    public function getHiddenQuery(): \Doctrine\ORM\Query
    {
        $q = $this->createQueryBuilder('f')
            ->andWhere('f.hidden = 1')
            ->orderBy('f.id', 'DESC');

        return $q->getQuery();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     * @throws \Doctrine\ORM\NonUniqueResultException
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

    private function addCategoryMime(QueryBuilder $q, ?string $mimeType): void
    {
        switch ($mimeType) {
            case 'video':
                $q->andWhere('f.mimeType LIKE :video OR f.mimeType IN(:flash, :flv, :a3gpp, :a3gppencrypted, :axrn3gppamr, :axrn3gppamrencrypted, :axrn3gppamrwb, :axrn3gppamrwbencrypted, :a3gpp2, :awmv)');
                $q->setParameter('video', 'video/%');
                $q->setParameter('flash', 'application/x-flash-video');
                $q->setParameter('flv', 'flv-application/octet-stream');
                $q->setParameter('a3gpp', 'audio/3gpp');
                $q->setParameter('a3gppencrypted', 'audio/3gpp-encrypted');
                $q->setParameter('axrn3gppamr', 'audio/x-rn-3gpp-amr');
                $q->setParameter('axrn3gppamrencrypted', 'audio/x-rn-3gpp-amr-encrypted');
                $q->setParameter('axrn3gppamrwb', 'audio/x-rn-3gpp-amr-wb');
                $q->setParameter('axrn3gppamrwbencrypted', 'audio/x-rn-3gpp-amr-wb-encrypted');
                $q->setParameter('a3gpp2', 'audio/3gpp2');
                $q->setParameter('awmv', 'audio/x-ms-wmv');
                break;
            case 'audio':
                $q->andWhere('f.mimeType LIKE :audio OR f.mimeType IN(:flash)');
                $q->setParameter('audio', 'audio/%');
                $q->setParameter('flash', 'application/x-flash-audio');
                break;
            case 'image':
                $q->andWhere('f.mimeType LIKE :image OR f.mimeType IN(:postscript, :illustrator, :adobeillustrator)');
                $q->setParameter('image', 'image/%');
                $q->setParameter('postscript', 'application/postscript');
                $q->setParameter('illustrator', 'application/illustrator');
                $q->setParameter('adobeillustrator', 'application/vnd.adobe.illustrator');
                break;
            case 'text':
                $q->andWhere('f.mimeType LIKE :text OR f.mimeType IN(:axml, :txml, :json, :xphp, :xhttpdphp, :xpython, :xpython3, :csv, :perl, :sql, :xsql, :yaml, :xsh, :xshellscript)');
                $q->setParameter('text', 'text/%');
                $q->setParameter('axml', 'application/xml');
                $q->setParameter('txml', 'text/xml');
                $q->setParameter('json', 'application/json');
                $q->setParameter('xphp', 'application/x-php');
                $q->setParameter('xhttpdphp', 'application/x-httpd-php');
                $q->setParameter('xpython', 'text/x-python');
                $q->setParameter('xpython3', 'text/x-python3');
                $q->setParameter('csv', 'application/csv');
                $q->setParameter('perl', 'application/x-perl');
                $q->setParameter('sql', 'application/sql');
                $q->setParameter('xsql', 'application/x-sql');
                $q->setParameter('yaml', 'application/x-yaml');
                $q->setParameter('xsh', 'application/x-sh');
                $q->setParameter('xshellscript', 'application/x-shellscript');
                break;
            case 'office':
                $q->andWhere('f.mimeType IN(:pdf, :acrobat, :nappdf, :xpdf, :ipdf, :msword, :vndmsword, :xmsword, :zzwinassocdoc, :docx, :vndmsexcel, :msexcel, :xmsexcel, :zzwinassocxls, :xlsx, :artf, :trtf, :vndmspowerpoint, :mspowerpoint, :powerpoint, :xmspowerpoint, :pptx, :xmsaccess, :mdb, :msaccess, :vndmsaccess1, :vndmsaccess2, :xmdb, :zzwinassocmdb)');
                $q->setParameter('pdf', 'application/pdf');
                $q->setParameter('acrobat', 'application/acrobat');
                $q->setParameter('nappdf', 'application/nappdf');
                $q->setParameter('xpdf', 'application/x-pdf');
                $q->setParameter('ipdf', 'image/pdf');
                $q->setParameter('msword', 'application/msword');
                $q->setParameter('vndmsword', 'application/vnd.ms-word');
                $q->setParameter('xmsword', 'application/x-msword');
                $q->setParameter('zzwinassocdoc', 'zz-application/zz-winassoc-doc');
                $q->setParameter('docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                $q->setParameter('vndmsexcel', 'application/vnd.ms-excel');
                $q->setParameter('msexcel', 'application/msexcel');
                $q->setParameter('xmsexcel', 'application/x-msexcel');
                $q->setParameter('zzwinassocxls', 'zz-application/zz-winassoc-xls');
                $q->setParameter('xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $q->setParameter('artf', 'application/rtf');
                $q->setParameter('trtf', 'text/rtf');
                $q->setParameter('vndmspowerpoint', 'application/vnd.ms-powerpoint');
                $q->setParameter('mspowerpoint', 'application/mspowerpoint');
                $q->setParameter('powerpoint', 'application/powerpoint');
                $q->setParameter('xmspowerpoint', 'application/x-mspowerpoint');
                $q->setParameter('pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
                $q->setParameter('xmsaccess', 'application/x-msaccess');
                $q->setParameter('mdb', 'application/mdb');
                $q->setParameter('msaccess', 'application/msaccess');
                $q->setParameter('vndmsaccess1', 'application/vnd.ms-access');
                $q->setParameter('vndmsaccess2', 'application/vnd.msaccess');
                $q->setParameter('xmdb', 'application/x-mdb');
                $q->setParameter('zzwinassocmdb', 'zz-application/zz-winassoc-mdb');
                break;
            case 'archive':
                $q->andWhere('f.fileName NOT LIKE \'%.apk\'');
                $q->andWhere('f.mimeType IN(:zip, :xzip, :xzipcompressed, :xrarcompressed, :xrar, :vndrar, :xbzip, :xbzip2, :xbz2, :p7z, :mscab, :zzcab, :tar, :xgzip, :gzip, :xace, :xacecompressed, :xlha, :xlzhcompressed, :iso1, :iso2, :iso3, :iso4, :iso5, :iso6, :iso7, :iso8, :iso9, :iso10)');
                $q->setParameter('zip', 'application/zip'); // zip
                $q->setParameter('xzip', 'application/x-zip'); // zip
                $q->setParameter('xzipcompressed', 'application/x-zip-compressed'); // zip
                $q->setParameter('xrarcompressed', 'application/x-rar-compressed'); // rar
                $q->setParameter('xrar', 'application/x-rar'); // rar
                $q->setParameter('vndrar', 'application/vnd.rar'); // rar
                $q->setParameter('xbzip', 'application/x-bzip'); // bz
                $q->setParameter('xbzip2', 'application/x-bzip2'); // bz2
                $q->setParameter('xbz2', 'application/x-bz2'); // bz2
                $q->setParameter('p7z', 'application/x-7z-compressed'); // 7z
                $q->setParameter('mscab', 'application/vnd.ms-cab-compressed'); // cab
                $q->setParameter('zzcab', 'zz-application/zz-winassoc-cab'); // cab
                $q->setParameter('tar', 'application/x-tar');
                $q->setParameter('xgzip', 'application/x-gzip'); // gz
                $q->setParameter('gzip', 'application/gzip'); // gz
                $q->setParameter('xace', 'application/x-ace'); // ace
                $q->setParameter('xacecompressed', 'application/x-ace-compressed'); //ace
                $q->setParameter('xlha', 'application/x-lha'); // lzh
                $q->setParameter('xlzhcompressed', 'application/x-lzh-compressed'); // lzh
                $q->setParameter('iso1', 'application/x-cd-image');
                $q->setParameter('iso2', 'application/x-gamecube-iso-image');
                $q->setParameter('iso3', 'application/x-gamecube-rom');
                $q->setParameter('iso4', 'application/x-iso9660-image');
                $q->setParameter('iso5', 'application/x-saturn-rom');
                $q->setParameter('iso6', 'application/x-sega-cd-rom');
                $q->setParameter('iso7', 'application/x-wbfs');
                $q->setParameter('iso8', 'application/x-wia');
                $q->setParameter('iso9', 'application/x-wii-iso-image');
                $q->setParameter('iso10', 'application/x-wii-rom');
                break;
            case 'android':
                $q->andWhere('f.mimeType = :android OR (f.mimeType IN(:zip, :xzip, :xzipcompressed) AND f.fileName LIKE \'%.apk\')');
                $q->setParameter('android', 'application/vnd.android.package-archive');
                $q->setParameter('zip', 'application/zip'); // zip
                $q->setParameter('xzip', 'application/x-zip'); // zip
                $q->setParameter('xzipcompressed', 'application/x-zip-compressed'); // zip
                break;
            case 'java':
                $q->andWhere('f.mimeType IN(:java, :xjava, :jar)');
                $q->setParameter('java', 'application/java-archive');
                $q->setParameter('xjava', 'application/x-java-archive');
                $q->setParameter('jar', 'application/x-jar');
                break;
        }
    }

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
