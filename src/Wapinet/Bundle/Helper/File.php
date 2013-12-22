<?php
namespace Wapinet\Bundle\Helper;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wapinet\Bundle\Entity\File as DataFile;

/**
 * File хэлпер
 */
class File
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
        $tags = explode($separator, $names);
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags, function ($value) {
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
        return implode($separator, $tags->toArray());
    }

    /**
     * @param DataFile $file
     */
    public function cleanupFile(DataFile $file)
    {
        // кэш картинок
        $cache = $this->container->get('liip_imagine.cache.manager');
        $helper = $this->container->get('vich_uploader.templating.helper.uploader_helper');
        $path = $helper->asset($file, 'file');
        $cache->remove($path, 'thumbnail');

        // сам файл и сконвертированные видео
        $filesystem = $this->container->get('filesystem');
        $path = $file->getFile()->getPathname();
        $filesystem->remove(array($path, $path . '.jpg', $path . '.mp4', $path . '.webm'));
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
