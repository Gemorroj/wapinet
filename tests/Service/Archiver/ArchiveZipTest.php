<?php

declare(strict_types=1);

namespace App\Tests\Service\Archiver;

use App\Service\Archiver\ArchiveLimits;
use App\Service\Archiver\ArchiverException;
use App\Service\Archiver\ArchiveZip;
use PHPUnit\Framework\TestCase;

final class ArchiveZipTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir().'/wapinet_zip_'.\bin2hex(\random_bytes(4));
        \mkdir($this->root);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->root.'/*') ?: [] as $path) {
            \is_dir($path) ? $this->removeDirectory($path) : @\unlink($path);
        }

        @\rmdir($this->root);
    }

    public function testAcceptsAValidArchive(): void
    {
        $archive = $this->createZip(['hello.txt' => 'hello', 'nested/inner.txt' => 'inner']);

        self::assertTrue($this->archive()->isValid($archive));
    }

    public function testRejectsParentDirectoryReference(): void
    {
        $archive = $this->createZip(['../escape.txt' => 'escape']);

        $this->expectException(ArchiverException::class);
        $this->expectExceptionMessage('недопустимые пути');

        $this->archive()->isValid($archive);
    }

    public function testRejectsWindowsParentDirectoryReference(): void
    {
        $archive = $this->createZip(['..\\escape.txt' => 'escape']);

        $this->expectException(ArchiverException::class);

        $this->archive()->isValid($archive);
    }

    public function testRejectsAbsolutePath(): void
    {
        $archive = $this->createZip(['/etc/passwd' => 'root']);

        $this->expectException(ArchiverException::class);
        $this->expectExceptionMessage('абсолютные пути');

        $this->archive()->isValid($archive);
    }

    public function testRejectsTooManyFiles(): void
    {
        $archive = $this->createZip(['a.txt' => 'a', 'b.txt' => 'b', 'c.txt' => 'c']);

        $this->expectException(ArchiverException::class);
        $this->expectExceptionMessage('файлов, максимум 2');

        $this->archive(new ArchiveLimits(maxFiles: 2))->isValid($archive);
    }

    public function testRejectsOversizedEntry(): void
    {
        $archive = $this->createZip(['big.txt' => 'abcdef']);

        $this->expectException(ArchiverException::class);
        $this->expectExceptionMessage('Файл в архиве больше');

        $this->archive(new ArchiveLimits(maxFileSize: 3))->isValid($archive);
    }

    public function testRejectsOversizedArchive(): void
    {
        $archive = $this->createZip(['a.txt' => 'aaaa', 'b.txt' => 'bbbb']);

        $this->expectException(ArchiverException::class);
        $this->expectExceptionMessage('Распакованный архив больше');

        $this->archive(new ArchiveLimits(maxTotalSize: 5))->isValid($archive);
    }

    public function testRejectsCorruptedArchive(): void
    {
        $path = $this->root.'/broken.zip';
        \file_put_contents($path, 'not a zip at all');

        self::assertFalse($this->archive()->isValid(new \Symfony\Component\HttpFoundation\File\File($path)));
    }

    public function testExtractEntryNeverWritesOutsideTheTargetDirectory(): void
    {
        $archive = $this->createZip(['../escape.txt' => 'escape', 'ok.txt' => 'ok']);
        $target = $this->root.'/target';
        \mkdir($target);

        $this->archive()->extractEntry($archive, '../escape.txt', $target);

        self::assertFileExists($target.'/escape.txt');
        self::assertFileDoesNotExist($this->root.'/escape.txt');
        self::assertFileDoesNotExist(\dirname($this->root).'/escape.txt');
    }

    public function testCreatesArchiveFromDirectory(): void
    {
        $source = $this->root.'/source';
        \mkdir($source);
        \mkdir($source.'/nested');
        \file_put_contents($source.'/a.txt', 'a');
        \file_put_contents($source.'/nested/b.txt', 'b');

        $archive = $this->archive()->create($source);

        self::assertTrue($this->archive()->isValid($archive));

        $zip = new \ZipArchive();
        self::assertTrue($zip->open($archive->getPathname()));
        self::assertSame('comment', $zip->getArchiveComment());
        self::assertNotFalse($zip->locateName('a.txt'));
        self::assertNotFalse($zip->locateName('nested/b.txt'));
        $zip->close();
    }

    public function testRemovesTemporaryArchiveWhenLimitIsExceeded(): void
    {
        $source = $this->root.'/source';
        \mkdir($source);
        \file_put_contents($source.'/big.txt', 'abcdef');

        try {
            $this->archive(new ArchiveLimits(maxFileSize: 3))->create($source);
            self::fail('Expected the archive limit exception.');
        } catch (ArchiverException) {
        }

        self::assertFileDoesNotExist($source.'.tmp');
    }

    private function archive(?ArchiveLimits $limits = null): ArchiveZip
    {
        return new ArchiveZip($limits ?? new ArchiveLimits(), 'comment');
    }

    /**
     * @param array<string, string> $entries
     */
    private function createZip(array $entries): \Symfony\Component\HttpFoundation\File\File
    {
        $path = $this->root.'/archive'.\bin2hex(\random_bytes(4)).'.zip';
        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        self::assertTrue($zip->close());

        return new \Symfony\Component\HttpFoundation\File\File($path);
    }

    private function removeDirectory(string $directory): void
    {
        foreach (\glob($directory.'/*') ?: [] as $path) {
            \is_dir($path) ? $this->removeDirectory($path) : @\unlink($path);
        }

        @\rmdir($directory);
    }
}
