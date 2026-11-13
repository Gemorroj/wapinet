<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Archiver;

use App\Service\Api\Archiver\Archiver;
use App\Service\Archiver\Archive7z;
use App\Service\Archiver\ArchiveLimits;
use App\Service\Archiver\ArchiveZip;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class ArchiverTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = \sys_get_temp_dir().'/wapinet_archiver_'.\bin2hex(\random_bytes(4));
        \mkdir($this->root.'/tmp', 0o777, true);
        \mkdir($this->root.'/archiver', 0o777, true);
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->root);
    }

    public function testCreatesArchiveWithUploadedFile(): void
    {
        $response = $this->archiver()->create([$this->upload('hello.txt', 'hello')]);

        static::assertMatchesRegularExpression('/^archive[a-f0-9]{16}$/', $response->archive);
        static::assertSame(5, $response->size);
        static::assertSame('zip', $response->format);
        static::assertCount(1, $response->items);
        static::assertSame('hello.txt', $response->items[0]->path);
        static::assertFalse($response->items[0]->isDirectory);
        static::assertSame('hello', \file_get_contents($this->root.'/archiver/'.$response->archive.'/hello.txt'));
    }

    public function testCreatesArchiveWithMultipleFiles(): void
    {
        $response = $this->archiver()->create([
            $this->upload('one.txt', '1'),
            $this->upload('two.txt', '22'),
            $this->upload('three.txt', '333'),
        ]);

        static::assertSame(['one.txt', 'three.txt', 'two.txt'], \array_column($response->items, 'path'));
        static::assertSame(6, $response->size);
    }

    public function testAddsMultipleFilesAndRemovesOne(): void
    {
        $archiver = $this->archiver();
        $archive = $archiver->create([$this->upload('one.txt', '1')])->archive;

        $response = $archiver->add($archive, [
            $this->upload('two.txt', '22'),
            $this->upload('three.txt', '333'),
        ]);

        static::assertSame(['one.txt', 'three.txt', 'two.txt'], \array_column($response->items, 'path'));
        static::assertSame(6, $response->size);

        $response = $archiver->remove($archive, 'one.txt');

        static::assertSame(['three.txt', 'two.txt'], \array_column($response->items, 'path'));
        static::assertSame(5, $response->size);
    }

    public function testDeletesWholeArchive(): void
    {
        $archiver = $this->archiver();
        $archive = $archiver->create([$this->upload('one.txt', '1')])->archive;

        static::assertDirectoryExists($this->root.'/archiver/'.$archive);

        $archiver->delete($archive);

        static::assertDirectoryDoesNotExist($this->root.'/archiver/'.$archive);

        $this->expectException(NotFoundHttpException::class);
        $archiver->response($archive);
    }

    public function testCreatesZipArchive(): void
    {
        $archiver = $this->archiver();
        $archive = $archiver->create([$this->upload('one.txt', '1')])->archive;
        $archiver->add($archive, [$this->upload('two.txt', '22')]);

        $file = $archiver->zip($archive);
        $zip = new \ZipArchive();
        static::assertTrue($zip->open($file->getPathname()));

        static::assertSame(['one.txt', 'two.txt'], [$zip->getNameIndex(0), $zip->getNameIndex(1)]);
        static::assertSame('Wapinet test', $zip->getArchiveComment());
        static::assertSame('22', $zip->getFromName('two.txt'));
        $zip->close();

        @\unlink($file->getPathname());
    }

    public function testExtractsZipArchive(): void
    {
        $source = $this->zipFile('source.zip', [
            'root.txt' => 'root',
            'nested/deep.txt' => 'deep',
        ]);

        $response = $this->archiver()->extract($source);

        static::assertSame(['nested', 'nested/deep.txt', 'root.txt'], \array_column($response->items, 'path'));
        static::assertSame(8, $response->size);
        static::assertSame('zip', $response->format);
        static::assertNotNull($response->sourceSize);
    }

    public function testRejectsUnsupportedArchive(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->archiver()->extract($this->upload('plain.txt', 'not an archive'));
    }

    public function testRejectsTraversalEntryInZip(): void
    {
        $source = $this->zipFile('evil.zip', ['../evil.txt' => 'evil']);

        $this->expectException(UnprocessableEntityHttpException::class);

        $this->archiver()->extract($source);
    }

    public function testRejectsTotalSizeLimitOnExtract(): void
    {
        $limits = new ArchiveLimits(maxTotalSize: 50, minFreeSpace: 0);
        $source = $this->zipFile('big.zip', ['big.txt' => \str_repeat('a', 100)]);

        $this->expectException(UnprocessableEntityHttpException::class);

        $this->archiver($limits)->extract($source);
    }

    public function testRejectsFileSizeLimit(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->archiver(new ArchiveLimits(maxFileSize: 3))->create([$this->upload('big.txt', 'hello')]);
    }

    public function testRejectsTotalSizeLimitOnAdd(): void
    {
        $archiver = $this->archiver(new ArchiveLimits(maxTotalSize: 6));
        $archive = $archiver->create([$this->upload('one.txt', 'hello')])->archive;

        $this->expectException(UnprocessableEntityHttpException::class);

        $archiver->add($archive, [$this->upload('two.txt', 'hello')]);
    }

    public function testRejectsFileCountLimitForBatch(): void
    {
        $archiver = $this->archiver(new ArchiveLimits(maxFiles: 2));

        $this->expectException(UnprocessableEntityHttpException::class);

        $archiver->create([
            $this->upload('one.txt', '1'),
            $this->upload('two.txt', '2'),
            $this->upload('three.txt', '3'),
        ]);
    }

    public function testRejectsFileCountLimitOnAdd(): void
    {
        $archiver = $this->archiver(new ArchiveLimits(maxFiles: 1));
        $archive = $archiver->create([$this->upload('one.txt', '1')])->archive;

        $this->expectException(UnprocessableEntityHttpException::class);

        $archiver->add($archive, [$this->upload('two.txt', '2')]);
    }

    public function testRejectsUnknownArchive(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->archiver()->response('archive'.\bin2hex(\random_bytes(8)));
    }

    public function testRejectsInvalidArchiveToken(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->archiver()->response('../etc');
    }

    public function testRejectsTraversalPath(): void
    {
        $archiver = $this->archiver();
        $archive = $archiver->create([$this->upload('one.txt', '1')])->archive;

        $this->expectException(AccessDeniedHttpException::class);

        $archiver->remove($archive, '../one.txt');
    }

    public function testRejectsMissingEntry(): void
    {
        $archiver = $this->archiver();
        $archive = $archiver->create([$this->upload('one.txt', '1')])->archive;

        $this->expectException(NotFoundHttpException::class);

        $archiver->filePath($archive, 'missing.txt');
    }

    private function archiver(?ArchiveLimits $limits = null): Archiver
    {
        $limits ??= new ArchiveLimits(minFreeSpace: 0);

        return new Archiver(
            new ArchiveZip($limits, 'Wapinet test'),
            new Archive7z($limits, '7z'),
            $limits,
            new Filesystem(),
            $this->root.'/archiver',
            $this->root.'/tmp',
        );
    }

    private function upload(string $name, string $content): UploadedFile
    {
        $path = $this->root.'/tmp/'.\bin2hex(\random_bytes(4)).'-'.\basename($name);
        \file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * @param array<string, string> $entries
     */
    private function zipFile(string $name, array $entries): UploadedFile
    {
        $path = $this->root.'/tmp/'.$name;
        $zip = new \ZipArchive();
        static::assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        foreach ($entries as $entry => $content) {
            static::assertTrue($zip->addFromString($entry, $content), 'Unable to add '.$entry);
        }

        static::assertTrue($zip->close());

        return new UploadedFile($path, $name, null, null, true);
    }
}


