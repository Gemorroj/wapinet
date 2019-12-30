<?php

namespace App\Service;

/**
 * Hash хэлпер
 */
class Hash
{
    public function getAlgorithms(): array
    {
        return \hash_algos();
    }

    public function hashString(string $algorithm, string $string): string
    {
        return \hash($algorithm, $string);
    }

    public function hashFile(string $algorithm, string $fileName): string
    {
        return \hash_file($algorithm, $fileName);
    }
}
