<?php
namespace Wapinet\Bundle\Exception;

use Wapinet\Bundle\Entity\File;

/**
 * Thrown whenever a client process fails.
 */
class FileDuplicatedException extends \RuntimeException
{
    protected $existingFile;

    public function __construct(File $existingFile)
    {
        $this->existingFile = $existingFile;
        parent::__construct('Такой файл уже существует.');
    }

    public function getExistingFile()
    {
        return $this->existingFile;
    }
}
