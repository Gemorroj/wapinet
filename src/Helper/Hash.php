<?php

namespace App\Helper;

/**
 * Hash хэлпер
 */
class Hash
{
    /**
     * @return array
     */
    public function getAlgorithms(): array
    {
        return \hash_algos();
    }

    /**
     * @param string $algorithm
     * @param string $string
     *
     * @return string
     */
    public function hashString(string $algorithm, string $string): string
    {
        return \hash($algorithm, $string);
    }

    /**
     * @param string $algorithm
     * @param string $fileName
     *
     * @return string
     */
    public function hashFile(string $algorithm, string $fileName): string
    {
        return \hash_file($algorithm, $fileName);
    }
}
