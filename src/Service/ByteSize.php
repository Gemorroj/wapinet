<?php

declare(strict_types=1);

namespace App\Service;

final class ByteSize
{
    public static function format(int $bytes): string
    {
        return match (true) {
            $bytes >= 1024 ** 3 => \round($bytes / 1024 ** 3).' ГБ',
            $bytes >= 1024 ** 2 => \round($bytes / 1024 ** 2).' МБ',
            $bytes >= 1024 => \round($bytes / 1024).' КБ',
            default => $bytes.' Б',
        };
    }
}
