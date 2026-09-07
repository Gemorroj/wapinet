<?php

namespace App\Uploader\Naming;

use Vich\UploaderBundle\Mapping\PropertyMappingInterface;
use Vich\UploaderBundle\Naming\DirectoryNamerInterface;

final readonly class FileDirectoryNamer implements DirectoryNamerInterface
{
    public function directoryName(object|array $object, PropertyMappingInterface $mapping): string
    {
        $createdAt = null;
        if (\is_array($object)) {
            $createdAt = $object['createdAt'] ?? null;
        } elseif (\method_exists($object, 'getCreatedAt')) {
            $createdAt = $object->getCreatedAt();
        }

        if (!$createdAt instanceof \DateTimeInterface) {
            $createdAt = new \DateTime();
        }

        return $createdAt->format('Y/m/d');
    }
}
