<?php

namespace App\Exception;

use App\Entity\File;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

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
     * @var RouterInterface
     */
    protected $router;

    public function __construct(File $existingFile, RouterInterface $router)
    {
        $this->existingFile = $existingFile;
        $this->router = $router;

        parent::__construct('Такой файл уже существует: '.$this->getPath());
    }

    public function getExistingFile(): File
    {
        return $this->existingFile;
    }

    public function getPath(): string
    {
        return $this->router->generate('file_view', [
                'id' => $this->getExistingFile()->getId(),
            ], Router::ABSOLUTE_URL
        );
    }
}
