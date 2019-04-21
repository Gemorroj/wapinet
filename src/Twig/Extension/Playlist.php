<?php

namespace App\Twig\Extension;

use Exception;
use M3uParser\M3uData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Playlist extends AbstractExtension
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
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('wapinet_playlist_list', [$this, 'getList']),
        ];
    }

    /**
     * @param File $file
     *
     * @return M3uData|null
     */
    public function getList(File $file)
    {
        $playlist = $this->container->get('playlist');

        try {
            return $playlist->parseFile($file);
        } catch (Exception $e) {
            $this->container->get('logger')->warning($e->getMessage());

            return null;
        }
    }
}
