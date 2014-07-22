<?php

namespace Wapinet\UploaderBundle\Naming;

use Vich\UploaderBundle\Naming\DirectoryNamerInterface;
use Vich\UploaderBundle\Mapping\PropertyMapping;

/**
 * UserAvatarDateDirectoryNamer
 */
class UserAvatarDirectoryNamer implements DirectoryNamerInterface
{
    /**
     * @inheritdoc
     */
    public function directoryName($object, PropertyMapping $mapping)
    {
        return '/' . $object->getCreatedAt()->format('Y/m/d') . '/id' . $object->getId();
    }
}
