<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Formats browsers cannot play natively, converted in the background after upload.
 */
enum FileConversion: string
{
    case Mp3 = 'mp3';
    case Mp4 = 'mp4';
}
