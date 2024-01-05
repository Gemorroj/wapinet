<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\File;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class FileDuplicatedException extends \RuntimeException
{
    public function __construct(private readonly File $existingFile, private readonly RouterInterface $router)
    {
        parent::__construct('Такой файл уже существует: '.$this->getPath());
    }

    public function getExistingFile(): File
    {
        return $this->existingFile;
    }

    public function getPath(): string
    {
        return $this->router->generate(
            'file_view',
            [
                'id' => $this->getExistingFile()->getId(),
            ],
            Router::ABSOLUTE_URL
        );
    }
}
