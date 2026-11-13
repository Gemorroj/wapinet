<?php

namespace App\Tests\Service\FileContent;

use App\Entity\File;
use App\Service\FileContent\AndroidApp;
use App\Service\FileContent\Audio;
use App\Service\FileContent\JavaApp;
use App\Service\FileContent\PreviewGenerator;
use App\Service\FileContent\Video;
use App\Service\FileSystem\Storage;
use PHPUnit\Framework\TestCase;

class PreviewGeneratorTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_preview_'.\uniqid('', true);
        \mkdir($this->directory.'/static/file', 0o777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    public function testSourceOnlyReadsExistingPreview(): void
    {
        $video = $this->createMock(Video::class);
        $video->expects(self::once())->method('find')->with('/static/video.avi')->willReturn(null);
        $video->expects(self::never())->method('getScreenshot');

        $preview = $this->preview($video);

        self::assertNull($preview->source(new File()->setMimeType('video/x-msvideo'), '/static/video.avi'));
    }

    public function testSourceReturnsReadyPreview(): void
    {
        $video = $this->createStub(Video::class);
        $video->method('find')->willReturn('/static/video.avi.jpg');

        $preview = $this->preview($video);

        self::assertSame('/static/video.avi.jpg', $preview->source(new File()->setMimeType('video/x-msvideo'), '/static/video.avi'));
    }

    public function testImagesAreServedAsIsOnlyForThumbnailableFormats(): void
    {
        $preview = $this->preview($this->createStub(Video::class));

        self::assertSame('/static/image.png', $preview->source(new File()->setMimeType('image/png'), '/static/image.png'));
        self::assertNull($preview->source(new File()->setMimeType('image/svg+xml'), '/static/image.svg'));
    }

    public function testGenerateBuildsPreview(): void
    {
        $video = $this->createMock(Video::class);
        $video->expects(self::once())->method('getScreenshot')->with('/static/video.avi');

        $this->preview($video)->generate(new File()->setMimeType('video/x-msvideo'), '/static/video.avi');
    }

    public function testGenerateMarksFileWithoutPreview(): void
    {
        $video = $this->createStub(Video::class);
        $video->method('find')->willReturn(null);
        $video->method('getScreenshot')->willReturn(null);

        $file = new File()->setMimeType('application/java-archive');
        $preview = $this->preview($video);

        $preview->generate($file, '/static/app.jar');

        self::assertFileExists($this->directory.'/static/app.jar.no-preview');
        self::assertNull($preview->source($file, '/static/app.jar'));
        self::assertFalse($preview->needsGeneration($file, '/static/app.jar'));
    }

    public function testNeedsGenerationForFreshFile(): void
    {
        $video = $this->createStub(Video::class);
        $video->method('find')->willReturn(null);

        self::assertTrue($this->preview($video)->needsGeneration(new File()->setMimeType('video/x-msvideo'), '/static/video.avi'));
    }

    public function testGenerateSkipsFileMarkedAsWithoutPreview(): void
    {
        \file_put_contents($this->directory.'/static/app.jar.no-preview', '');

        $video = $this->createMock(Video::class);
        $video->expects(self::never())->method('getScreenshot');

        $this->preview($video)->generate(new File()->setMimeType('video/x-msvideo'), '/static/app.jar');
    }

    public function testGenerateIgnoresFilesWithoutPreview(): void
    {
        $video = $this->createMock(Video::class);
        $video->expects(self::never())->method('getScreenshot');

        $this->preview($video)->generate(new File()->setMimeType('text/plain'), '/static/file.txt');
    }

    private function preview(Video $video): PreviewGenerator
    {
        return new PreviewGenerator(
            $video,
            $this->createStub(Audio::class),
            $this->createStub(JavaApp::class),
            $this->createStub(AndroidApp::class),
            new Storage($this->directory),
        );
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
