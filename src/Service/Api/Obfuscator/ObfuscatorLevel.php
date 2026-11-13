<?php

declare(strict_types=1);

namespace App\Service\Api\Obfuscator;

enum ObfuscatorLevel: string
{
    case Light = 'light';
    case Normal = 'normal';
    case Max = 'max';
}
