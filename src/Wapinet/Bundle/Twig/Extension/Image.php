<?php

namespace Wapinet\Bundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Imagine\Gd\Imagine;
use Imagine\Gd\Image as GdImage;

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
        );
    }


    /**
     * @param File $file
     *
     * @return GdImage|null
     */
    public function getInfo (File $file)
    {
        $imagine = new Imagine();
        try {
            $info = $imagine->open($file->getPathname());
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
        return 'wapinet_image';
    }
}
