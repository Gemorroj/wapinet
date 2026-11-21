<?php

declare(strict_types=1);

namespace App\Service\Api\Obfuscator;

interface PhpCodeObfuscatorInterface
{
    public function obfuscate(
        string $code,
        bool $removeComments = true,
        ObfuscatorLevel $level = ObfuscatorLevel::Max,
    ): string;
}
