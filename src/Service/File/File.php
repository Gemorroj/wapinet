<?php

namespace App\Service\File;

use App\Entity\File as DataFile;
use Doctrine\Common\Collections\ArrayCollection;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

final readonly class File
{
    public function __construct(
        private UploaderHelper $uploaderHelper,
        private CacheManager $cacheManager,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
        private Filesystem $filesystem
    ) {
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
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher($file);
        $hashedPassword = $passwordHasher->hash($password);
        $file->setPassword($hashedPassword);
    }

    public function removePassword(DataFile $file): void
    {
        $file->setPassword(null);
        $file->setPlainPassword(null);
    }

    /**
     * @throws AccessDeniedException|NotFoundHttpException
     */
    public function checkFile(string $directory, string $path, bool $allowDirectory = false): string
    {
        $path = \str_replace('\\', '/', $path);

        if (\str_contains($path, '../')) {
            throw new AccessDeniedException('Запрещен доступ: "'.$path.'"".');
        }

        $file = \realpath($directory.\DIRECTORY_SEPARATOR.$path);

        if (false === $file) {
            throw new NotFoundHttpException('Файл не найден: "'.$path.'"".');
        }

        if (true !== $allowDirectory && true === \is_dir($path)) {
            throw new AccessDeniedException('Запрещен доступ: "'.$path.'"".');
        }

        return $file;
    }
}
