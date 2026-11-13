<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\MimeGuesser;
use PHPUnit\Framework\TestCase;

final class MimeGuesserTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = \sys_get_temp_dir().'/wapinet_'.\bin2hex(\random_bytes(4)).'.jar';

        $archive = new \ZipArchive();
        $archive->open($this->file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $archive->addFromString('META-INF/MANIFEST.MF', "Manifest-Version: 1.0\n");
        $archive->close();
    }

    protected function tearDown(): void
    {
        @\unlink($this->file);
    }

    public function testPrefersSpecificExtensionForZipBasedFormats(): void
    {
        $mimeType = new MimeGuesser()->getMimeType(new \SplFileInfo($this->file));

        static::assertContains($mimeType, [
            'application/java-archive',
            'application/x-java-archive',
            'application/x-jar',
        ]);
    }

    public function testDetectsSplitArchiveExtensions(): void
    {
        $guesser = new MimeGuesser();

        foreach ([
            '001' => 'application/x-7z-compressed',
            'r00' => 'application/x-rar',
            'z01' => 'application/zip',
        ] as $extension => $expected) {
            $file = \sys_get_temp_dir().'/wapinet_'.\bin2hex(\random_bytes(4)).'.'.$extension;
            \file_put_contents($file, 'placeholder');

            try {
                static::assertSame($expected, $guesser->getMimeType(new \SplFileInfo($file)));
            } finally {
                @\unlink($file);
            }
        }
    }
}
