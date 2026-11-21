<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent;

use App\Entity\File as EntityFile;
use App\Service\FileContent\Android\Apk;
use App\Service\FileContent\Audio\Midi;
use App\Service\FileContent\Ffmpeg;
use App\Service\FileContent\MetadataExtractor;
use App\Service\FileContent\Torrent;
use Imagine\Gd\Imagine;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Symfony\Component\Process\Process;

#[AllowMockObjectsWithoutExpectations]
final class MetadataExtractorTest extends TestCase
{
    private string $directory;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_meta_'.\bin2hex(\random_bytes(4));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->directory);
    }

    public function testExtractsImageDimensions(): void
    {
        $path = $this->directory.'/image.png';
        $image = \imagecreatetruecolor(3, 2);
        self::assertNotFalse($image);
        \imagepng($image, $path);

        $meta = $this->extractor()->getFileMeta($this->file($path, image: true));

        self::assertSame(3, $meta->get('width'));
        self::assertSame(2, $meta->get('height'));
        self::assertFalse($meta->has('dateTimeOriginal'));
        self::assertFalse($meta->has('comment'));
    }

    public function testExtractsAudioStreamMetadata(): void
    {
        $path = $this->directory.'/sample.mp3';
        \copy(__DIR__.'/../../fixtures/audio/sample.mp3', $path);

        $meta = $this->extractor()->getFileMeta($this->file($path, audio: true));

        self::assertGreaterThan(0, $meta->get('duration'));
        self::assertSame('mp3', $meta->get('codecName'));
        self::assertGreaterThan(0, $meta->get('sampleRate'));
        self::assertGreaterThan(0, $meta->get('bitRate'));
    }

    public function testExtractsVideoStreamMetadata(): void
    {
        $path = $this->directory.'/video.mp4';
        $this->createVideo($path);

        $meta = $this->extractor()->getFileMeta($this->file($path, video: true));

        self::assertSame(64, $meta->get('width'));
        self::assertSame(48, $meta->get('height'));
        self::assertSame('h264', $meta->get('codecName'));
        self::assertGreaterThan(0, $meta->get('duration'));
        self::assertFalse($meta->has('audioCodecName'));
    }

    public function testExtractsAndroidManifestMetadata(): void
    {
        $path = $this->directory.'/app.apk';
        \copy(__DIR__.'/../../fixtures/apk/ebhs.apk', $path);

        $meta = $this->extractor()->getFileMeta($this->file($path, android: true));

        self::assertSame('com.cks.EBHS', $meta->get('packageName'));
        self::assertSame('1.0', $meta->get('versionName'));
        self::assertSame(['INTERNET'], \array_slice(\array_keys((array) $meta->get('permissions')), 0, 1));
    }

    public function testExtractsMidiDuration(): void
    {
        $path = $this->directory.'/melody.mid';
        \file_put_contents($path, $this->midi());

        $meta = $this->extractor()->getFileMeta($this->file($path, audio: true, midi: true));

        self::assertEqualsWithDelta(0.5, $meta->get('duration'), 0.0001);
    }

    public function testExtractsSingleFileTorrentMetadata(): void
    {
        $path = $this->directory.'/single.torrent';
        \file_put_contents($path, 'd7:comment5:hello13:creation datei1700000000e4:infod6:lengthi123e4:name8:file.txtee');

        $meta = $this->extractor()->getFileMeta($this->file($path, torrent: true));

        self::assertSame(123, $meta->get('size'));
        self::assertSame('file.txt', $meta->get('name'));
        self::assertSame('hello', $meta->get('comment'));
        self::assertSame(1700000000, $meta->get('datetime')->getTimestamp());
    }

    public function testSumsSizesOfMultiFileTorrent(): void
    {
        $path = $this->directory.'/multi.torrent';
        \file_put_contents(
            $path,
            'd4:infod5:filesld6:lengthi10e4:name1:aed6:lengthi5e4:name1:beeee',
        );

        $meta = $this->extractor()->getFileMeta($this->file($path, torrent: true));

        self::assertSame(15, $meta->get('size'));
    }

    public function testReturnsEmptyMetadataForUnknownType(): void
    {
        $path = $this->directory.'/file.bin';
        \file_put_contents($path, 'data');

        $meta = $this->extractor()->getFileMeta($this->file($path));

        self::assertSame('', (string) $meta);
    }

    private function extractor(): MetadataExtractor
    {
        return new MetadataExtractor(
            new Ffmpeg($this->binary('FFMPEG_PATH'), $this->binary('FFPROBE_PATH')),
            new Apk(),
            new Torrent(),
            new Imagine(),
            new Midi(),
        );
    }

    private function binary(string $name): string
    {
        $path = (string) ($_SERVER[$name] ?? $_ENV[$name] ?? '');

        if ('' === $path || !\is_file($path)) {
            self::markTestSkipped('The '.$name.' binary is not available.');
        }

        return $path;
    }

    private function createVideo(string $path): void
    {
        $process = new Process([
            $this->binary('FFMPEG_PATH'),
            '-y',
            '-f',
            'lavfi',
            '-i',
            'testsrc=size=64x48:rate=10',
            '-t',
            '1',
            '-pix_fmt',
            'yuv420p',
            $path,
        ]);
        $process->mustRun();
    }

    private function file(
        string $path,
        bool $video = false,
        bool $audio = false,
        bool $image = false,
        bool $torrent = false,
        bool $midi = false,
        bool $android = false,
    ): EntityFile {
        $file = $this->createMock(EntityFile::class);
        $file->method('isVideo')->willReturn($video);
        $file->method('isAudio')->willReturn($audio);
        $file->method('isMidi')->willReturn($midi);
        $file->method('isImage')->willReturn($image);
        $file->method('isTorrent')->willReturn($torrent);
        $file->method('isAndroidApp')->willReturn($android);
        $file->method('getFile')->willReturn(new BaseFile($path));

        return $file;
    }

    private function midi(): string
    {
        $header = 'MThd'.\pack('N', 6).\pack('n', 0).\pack('n', 1).\pack('n', 96);
        $events = "\x00\xFF\x51\x03\x07\xA1\x20"
            ."\x00\x90\x3C\x64"
            ."\x60\x80\x3C\x40"
            ."\x00\xFF\x2F\x00";

        return $header.'MTrk'.\pack('N', \strlen($events)).$events;
    }
}
