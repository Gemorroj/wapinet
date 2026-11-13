<?php

namespace App\Tests\Enum;

use App\Entity\File;
use App\Enum\FileConversion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FileConversionTest extends TestCase
{
    #[DataProvider('provideMimeTypes')]
    public function testConversionTarget(string $mimeType, ?FileConversion $expected): void
    {
        $file = new File();
        $file->setMimeType($mimeType);

        self::assertSame($expected, $file->getConversion());
    }

    /**
     * @return iterable<string, array{string, ?FileConversion}>
     */
    public static function provideMimeTypes(): iterable
    {
        yield 'amr audio' => ['audio/amr', FileConversion::Mp3];
        yield 'encrypted amr audio' => ['audio/amr-encrypted', FileConversion::Mp3];
        yield 'flv video' => ['video/x-flv', FileConversion::Mp4];
        yield 'avi video' => ['video/x-msvideo', FileConversion::Mp4];
        yield 'mov video' => ['video/quicktime', FileConversion::Mp4];
        yield '3gp video' => ['video/3gpp', FileConversion::Mp4];
        yield 'wmv video' => ['video/x-ms-wmv', FileConversion::Mp4];
        yield 'mp4 video' => ['video/mp4', null];
        yield 'webm video' => ['video/webm', null];
        yield 'mp3 audio' => ['audio/mpeg', null];
        yield 'image' => ['image/png', null];
        yield 'text' => ['text/plain', null];
    }
}
