<?php
namespace Wapinet\Bundle\Helper;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wapinet\Bundle\Entity\File as DataFile;
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
     * Splits an string into an array of valid tag names
     *
     * @param string    $names      String of tag names
     * @param string    $separator  Tag name separator
     * @return array
     */
    public function splitTagNames($names, $separator = ',')
    {
        $tags = \explode($separator, $names);
        $tags = \array_map('trim', $tags);
        $tags = \array_filter($tags, function ($value) {
            return !empty($value);
        });

        return array_values($tags);
    }

    /**
     * Splits an string into an array of valid tag names
     *
     * @param Collection    $tags      ArrayCollection of tags
     * @param string   $separator  Tag name separator
     * @return string
     */
    public function joinTagNames(Collection $tags, $separator = ', ')
    {
        return \implode($separator, $tags->toArray());
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
                $realPath . '.webm',
                $realPath . '.mp4.jpg',
                $realPath . '.webm.jpg',
                $realPath . '.mp4.png',
                $realPath . '.webm.png',
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
                $path . '.webm',
                $path . '.mp4.jpg',
                $path . '.webm.jpg',
                $path . '.mp4.png',
                $path . '.webm.png',
            ),
            'thumbnail'
        );

        // комментарии
        $this->container->get('wapinet_comment.helper')->removeThread('file-' . $file->getId());

        // тэги
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        /** @var Tag $tag */
        foreach ($file->getTags() as $tag) {
            // уменьшаем кол-во использований тэга
            if (null !== $file->getPassword()) {
                $tag->setCountPassword($tag->getCountPassword() - 1);
            } else {
                $tag->setCount($tag->getCount() - 1);
            }

            // если привязанных к тэгу файлов меньше 1, то удаляем тэг
            if (($tag->getCount() + $tag->getCountPassword()) < 1) {
                $entityManager->remove($tag);
            }
        }
        // $entityManager->flush();
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
    }
}
