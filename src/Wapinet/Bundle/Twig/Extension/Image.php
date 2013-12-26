<?php

namespace Wapinet\Bundle\Twig\Extension;

use Imagine\Image\ImageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class Image extends \Twig_Extension
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
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_image_info', array($this, 'getInfo')),
            new \Twig_SimpleFunction('wapinet_image_exif', array($this, 'getExif')),
        );
    }


    /**
     * @param File $file
     *
     * @return ImageInterface|null
     */
    public function getInfo (File $file)
    {
        $imagine = $this->container->get('liip_imagine');
        try {
            $info = $imagine->open($file->getPathname());
        } catch (\Exception $e) {
            $info = null;
        }

        return $info;
    }

    /**
     * @param File $file
     *
     * @return array|null
     */
    public function getExif (File $file)
    {
        if (true === function_exists('exif_read_data')) {
            $exif = @exif_read_data($file->getPathname());
            if (false !== $exif) {
                return $exif;
            }
        }

        return null;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_image';
    }
}
