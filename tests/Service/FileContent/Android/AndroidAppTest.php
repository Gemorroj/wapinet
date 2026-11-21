<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent\Android;

use App\Service\Archiver\ArchiveLimits;
use App\Service\Archiver\ArchiveZip;
use App\Service\FileContent\Android\AndroidApp;
use App\Service\FileContent\Android\Apk;
use App\Service\FileSystem\Storage;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

final class AndroidAppTest extends TestCase
{
    private string $root;
    private Storage $storage;
    private AndroidApp $androidApp;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir().'/wapinet_android_'.\bin2hex(\random_bytes(4));
        $this->filesystem = new Filesystem();
        $this->filesystem->mkdir([$this->root.'/public/upload', $this->root.'/tmp']);

        $this->storage = new Storage($this->root.'/public');
        $this->androidApp = new AndroidApp(
            new ArchiveZip(new ArchiveLimits(), 'comment'),
            new Apk(),
            new NullLogger(),
            $this->filesystem,
            $this->storage,
            $this->root.'/tmp',
        );
    }

    protected function tearDown(): void
    {
        // The APK parser keeps the archive open, which locks the file on Windows.
        unset($this->androidApp);
        \gc_collect_cycles();

        $this->filesystem->remove($this->root);
    }

    public function testExtractsIconFromTheFallbackList(): void
    {
        $this->createApk(['res/drawable-hdpi/ic_launcher.png' => 'icon-data']);

        self::assertSame('/upload/app.apk.png', $this->androidApp->getScreenshot('/upload/app.apk'));
        self::assertSame('icon-data', \file_get_contents($this->root.'/public/upload/app.apk.png'));
    }

    public function testExtractsRootLevelIcon(): void
    {
        $this->createApk(['res/icon.png' => 'root-icon']);

        self::assertSame('/upload/app.apk.png', $this->androidApp->getScreenshot('/upload/app.apk'));
        self::assertSame('root-icon', \file_get_contents($this->root.'/public/upload/app.apk.png'));
    }

    public function testExtractsLargestIconReferencedByTheManifest(): void
    {
        \copy(__DIR__.'/../../../fixtures/apk/ebhs.apk', $this->root.'/public/upload/app.apk');

        $zip = new \ZipArchive();
        self::assertTrue($zip->open($this->root.'/public/upload/app.apk'));
        $expected = $zip->getFromName('res/drawable-hdpi/ebhs.png');
        $zip->close();

        self::assertIsString($expected);
        self::assertSame('/upload/app.apk.png', $this->androidApp->getScreenshot('/upload/app.apk'));
        self::assertSame($expected, \file_get_contents($this->root.'/public/upload/app.apk.png'));
    }

    public function testReturnsNullWithoutAnyIcon(): void
    {
        $this->createApk(['classes.dex' => 'dex']);

        self::assertNull($this->androidApp->getScreenshot('/upload/app.apk'));
        self::assertFileDoesNotExist($this->root.'/public/upload/app.apk.png');
    }

    public function testReturnsExistingScreenshotWithoutReadingTheArchive(): void
    {
        $this->createApk([]);
        $this->filesystem->dumpFile($this->root.'/public/upload/app.apk.png', 'cached');

        self::assertSame('/upload/app.apk.png', $this->androidApp->getScreenshot('/upload/app.apk'));
        self::assertSame('cached', \file_get_contents($this->root.'/public/upload/app.apk.png'));
    }

    public function testFindReturnsOnlyExistingScreenshot(): void
    {
        self::assertNull($this->androidApp->find('/upload/app.apk'));

        $this->filesystem->dumpFile($this->root.'/public/upload/app.apk.png', 'cached');

        self::assertSame('/upload/app.apk.png', $this->androidApp->find('/upload/app.apk'));
    }

    /**
     * @param array<string, string> $entries
     */
    private function createApk(array $entries): void
    {
        $path = $this->root.'/public/upload/app.apk';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
    }
}
