<?php

namespace Wapinet\UploaderBundle\Naming;

use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

/**
 * DateDirectoryNamer
 */
class DirectoryNamer implements DirectoryNamerInterface
{
    /**
     * Creates a directory name for the file being uploaded.
     *
     * @param object $obj The object the upload is attached to.
     * @param string $field The name of the uploadable field to generate a name for.
     * @param string $uploadDir The upload directory set in config
     * @return string The directory name.
     */
    public function directoryName($obj, $field, $uploadDir)
    {
        return $uploadDir . '/' . str_replace('\\', '/', get_class($obj)) . '/' . $obj->getId();
    }
}
