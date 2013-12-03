<?php
namespace Wapinet\Bundle\Helper\Archiver;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Wapinet\Bundle\Exception\ArchiverException;

/**
 * Archive хэлпер
 */
abstract class Archive
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
     * @param string $directory
     * @return string
     */
    protected function getTmpArchive($directory)
    {
        return $directory . '.tmp';
    }

    /**
     * @param string $directory
     * @throws ArchiverException
     * @return File
     */
    abstract public function create ($directory);
}
