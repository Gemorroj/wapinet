<?php

namespace App\Service\File;

use App\Entity\File as DataFile;
use const DIRECTORY_SEPARATOR;
use Doctrine\Common\Collections\ArrayCollection;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use function realpath;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * File хэлпер
 */
class File
{
    /**
     * @var UploaderHelper
     */
    private $uploaderHelper;
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var CacheManager
     */
    private $cacheManager;

    public function __construct(UploaderHelper $uploaderHelper, CacheManager $cacheManager, EncoderFactoryInterface $encoderFactory, Filesystem $filesystem)
    {
        $this->uploaderHelper = $uploaderHelper;
        $this->encoderFactory = $encoderFactory;
        $this->filesystem = $filesystem;
        $this->cacheManager = $cacheManager;
    }

    public function cleanupFile(DataFile $file): void
    {
        // скриншоты и сконвертированные видео
        $realPath = $file->getFile()->getRealPath();
        $this->filesystem->remove([
            // $realPath, // сам файл удаляется entity менеджером
            $realPath.'.png',
            $realPath.'.jpg',
            $realPath.'.mp4',
            $realPath.'.mp4.jpg',
            $realPath.'.mp4.png',
            $realPath.'.mp3',
        ]);

        // кэш картинок
        $path = $this->uploaderHelper->asset($file, 'file');
        $this->cacheManager->remove([
            $path,
            $path.'.png',
            $path.'.jpg',
            $path.'.mp4',
            $path.'.mp4.jpg',
            $path.'.mp4.png',
            $path.'.mp3',
        ], 'thumbnail');
    }

    public function copyFileTagsToTags(DataFile $file): void
    {
        $tagsCollection = new ArrayCollection();

        foreach ($file->getFileTags() as $fileTags) {
            $tagsCollection->add($fileTags->getTag());
        }

        $file->setTags($tagsCollection);
    }

    public function setPassword(DataFile $file, string $password): void
    {
        $file->setSaltValue();

        $encoder = $this->encoderFactory->getEncoder($file);
        $encodedPassword = $encoder->encodePassword($password, $file->getSalt());
        $file->setPassword($encodedPassword);
    }

    public function removePassword(DataFile $file): void
    {
        $file->removeSalt();
        $file->setPassword(null);
        $file->setPlainPassword(null);
    }

    /**
     * @throws AccessDeniedException|NotFoundHttpException
     */
    public function checkFile(string $directory, string $path, bool $allowDirectory = false): string
    {
        $path = \str_replace('\\', '/', $path);

        if (false !== \mb_strpos($path, '../')) {
            throw new AccessDeniedException('Запрещен доступ: "'.$path.'"".');
        }

        $file = \realpath($directory.DIRECTORY_SEPARATOR.$path);

        if (false === $file) {
            throw new NotFoundHttpException('Файл не найден: "'.$path.'"".');
        }

        if (true !== $allowDirectory && true === \is_dir($allowDirectory)) {
            throw new AccessDeniedException('Запрещен доступ: "'.$path.'"".');
        }

        return $file;
    }
}
