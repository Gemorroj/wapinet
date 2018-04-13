<?php

namespace App\Uploader\Naming;

use Vich\UploaderBundle\Mapping\PropertyMapping;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * FileDirectoryNamer
 */
class FileDirectoryNamer implements DirectoryNamerInterface
{
    /**
     * @inheritdoc
     */
    public function directoryName($object, PropertyMapping $mapping): string
    {
        return '/' . $object->getCreatedAt()->format('Y/m/d');
    }
}
