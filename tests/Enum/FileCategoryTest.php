<?php

declare(strict_types=1);

namespace App\Tests\Enum;

use App\Entity\File;
use App\Enum\FileCategory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileCategoryTest extends TestCase
{
    #[DataProvider('files')]
    public function testClassifiesFile(string $mimeType, string $fileName, FileCategory $expected): void
    {
        $file = new File()
            ->setMimeType($mimeType)
            ->setFileName($fileName);

        static::assertSame($expected, FileCategory::fromFile($file));
    }

    /**
     * @return iterable<string, array{string, string, FileCategory}>
     */
    public static function files(): iterable
    {
        yield 'image' => ['image/png', 'photo.png', FileCategory::Image];
        yield 'video' => ['video/x-flv', 'clip.flv', FileCategory::Video];
        yield 'audio' => ['audio/midi', 'melody.mid', FileCategory::Audio];
        yield 'text' => ['text/plain', 'readme.txt', FileCategory::Text];
        yield 'office rtf' => ['text/rtf', 'document.rtf', FileCategory::Office];
        yield 'archive' => ['application/zip', 'archive.zip', FileCategory::Archive];
        yield 'apk' => ['application/zip', 'app.apk', FileCategory::Android];
        yield 'jar' => ['application/x-java-archive', 'app.jar', FileCategory::Java];
        yield 'windows' => ['application/x-dosexec', 'app.exe', FileCategory::Windows];
        yield 'playlist' => ['audio/x-mpegurl', 'playlist.m3u', FileCategory::Playlist];
        yield 'torrent' => ['application/x-bittorrent', 'data.torrent', FileCategory::Torrent];
        yield 'other' => ['application/octet-stream', 'data.bin', FileCategory::Other];
    }
}
