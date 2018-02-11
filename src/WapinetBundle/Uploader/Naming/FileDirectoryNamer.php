<?php

namespace WapinetBundle\Uploader\Naming;

use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;

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
