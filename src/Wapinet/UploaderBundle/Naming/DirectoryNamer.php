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
        // Proxies/__CG__/Wapinet/UserBundle/Entity/User
        $class = get_class($obj);
        $list = explode('\\Entity\\', $class);
        // User
        $class = $list[1];

        return $uploadDir . '/' . str_replace('\\', '/', $class) . '/' . $obj->getId();
    }
}
