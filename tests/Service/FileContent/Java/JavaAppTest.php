<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent\Java;

use App\Service\Archiver\ArchiveLimits;
use App\Service\Archiver\ArchiveZip;
use App\Service\FileContent\Java\JavaApp;
use App\Service\FileSystem\Storage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class JavaAppTest extends TestCase
{
    private string $root;
    private Storage $storage;
    private JavaApp $javaApp;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir().'/wapinet_java_'.\bin2hex(\random_bytes(4));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir([$this->root.'/public/upload', $this->root.'/tmp']);

        $this->storage = new Storage($this->root.'/public');
        $this->javaApp = new JavaApp(
            new ArchiveZip(new ArchiveLimits(), 'comment'),
            $this->filesystem,
            $this->storage,
            $this->root.'/tmp',
        );
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testExtractsIconReferencedByMidletIcon(): void
    {
        $this->createJar(['META-INF/MANIFEST.MF' => "MIDlet-Icon: /icons/app.png\n", 'icons/app.png' => 'png-data']);

        self::assertSame('/upload/app.jar.png', $this->javaApp->getScreenshot('/upload/app.jar'));
        self::assertSame('png-data', \file_get_contents($this->root.'/public/upload/app.jar.png'));
    }

    public function testExtractsIconFromNestedPathInMidletOne(): void
    {
        $this->createJar([
            'META-INF/MANIFEST.MF' => "MIDlet-1: My App, res/icons/app.png, com.example.Main\nMIDlet-Vendor: Example\n",
            'res/icons/app.png' => 'nested-png',
        ]);

        self::assertSame('/upload/app.jar.png', $this->javaApp->getScreenshot('/upload/app.jar'));
        self::assertSame('nested-png', \file_get_contents($this->root.'/public/upload/app.jar.png'));
    }

    public function testPrefersTheFirstPngOfMidletOne(): void
    {
        $this->createJar([
            'META-INF/MANIFEST.MF' => "MIDlet-1: My App, /icons/second.png, /icons/first.png, com.example.Main\n",
            'icons/second.png' => 'second',
            'icons/first.png' => 'first',
        ]);

        self::assertSame('/upload/app.jar.png', $this->javaApp->getScreenshot('/upload/app.jar'));
        self::assertSame('second', \file_get_contents($this->root.'/public/upload/app.jar.png'));
    }

    public function testIgnoresNonPngIconAndFallsBackToMidletOne(): void
    {
        $this->createJar([
            'META-INF/MANIFEST.MF' => "MIDlet-Icon: /icons/logo.svg\nMIDlet-1: My App, /icons/app.png, com.example.Main\n",
            'icons/logo.svg' => '<svg/>',
            'icons/app.png' => 'fallback-png',
        ]);

        self::assertSame('/upload/app.jar.png', $this->javaApp->getScreenshot('/upload/app.jar'));
        self::assertSame('fallback-png', \file_get_contents($this->root.'/public/upload/app.jar.png'));
    }

    public function testReturnsNullWithoutManifest(): void
    {
        $this->createJar(['com/example/Main.class' => 'class']);

        self::assertNull($this->javaApp->getScreenshot('/upload/app.jar'));
    }

    public function testReturnsNullWhenManifestHasNoPngIcon(): void
    {
        $this->createJar([
            'META-INF/MANIFEST.MF' => "MIDlet-Icon: /icons/logo.bmp\n",
            'icons/logo.bmp' => 'bmp',
        ]);

        self::assertNull($this->javaApp->getScreenshot('/upload/app.jar'));
    }

    public function testTraversalInManifestNeverWritesOutsideTheTemporaryDirectory(): void
    {
        $this->createJar([
            'META-INF/MANIFEST.MF' => "MIDlet-1: My App, ../../escape.png, com.example.Main\n",
            '../../escape.png' => 'escaping-png',
        ]);

        self::assertNull($this->javaApp->getScreenshot('/upload/app.jar'));
        self::assertFileDoesNotExist($this->root.'/escape.png');
        self::assertFileDoesNotExist($this->root.'/public/upload/app.jar.png');
    }

    public function testReturnsExistingScreenshotWithoutReadingTheArchive(): void
    {
        $this->filesystem->dumpFile($this->root.'/public/upload/app.jar.png', 'cached');

        self::assertSame('/upload/app.jar.png', $this->javaApp->getScreenshot('/upload/app.jar'));
        self::assertSame('cached', \file_get_contents($this->root.'/public/upload/app.jar.png'));
    }

    public function testFindReturnsOnlyExistingScreenshot(): void
    {
        self::assertNull($this->javaApp->find('/upload/app.jar'));

        $this->filesystem->dumpFile($this->root.'/public/upload/app.jar.png', 'cached');

        self::assertSame('/upload/app.jar.png', $this->javaApp->find('/upload/app.jar'));
    }

    /**
     * @param array<string, string> $entries
     */
    private function createJar(array $entries): void
    {
        $path = $this->root.'/public/upload/app.jar';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
    }
}
