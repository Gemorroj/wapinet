<?php

namespace Wapinet\Bundle\Twig\Extension;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Wapinet\Bundle\Entity\FileRepository;
use Wapinet\UserBundle\Entity\User;

class File extends \Twig_Extension
{
    /**
     * @var FileRepository
     */
    protected $fileRepository;
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     * @param EntityManager $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->container = $container;
        $this->fileRepository = $em->getRepository('Wapinet\Bundle\Entity\File');
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('basename', 'basename'),
        );
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('file_count_all', array($this, 'getCountAll')),
            new \Twig_SimpleFunction('file_count_today', array($this, 'getCountToday')),
            new \Twig_SimpleFunction('file_count_yesterday', array($this, 'getCountYesterday')),
            new \Twig_SimpleFunction('file_count_category', array($this, 'getCountCategory')),
            new \Twig_SimpleFunction('file_count_user', array($this, 'getCountUser')),
            new \Twig_SimpleFunction('file_get_media_info', array($this, 'getMediaInfo')),
        );
    }

    /**
     * @return int
     */
    public function getCountAll()
    {
        return $this->fileRepository->countAll();
    }

    /**
     * @return int
     */
    public function getCountToday()
    {
        return $this->fileRepository->countDate(
            new \DateTime('today', $this->container->get('timezone')->getTimezone())
        );
    }

    /**
     * @return int
     */
    public function getCountYesterday()
    {
        return $this->fileRepository->countDate(
            new \DateTime('yesterday', $this->container->get('timezone')->getTimezone()),
            new \DateTime('today', $this->container->get('timezone')->getTimezone())
        );
    }

    /**
     * @param string $category
     * @return int
     */
    public function getCountCategory($category)
    {
        return $this->fileRepository->countCategory($category);
    }

    /**
     * @param User $user
     * @return int
     */
    public function getCountUser(User $user)
    {
        return $this->fileRepository->countUser($user);
    }


    /**
     * @param \Symfony\Component\HttpFoundation\File\File $file
     *
     * @return null|\FFMpeg\FFProbe\DataMapping\StreamCollection
     */
    public function getMediaInfo(\Symfony\Component\HttpFoundation\File\File $file)
    {
        $ffprobe = $this->container->get('dubture_ffmpeg.ffprobe');

        try {
            $info = $ffprobe->streams($file->getPathname());
        } catch (\Exception $e) {
            $info = null;
        }

        return $info;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'file';
    }
}
