<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent;

use App\Service\FileContent\Ffmpeg;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class FfmpegTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_ffmpeg_'.\bin2hex(\random_bytes(4));
        \mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->directory.'/*') ?: [] as $file) {
            @\unlink($file);
        }

        @\rmdir($this->directory);
    }

    public function testScreenshotOfTheFirstFrame(): void
    {
        $video = $this->video();
        $output = $this->directory.'/first.jpg';

        $this->ffmpeg()->screenshot($video, $output, 0);

        self::assertFileExists($output);
        self::assertSame('image/jpeg', \getimagesize($output)['mime'] ?? null);
    }

    public function testScreenshotAtASecond(): void
    {
        $video = $this->video();
        $output = $this->directory.'/second.jpg';

        $this->ffmpeg()->screenshot($video, $output, 1);

        self::assertFileExists($output);
        self::assertSame('image/jpeg', \getimagesize($output)['mime'] ?? null);
    }

    private function ffmpeg(): Ffmpeg
    {
        return new Ffmpeg($this->binary('FFMPEG_PATH'), $this->binary('FFPROBE_PATH'), 1);
    }

    private function video(): string
    {
        $path = $this->directory.'/video.mp4';
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

        return $path;
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
