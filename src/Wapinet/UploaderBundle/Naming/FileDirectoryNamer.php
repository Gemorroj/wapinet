<?php

namespace Wapinet\UploaderBundle\Naming;

use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;

/**
 * FileDirectoryNamer
 */
class FileDirectoryNamer implements DirectoryNamerInterface
{
    /**
     * Creates a directory name for the file being uploaded.
     *
     * @param object          $object  The object the upload is attached to.
     * @param Propertymapping $mapping The mapping to use to manipulate the given object.
     *
     * @note The $object parameter can be null.
     *
     * @return string The directory name.
     */
    public function directoryName($object, PropertyMapping $mapping)
    {
        return $mapping->getUploadDestination() . '/' . $object->getCreatedAt()->format('Y/m/d');
    }
}
