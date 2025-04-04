<?php

namespace App\Uploader\Naming;

use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

final readonly class FileDirectoryNamer implements DirectoryNamerInterface
{
    public function directoryName(object|array $object, PropertyMapping $mapping): string
    {
        return '/'.$object->getCreatedAt()->format('Y/m/d');
    }
}
