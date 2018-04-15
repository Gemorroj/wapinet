<?php

namespace App\Twig\Extension;

use M3uParser\M3uEntry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class Playlist extends \Twig_Extension
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
            new \Twig_SimpleFunction('wapinet_playlist_list', array($this, 'getList')),
        );
    }


    /**
     * @param File $file
     *
     * @return M3uEntry[]|null
     */
    public function getList (File $file)
    {
        $playlist = $this->container->get('playlist');

        try {
            return $playlist->parseFile($file);
        } catch (\Exception $e) {
            $this->container->get('logger')->warning($e->getMessage());
            return null;
        }
    }
}
