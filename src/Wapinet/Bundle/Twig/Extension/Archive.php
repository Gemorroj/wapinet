<?php

namespace Wapinet\Bundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class Archive extends \Twig_Extension
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
            new \Twig_SimpleFunction('wapinet_archive_list', array($this, 'getList')),
        );
    }


    /**
     * @param File $file
     *
     * @return \Archive7z\Entry[]|null
     */
    public function getList (File $file)
    {
        $archive = $this->container->get('archive_7z');

        try {
            $entries = $archive->getEntries($file);
        } catch (\Exception $e) {
            $entries = null;
        }

        return $entries;
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_archive';
    }
}
