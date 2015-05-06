<?php

namespace Wapinet\Bundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class Torrent extends \Twig_Extension
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
            new \Twig_SimpleFunction('wapinet_torrent_list', array($this, 'getList')),
        );
    }


    /**
     * @param File $file
     *
     * @return array|null
     */
    public function getList (File $file)
    {
        $torrent = $this->container->get('torrent');

        try {
            $data = $torrent->decodeFile($file);
        } catch (\Exception $e) {
            $this->container->get('logger')->warning($e->getMessage());
            return null;
        }

        $list = array();
        foreach ($data['info']['files'] as $entry) {
            $list[] = array(
                'path' => \implode('/', $entry['path']),
                'size' => $entry['length'],
            );
        }

        return $list;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_torrent';
    }
}
