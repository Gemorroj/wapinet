<?php

namespace App\Twig\Extension;

use Archive7z\Entry;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Archive extends AbstractExtension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Archive constructor.
     *
     * @param ContainerInterface $container
     */
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
        return [
            new TwigFunction('wapinet_archive_list', [$this, 'getList']),
        ];
    }

    /**
     * @param File $file
     *
     * @return Entry[]|null
     */
    public function getList(File $file)
    {
        $archive = $this->container->get('archive_7z');

        try {
            $entries = $archive->getEntries($file);
        } catch (Exception $e) {
            $this->container->get('logger')->warning($e->getMessage());
            $entries = null;
        }

        return $entries;
    }
}
