<?php

declare(strict_types=1);

namespace App\DBAL\Types;

use App\Entity\File\Meta;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class FileMetaType extends Type
{
    public const string TYPE_NAME = 'file_meta';

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof Meta) {
            return \json_encode($value, \JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException('Unknown type');
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Meta
    {
        if (null === $value) {
            return null;
        }

        $data = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR | \JSON_BIGINT_AS_STRING);

        return Meta::create($data);
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDoctrineTypeMapping(static::TYPE_NAME);
    }

    public function getName(): string
    {
        return self::TYPE_NAME;
    }
}
