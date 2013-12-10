<?php

namespace Wapinet\UploaderBundle\Naming;

use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Wapinet\UserBundle\Entity\User;

/**
 * UserAvatarDateDirectoryNamer
 */
class UserAvatarDirectoryNamer implements DirectoryNamerInterface
{
    /**
     * Creates a directory name for the file being uploaded.
     *
     * @param User $obj The object the upload is attached to.
     * @param string $field The name of the uploadable field to generate a name for.
     * @param string $uploadDir The upload directory set in config
     * @return string The directory name.
     */
    public function directoryName($obj, $field, $uploadDir)
    {
        return $uploadDir . '/' . $obj->getCreatedAt()->format('Y/m/d') . '/id' . $obj->getId();
    }
}
