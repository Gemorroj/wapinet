<?php

namespace App\Twig\Extension;

use M3uParser\M3uData;
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
     * @inheritdoc
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('wapinet_playlist_list', [$this, 'getList']),
        ];
    }


    /**
     * @param File $file
     *
     * @return M3uData|null
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
