<?php

namespace App\Tests\Service\FileContent\Audio;

use App\Service\FileContent\Audio\AudioProcessor;
use App\Service\FileContent\Ffmpeg;
use App\Service\FileContent\Getid3;
use App\Service\FileSystem\Storage;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class AudioProcessorTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_audio_'.\uniqid('', true);
        \mkdir($this->directory.'/public/static/file', 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    public function testExtractsEmbeddedCover(): void
    {
        $file = $this->directory.'/public/static/file/test.mp3';

        \file_put_contents($file, $this->mp3(true));

        self::assertSame('/static/file/test.mp3.png', $this->audio()->getCover('/static/file/test.mp3'));
        self::assertFileExists($file.'.png');
    }

    public function testReturnsCachedCover(): void
    {
        $file = $this->directory.'/public/static/file/test.mp3';

        \file_put_contents($file, $this->mp3(true));
        \file_put_contents($file.'.jpg', 'cached');

        self::assertSame('/static/file/test.mp3.jpg', $this->audio()->getCover('/static/file/test.mp3'));
    }

    public function testReturnsNullWithoutCover(): void
    {
        \file_put_contents($this->directory.'/public/static/file/test.mp3', $this->mp3(false));

        self::assertNull($this->audio()->getCover('/static/file/test.mp3'));
    }

    private function audio(): AudioProcessor
    {
        return new AudioProcessor(
            new Ffmpeg('/usr/bin/ffmpeg', '/usr/bin/ffprobe', 3),
            new Getid3(),
            new Storage($this->directory.'/public'),
            new NullLogger(),
        );
    }

    /**
     * Minimal MP3: an ID3v2.3 tag (optionally with a PNG cover) and one audio frame.
     */
    private function mp3(bool $withCover): string
    {
        $tag = '';

        if ($withCover) {
            $image = \imagecreatetruecolor(16, 16);
            \ob_start();
            \imagepng($image);
            $png = (string) \ob_get_clean();

            $body = "\x00".'image/png'."\x00\x03".'cover'."\x00".$png;
            $size = \strlen($body);
            $tag = 'APIC'
                .\chr(($size >> 24) & 0xFF).\chr(($size >> 16) & 0xFF).\chr(($size >> 8) & 0xFF).\chr($size & 0xFF)
                ."\x00\x00".$body;
        }

        $size = \strlen($tag);
        $syncSafe = \chr(($size >> 21) & 0x7F).\chr(($size >> 14) & 0x7F).\chr(($size >> 7) & 0x7F).\chr($size & 0x7F);

        return 'ID3'."\x03\x00\x00".$syncSafe.$tag."\xFF\xFB\x90\x64".\str_repeat("\x00", 413);
    }

    private function removeDirectory(string $directory): void
    {
        if (!\is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? \rmdir($item->getPathname()) : \unlink($item->getPathname());
        }

        \rmdir($directory);
    }
}
