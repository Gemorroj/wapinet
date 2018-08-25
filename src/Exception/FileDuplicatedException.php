<?php

namespace App\Exception;

use App\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Router;

/**
 * Thrown whenever a client process fails.
 */
class FileDuplicatedException extends \RuntimeException
{
    /**
     * @var File
     */
    protected $existingFile;
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param File               $existingFile
     * @param ContainerInterface $container
     */
    public function __construct(File $existingFile, ContainerInterface $container)
    {
        $this->existingFile = $existingFile;
        $this->container = $container;

        parent::__construct('Такой файл уже существует: '.$this->getPath());
    }

    /**
     * @return File
     */
    public function getExistingFile()
    {
        return $this->existingFile;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->getContainer()->get('router')->generate('file_view', [
                'id' => $this->getExistingFile()->getId(),
            ], Router::ABSOLUTE_URL
        );
    }
}
