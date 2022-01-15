<?php

namespace App\Repository;

use App\Entity\File;
use App\Entity\Tag;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
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
        return $this->count([
            'password' => null,
            'hidden' => true,
        ]);
    }

    public function countAll(): int
    {
        return $this->count([
            'password' => null,
            'hidden' => false,
        ]);
    }

    public function countDate(\DateTime $datetimeStart, ?\DateTime $datetimeEnd = null): int
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->andWhere('f.createdAt > :date_start');

        $queryBuilder->setParameter('date_start', $datetimeStart);

        if ($datetimeEnd) {
            $queryBuilder->andWhere('f.createdAt < :date_end');
            $queryBuilder->setParameter('date_end', $datetimeEnd);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function countCategory(string $category): int
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0');

        $this->addCategoryMime($queryBuilder, $category);

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function countUser(User $user): int
    {
        return $this->count([
            'password' => null,
            'hidden' => false,
            'user' => $user,
        ]);
    }

    public function getListQuery(?\DateTime $datetimeStart = null, ?\DateTime $datetimeEnd = null, ?string $category = null): Query
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->where('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->orderBy('f.id', 'DESC');

        if ($datetimeStart) {
            $queryBuilder->andWhere('f.createdAt > :date_start');
            $queryBuilder->setParameter('date_start', $datetimeStart);
        }
        if ($datetimeEnd) {
            $queryBuilder->andWhere('f.createdAt < :date_end');
            $queryBuilder->setParameter('date_end', $datetimeEnd);
        }

        $this->addCategoryMime($queryBuilder, $category);

        return $queryBuilder->getQuery();
    }

    public function getHiddenQuery(): Query
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->andWhere('f.hidden = 1')
            ->orderBy('f.id', 'DESC');

        return $queryBuilder->getQuery();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPrevFile(int $id, ?string $category = null): ?File
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->where('f.id > :id')
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->setParameter('id', $id)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(1);

        $this->addCategoryMime($queryBuilder, $category);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNextFile(int $id, ?string $category = null): ?File
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->where('f.id < :id')
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')
            ->setParameter('id', $id)
            ->orderBy('f.id', 'DESC')
            ->setMaxResults(1);

        $this->addCategoryMime($queryBuilder, $category);

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function addCategoryMime(QueryBuilder $qb, ?string $mimeType): void
    {
        switch ($mimeType) {
            case 'video':
                $qb->andWhere('f.mimeType LIKE :video OR f.mimeType IN(:flash, :flv, :a3gpp, :a3gppencrypted, :axrn3gppamr, :axrn3gppamrencrypted, :axrn3gppamrwb, :axrn3gppamrwbencrypted, :a3gpp2, :awmv)');
                $qb->setParameter('video', 'video/%');
                $qb->setParameter('flash', 'application/x-flash-video');
                $qb->setParameter('flv', 'flv-application/octet-stream');
                $qb->setParameter('a3gpp', 'audio/3gpp');
                $qb->setParameter('a3gppencrypted', 'audio/3gpp-encrypted');
                $qb->setParameter('axrn3gppamr', 'audio/x-rn-3gpp-amr');
                $qb->setParameter('axrn3gppamrencrypted', 'audio/x-rn-3gpp-amr-encrypted');
                $qb->setParameter('axrn3gppamrwb', 'audio/x-rn-3gpp-amr-wb');
                $qb->setParameter('axrn3gppamrwbencrypted', 'audio/x-rn-3gpp-amr-wb-encrypted');
                $qb->setParameter('a3gpp2', 'audio/3gpp2');
                $qb->setParameter('awmv', 'audio/x-ms-wmv');
                break;
            case 'audio':
                $qb->andWhere('f.mimeType LIKE :audio OR f.mimeType IN(:flash)');
                $qb->setParameter('audio', 'audio/%');
                $qb->setParameter('flash', 'application/x-flash-audio');
                break;
            case 'image':
                $qb->andWhere('f.mimeType LIKE :image OR f.mimeType IN(:postscript, :illustrator, :adobeillustrator)');
                $qb->setParameter('image', 'image/%');
                $qb->setParameter('postscript', 'application/postscript');
                $qb->setParameter('illustrator', 'application/illustrator');
                $qb->setParameter('adobeillustrator', 'application/vnd.adobe.illustrator');
                break;
            case 'text':
                $qb->andWhere('f.mimeType LIKE :text OR f.mimeType IN(:axml, :txml, :json, :xphp, :xhttpdphp, :xpython, :xpython3, :csv, :perl, :sql, :xsql, :yaml, :xsh, :xshellscript)');
                $qb->setParameter('text', 'text/%');
                $qb->setParameter('axml', 'application/xml');
                $qb->setParameter('txml', 'text/xml');
                $qb->setParameter('json', 'application/json');
                $qb->setParameter('xphp', 'application/x-php');
                $qb->setParameter('xhttpdphp', 'application/x-httpd-php');
                $qb->setParameter('xpython', 'text/x-python');
                $qb->setParameter('xpython3', 'text/x-python3');
                $qb->setParameter('csv', 'application/csv');
                $qb->setParameter('perl', 'application/x-perl');
                $qb->setParameter('sql', 'application/sql');
                $qb->setParameter('xsql', 'application/x-sql');
                $qb->setParameter('yaml', 'application/x-yaml');
                $qb->setParameter('xsh', 'application/x-sh');
                $qb->setParameter('xshellscript', 'application/x-shellscript');
                break;
            case 'office':
                $qb->andWhere('f.mimeType IN(:pdf, :acrobat, :nappdf, :xpdf, :ipdf, :msword, :vndmsword, :xmsword, :zzwinassocdoc, :docx, :vndmsexcel, :msexcel, :xmsexcel, :zzwinassocxls, :xlsx, :artf, :trtf, :vndmspowerpoint, :mspowerpoint, :powerpoint, :xmspowerpoint, :pptx, :xmsaccess, :mdb, :msaccess, :vndmsaccess1, :vndmsaccess2, :xmdb, :zzwinassocmdb)');
                $qb->setParameter('pdf', 'application/pdf');
                $qb->setParameter('acrobat', 'application/acrobat');
                $qb->setParameter('nappdf', 'application/nappdf');
                $qb->setParameter('xpdf', 'application/x-pdf');
                $qb->setParameter('ipdf', 'image/pdf');
                $qb->setParameter('msword', 'application/msword');
                $qb->setParameter('vndmsword', 'application/vnd.ms-word');
                $qb->setParameter('xmsword', 'application/x-msword');
                $qb->setParameter('zzwinassocdoc', 'zz-application/zz-winassoc-doc');
                $qb->setParameter('docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                $qb->setParameter('vndmsexcel', 'application/vnd.ms-excel');
                $qb->setParameter('msexcel', 'application/msexcel');
                $qb->setParameter('xmsexcel', 'application/x-msexcel');
                $qb->setParameter('zzwinassocxls', 'zz-application/zz-winassoc-xls');
                $qb->setParameter('xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $qb->setParameter('artf', 'application/rtf');
                $qb->setParameter('trtf', 'text/rtf');
                $qb->setParameter('vndmspowerpoint', 'application/vnd.ms-powerpoint');
                $qb->setParameter('mspowerpoint', 'application/mspowerpoint');
                $qb->setParameter('powerpoint', 'application/powerpoint');
                $qb->setParameter('xmspowerpoint', 'application/x-mspowerpoint');
                $qb->setParameter('pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
                $qb->setParameter('xmsaccess', 'application/x-msaccess');
                $qb->setParameter('mdb', 'application/mdb');
                $qb->setParameter('msaccess', 'application/msaccess');
                $qb->setParameter('vndmsaccess1', 'application/vnd.ms-access');
                $qb->setParameter('vndmsaccess2', 'application/vnd.msaccess');
                $qb->setParameter('xmdb', 'application/x-mdb');
                $qb->setParameter('zzwinassocmdb', 'zz-application/zz-winassoc-mdb');
                break;
            case 'archive':
                $qb->andWhere('f.fileName NOT LIKE \'%.apk\'');
                $qb->andWhere('f.mimeType IN(:zip, :xzip, :xzipcompressed, :xrarcompressed, :xrar, :vndrar, :xbzip, :xbzip2, :xbz2, :p7z, :mscab, :zzcab, :tar, :xgzip, :gzip, :xace, :xacecompressed, :xlha, :xlzhcompressed, :iso1, :iso2, :iso3, :iso4, :iso5, :iso6, :iso7, :iso8, :iso9, :iso10)');
                $qb->setParameter('zip', 'application/zip'); // zip
                $qb->setParameter('xzip', 'application/x-zip'); // zip
                $qb->setParameter('xzipcompressed', 'application/x-zip-compressed'); // zip
                $qb->setParameter('xrarcompressed', 'application/x-rar-compressed'); // rar
                $qb->setParameter('xrar', 'application/x-rar'); // rar
                $qb->setParameter('vndrar', 'application/vnd.rar'); // rar
                $qb->setParameter('xbzip', 'application/x-bzip'); // bz
                $qb->setParameter('xbzip2', 'application/x-bzip2'); // bz2
                $qb->setParameter('xbz2', 'application/x-bz2'); // bz2
                $qb->setParameter('p7z', 'application/x-7z-compressed'); // 7z
                $qb->setParameter('mscab', 'application/vnd.ms-cab-compressed'); // cab
                $qb->setParameter('zzcab', 'zz-application/zz-winassoc-cab'); // cab
                $qb->setParameter('tar', 'application/x-tar');
                $qb->setParameter('xgzip', 'application/x-gzip'); // gz
                $qb->setParameter('gzip', 'application/gzip'); // gz
                $qb->setParameter('xace', 'application/x-ace'); // ace
                $qb->setParameter('xacecompressed', 'application/x-ace-compressed'); //ace
                $qb->setParameter('xlha', 'application/x-lha'); // lzh
                $qb->setParameter('xlzhcompressed', 'application/x-lzh-compressed'); // lzh
                $qb->setParameter('iso1', 'application/x-cd-image');
                $qb->setParameter('iso2', 'application/x-gamecube-iso-image');
                $qb->setParameter('iso3', 'application/x-gamecube-rom');
                $qb->setParameter('iso4', 'application/x-iso9660-image');
                $qb->setParameter('iso5', 'application/x-saturn-rom');
                $qb->setParameter('iso6', 'application/x-sega-cd-rom');
                $qb->setParameter('iso7', 'application/x-wbfs');
                $qb->setParameter('iso8', 'application/x-wia');
                $qb->setParameter('iso9', 'application/x-wii-iso-image');
                $qb->setParameter('iso10', 'application/x-wii-rom');
                break;
            case 'android':
                $qb->andWhere('f.mimeType = :android OR (f.mimeType IN(:java, :zip, :xzip, :xzipcompressed) AND f.fileName LIKE \'%.apk\')');
                $qb->setParameter('android', 'application/vnd.android.package-archive');
                $qb->setParameter('java', 'application/java-archive'); // jar
                $qb->setParameter('zip', 'application/zip'); // zip
                $qb->setParameter('xzip', 'application/x-zip'); // zip
                $qb->setParameter('xzipcompressed', 'application/x-zip-compressed'); // zip
                break;
            case 'java':
                $qb->andWhere('f.mimeType IN(:java, :xjava, :jar) AND f.filename NOT LIKE \'%.apk\'');
                $qb->setParameter('java', 'application/java-archive');
                $qb->setParameter('xjava', 'application/x-java-archive');
                $qb->setParameter('jar', 'application/x-jar');
                break;
        }
    }

    public function getUserFilesQuery(User $user): Query
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->innerJoin('f.user', 'u')

            ->where('u = :user')
            ->setParameter('user', $user)
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')

            ->orderBy('f.id', 'DESC')
        ;

        return $queryBuilder->getQuery();
    }

    public function getTagFilesQuery(Tag $tag): Query
    {
        $queryBuilder = $this->createQueryBuilder('f')
            ->innerJoin('f.fileTags', 'ft')

            ->where('ft.tag = :tag')
            ->setParameter('tag', $tag)
            ->andWhere('f.password IS NULL')
            ->andWhere('f.hidden = 0')

            ->orderBy('f.id', 'DESC')
            ;

        return $queryBuilder->getQuery();
    }
}
