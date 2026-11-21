<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent;

use App\Service\FileContent\Ffmpeg;
use App\Service\FileContent\Video\VideoProcessor;
use App\Service\FileSystem\Storage;
use App\Tests\Stub\TestableVideo;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\FFProbe\DataMapping\Stream;
use FFMpeg\FFProbe\DataMapping\StreamCollection;
use FFMpeg\Filters\Video\VideoFilters;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video as FfmpegVideo;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[AllowMockObjectsWithoutExpectations]
final class VideoProcessorTest extends TestCase
{
    private string $root;
    private Storage $storage;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir().'/wapinet_video_'.\bin2hex(\random_bytes(4));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir($this->root.'/public/upload');

        $this->storage = new Storage($this->root.'/public');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testOddDimensionsAreRoundedDownToEven(): void
    {
        $filters = $this->createMock(VideoFilters::class);
        $filters->expects(self::once())
            ->method('resize')
            ->with(
                self::callback(static fn (Dimension $size): bool => 98 === $size->getWidth() && 96 === $size->getHeight()),
                'inset',
            )
            ->willReturnSelf();
        $filters->expects(self::once())->method('synchronize')->willReturnSelf();

        $media = $this->media($this->file(1024), ['width' => 101, 'height' => 99], $filters);

        $this->video()->apply($this->format(), $media);
    }

    public function testEvenDimensionsAreNotResized(): void
    {
        $filters = $this->createMock(VideoFilters::class);
        $filters->expects(self::never())->method('resize');

        $media = $this->media($this->file(1024), ['width' => 64, 'height' => 48], $filters);

        $this->video()->apply($this->format(), $media);
    }

    public function testOnePixelDimensionsNeverBecomeNegative(): void
    {
        $filters = $this->createMock(VideoFilters::class);
        $filters->expects(self::once())
            ->method('resize')
            ->with(
                self::callback(static fn (Dimension $size): bool => 2 === $size->getWidth() && 2 === $size->getHeight()),
                'inset',
            )
            ->willReturnSelf();
        $filters->expects(self::once())->method('synchronize')->willReturnSelf();

        $media = $this->media($this->file(1024), ['width' => 1, 'height' => 1], $filters);

        $this->video()->apply($this->format(), $media);
    }

    public function testLowersTheBitrateForSmallVideos(): void
    {
        $format = $this->format(1000);
        $format->expects(self::once())->method('setKiloBitrate')->with(33);

        $media = $this->media($this->file(102400), ['width' => 64, 'height' => 48, 'duration' => 10], $this->createMock(VideoFilters::class));

        $this->video()->apply($format, $media);
    }

    public function testNeverSetsAZeroBitrate(): void
    {
        $format = $this->format(1000);
        $format->expects(self::never())->method('setKiloBitrate');

        $media = $this->media($this->file(1), ['width' => 64, 'height' => 48, 'duration' => 100000], $this->createMock(VideoFilters::class));

        $this->video()->apply($format, $media);
    }

    public function testScreenshotSecondIsCappedForShortVideos(): void
    {
        self::assertSame(1, $this->screenshotSecond(3));
        self::assertSame(0, $this->screenshotSecond(1));
        self::assertSame(0, $this->screenshotSecond(null));
        self::assertSame(5, $this->screenshotSecond(20));
    }

    public function testFindReturnsOnlyExistingScreenshot(): void
    {
        $video = $this->video();

        self::assertNull($video->find('/upload/movie.mkv'));

        $this->filesystem->dumpFile($this->root.'/public/upload/movie.mkv.jpg', 'jpg');

        self::assertSame('/upload/movie.mkv.jpg', $video->find('/upload/movie.mkv'));
    }

    public function testUsesTheExistingScreenshotWithoutConverting(): void
    {
        $this->filesystem->dumpFile($this->root.'/public/upload/movie.mkv.jpg', 'jpg');

        self::assertSame('/upload/movie.mkv.jpg', $this->video()->getScreenshot('/upload/movie.mkv'));
    }

    public function testReturnsTheExistingMp4(): void
    {
        $this->filesystem->dumpFile($this->root.'/public/upload/movie.mkv.mp4', 'mp4');

        self::assertSame('/upload/movie.mkv.mp4', $this->video()->convertToMp4('/upload/movie.mkv'));
    }

    #[IgnoreDeprecations]
    public function testConvertsARealVideoToMp4(): void
    {
        $source = '/upload/movie.mkv';
        $this->generateVideo($this->storage->absolute($source));

        self::assertSame('/upload/movie.mkv.mp4', $this->realVideo()->convertToMp4($source));
        self::assertFileExists($this->storage->absolute('/upload/movie.mkv.mp4'));
    }

    public function testExtractsARealScreenshot(): void
    {
        $source = '/upload/movie.mkv';
        $this->generateVideo($this->storage->absolute($source));

        self::assertSame('/upload/movie.mkv.jpg', $this->realVideo()->getScreenshot($source));
        self::assertSame('image/jpeg', \getimagesize($this->storage->absolute('/upload/movie.mkv.jpg'))['mime'] ?? null);
    }

    private function video(): TestableVideo
    {
        return new TestableVideo(new Ffmpeg('/bin/false', '/bin/false'), $this->storage, new NullLogger(), 5);
    }

    private function realVideo(): VideoProcessor
    {
        return new VideoProcessor(
            new Ffmpeg($this->binary('FFMPEG_PATH'), $this->binary('FFPROBE_PATH')),
            $this->storage,
            new NullLogger(),
            0,
        );
    }

    /**
     * @param array<string, int> $data
     */
    private function media(string $path, array $data, VideoFilters $filters): FfmpegVideo
    {
        $stream = new Stream(['codec_type' => 'video'] + $data);
        $media = $this->createMock(FfmpegVideo::class);
        $media->method('getStreams')->willReturn(new StreamCollection([$stream]));
        $media->method('getPathfile')->willReturn($path);
        $media->method('filters')->willReturn($filters);

        return $media;
    }

    /**
     * @return X264&MockObject
     */
    private function format(int $kiloBitrate = 1000): X264
    {
        $format = $this->createMock(X264::class);
        $format->method('getKiloBitrate')->willReturn($kiloBitrate);

        return $format;
    }

    private function screenshotSecond(?int $duration): int
    {
        $media = $this->createMock(FfmpegVideo::class);
        $media->method('getStreams')->willReturn(new StreamCollection([
            new Stream(['codec_type' => 'video', 'width' => 64, 'height' => 48] + (null === $duration ? [] : ['duration' => $duration])),
        ]));

        $method = new \ReflectionMethod(VideoProcessor::class, 'getScreenshotSecond');

        return $method->invoke(new VideoProcessor(new Ffmpeg('/bin/false', '/bin/false'), $this->storage, new NullLogger(), 5), $media);
    }

    private function file(int $size): string
    {
        $path = $this->root.'/public/upload/source.bin';
        $this->filesystem->dumpFile($path, \str_repeat('x', $size));

        return $path;
    }

    private function generateVideo(string $path): void
    {
        $process = new Process([
            $this->binary('FFMPEG_PATH'),
            '-y',
            '-f',
            'lavfi',
            '-i',
            'testsrc=size=64x48:rate=10',
            '-t',
            '2',
            '-pix_fmt',
            'yuv420p',
            $path,
        ]);
        $process->mustRun();
    }

    private function binary(string $name): string
    {
        $path = (string) ($_SERVER[$name] ?? $_ENV[$name] ?? '');

        if ('' === $path || !\is_file($path)) {
            self::markTestSkipped('The '.$name.' binary is not available.');
        }

        return $path;
    }
}
