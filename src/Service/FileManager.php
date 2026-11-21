<?php

namespace App\Service;

use App\Entity\File as DataFile;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

final readonly class FileManager
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
        // превью, обложки и сконвертированные файлы
        $realPath = $file->getFile()->getRealPath();
        $this->filesystem->remove([
            // $realPath, // сам файл удаляется entity менеджером
            $realPath.'.png',
            $realPath.'.jpg',
            $realPath.'.webp',
            $realPath.'.gif',
            $realPath.'.mp4',
            $realPath.'.mp4.jpg',
            $realPath.'.mp4.png',
            $realPath.'.mp3',
            $realPath.'.no-preview',
        ]);

        // кэш картинок
        $path = $this->uploaderHelper->asset($file, 'file');
        $this->cacheManager->remove([
            $path,
            $path.'.png',
            $path.'.jpg',
            $path.'.webp',
            $path.'.gif',
            $path.'.mp4',
            $path.'.mp4.jpg',
            $path.'.mp4.png',
            $path.'.mp3',
        ], 'thumbnail');
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
}
