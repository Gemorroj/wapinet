<?php

namespace App\Helper\File;

use App\Entity\File as DataFile;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * File хэлпер
 */
class File
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * File constructor.
     *
     * @param ContainerInterface      $container
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function __construct(ContainerInterface $container, EncoderFactoryInterface $encoderFactory)
    {
        $this->container = $container;
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param DataFile $file
     */
    public function cleanupFile(DataFile $file)
    {
        // скриншоты и сконвертированные видео
        $realPath = $file->getFile()->getRealPath();
        $this->container->get('filesystem')->remove([
            // $realPath, // сам файл удаляется entity менеджером
            $realPath.'.png',
            $realPath.'.jpg',
            $realPath.'.mp4',
            $realPath.'.mp4.jpg',
            $realPath.'.mp4.png',
            $realPath.'.mp3',
        ]);

        // кэш картинок
        $path = $this->container->get('vich_uploader.templating.helper.uploader_helper')->asset($file, 'file');
        $this->container->get('liip_imagine.cache.manager')->remove([
            $path,
            $path.'.png',
            $path.'.jpg',
            $path.'.mp4',
            $path.'.mp4.jpg',
            $path.'.mp4.png',
            $path.'.mp3',
        ], 'thumbnail');
    }

    /**
     * @param DataFile $file
     */
    public function copyFileTagsToTags(DataFile $file)
    {
        $tagsCollection = new ArrayCollection();

        foreach ($file->getFileTags() as $fileTags) {
            $tagsCollection->add($fileTags->getTag());
        }

        $file->setTags($tagsCollection);
    }

    /**
     * @param DataFile $file
     * @param string   $password
     */
    public function setPassword(DataFile $file, string $password)
    {
        $file->setSaltValue();

        $encoder = $this->encoderFactory->getEncoder($file);
        $encodedPassword = $encoder->encodePassword($password, $file->getSalt());
        $file->setPassword($encodedPassword);
    }

    /**
     * @param DataFile $file
     */
    public function removePassword(DataFile $file)
    {
        $file->removeSalt();
        $file->setPassword(null);
        $file->setPlainPassword(null);
    }

    /**
     * @param string $directory
     * @param string $path
     * @param bool   $allowDirectory
     *
     * @throws AccessDeniedException|NotFoundHttpException
     *
     * @return string
     */
    public function checkFile($directory, $path, $allowDirectory = false)
    {
        $path = \str_replace('\\', '/', $path);

        if (false !== \strpos($path, '../')) {
            throw new AccessDeniedException('Запрещен доступ: "'.$path.'"".');
        }

        $file = \realpath($directory.\DIRECTORY_SEPARATOR.$path);

        if (false === $file) {
            throw new NotFoundHttpException('Файл не найден: "'.$path.'"".');
        }

        if (true !== $allowDirectory && true === \is_dir($allowDirectory)) {
            throw new AccessDeniedException('Запрещен доступ: "'.$path.'"".');
        }

        return $file;
    }
}
