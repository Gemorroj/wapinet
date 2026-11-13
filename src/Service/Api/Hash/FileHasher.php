<?php

declare(strict_types=1);

namespace App\Service\Api\Hash;

/**
 * Читает файл один раз и считает выбранные алгоритмы сразу, без загрузки в память.
 * Ключи — те же идентификаторы, что и на клиенте, значения — имена алгоритмов PHP.
 */
final readonly class FileHasher
{
    public const array ALGORITHMS = [
        'md5' => 'md5',
        'sha1' => 'sha1',
        'sha224' => 'sha224',
        'sha256' => 'sha256',
        'sha384' => 'sha384',
        'sha512' => 'sha512',
        'sha512-224' => 'sha512/224',
        'sha512-256' => 'sha512/256',
        'sha3-224' => 'sha3-224',
        'sha3-256' => 'sha3-256',
        'sha3-384' => 'sha3-384',
        'sha3-512' => 'sha3-512',
        'ripemd160' => 'ripemd160',
        'crc32' => 'crc32b',
        'crc32c' => 'crc32c',
    ];

    /**
     * @return list<string>
     */
    public static function algorithmIds(): array
    {
        return \array_keys(self::ALGORITHMS);
    }

    /**
     * @param list<string> $algorithms
     *
     * @return array<string, string>
     */
    public function hash(string $path, array $algorithms): array
    {
        $handle = @\fopen($path, 'rb');

        if (false === $handle) {
            throw new \RuntimeException('Не удалось открыть файл для чтения.');
        }

        $contexts = [];

        foreach ($algorithms as $id) {
            if (!isset(self::ALGORITHMS[$id])) {
                \fclose($handle);

                throw new \InvalidArgumentException(\sprintf('Неизвестный алгоритм: %s.', $id));
            }

            $contexts[$id] = \hash_init(self::ALGORITHMS[$id]);
        }

        try {
            while (!\feof($handle)) {
                $chunk = \fread($handle, 1 << 16);

                if (false === $chunk) {
                    throw new \RuntimeException('Не удалось прочитать файл.');
                }

                foreach ($contexts as $context) {
                    \hash_update($context, $chunk);
                }
            }
        } finally {
            \fclose($handle);
        }

        $hashes = [];

        foreach ($contexts as $id => $context) {
            $hashes[$id] = \hash_final($context);
        }

        return $hashes;
    }
}

