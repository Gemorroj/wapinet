<?php
namespace Wapinet\Bundle\Helper\File;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Wapinet\Bundle\Entity\File as DataFile;
use Wapinet\Bundle\Entity\FileTags;
use Wapinet\Bundle\Entity\Tag;

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
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    /**
     * @param DataFile $file
     */
    public function cleanupFile(DataFile $file)
    {
        // скриншоты и сконвертированные видео
        $realPath = $file->getFile()->getRealPath();
        $this->container->get('filesystem')->remove(
            array(
                // $realPath, // сам файл удаляется entity менеджером
                $realPath . '.png',
                $realPath . '.jpg',
                $realPath . '.mp4',
                $realPath . '.mp4.jpg',
                $realPath . '.mp4.png',
                $realPath . '.mp3',
            )
        );

        // кэш картинок
        $path = $this->container->get('vich_uploader.templating.helper.uploader_helper')->asset($file, 'file');
        $this->container->get('liip_imagine.cache.manager')->remove(
            array(
                $path,
                $path . '.png',
                $path . '.jpg',
                $path . '.mp4',
                $path . '.mp4.jpg',
                $path . '.mp4.png',
                $path . '.mp3',
            ),
            'thumbnail'
        );

        // комментарии
        $this->container->get('wapinet_comment.helper')->removeThread('file-' . $file->getId());
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
     * @param string $password
     */
    public function setPassword(DataFile $file, $password)
    {
        $file->setSaltValue();

        $encoder = $this->container->get('security.encoder_factory')->getEncoder($file);
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
     * @param bool $allowDirectory
     * @throws AccessDeniedException|NotFoundHttpException
     * @return string
     */
    public function checkFile($directory, $path, $allowDirectory = false)
    {
        $path = \str_replace('\\', '/', $path);

        if (false !== \strpos($path, '../')) {
            throw new AccessDeniedException('Запрещен доступ: "' . $path . '"".');
        }

        $file = \realpath($directory . \DIRECTORY_SEPARATOR . $path);

        if (false === $file) {
            throw new NotFoundHttpException('Файл не найден: "' . $path . '"".');
        }

        if (true !== $allowDirectory && true === \is_dir($allowDirectory)) {
            throw new AccessDeniedException('Запрещен доступ: "' . $path . '"".');
        }

        return $file;
    }
}
