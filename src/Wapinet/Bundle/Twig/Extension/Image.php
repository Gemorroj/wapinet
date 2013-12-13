<?php

namespace Wapinet\Bundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;

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
            new \Twig_SimpleFunction('wapinet_image_size', array($this, 'getSize')),
        );
    }


    /**
     * @param File $file
     *
     * @return Box
     */
    public function getSize (File $file)
    {
        $imagine = new Imagine();
        $image = $imagine->open($file->getPathname());

        return $image->getSize();
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
