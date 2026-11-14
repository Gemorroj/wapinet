<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\Tests\WebTestCaseWapinet;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class ArchiverControllerTest extends WebTestCaseWapinet
{
    /** @var list<string> */
    private array $archives = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        $directory = static::getContainer()->getParameter('kernel.tmp_archiver_dir');
        $filesystem = new Filesystem();

        foreach ($this->archives as $archive) {
            $filesystem->remove([
                $directory.\DIRECTORY_SEPARATOR.$archive,
                $directory.\DIRECTORY_SEPARATOR.$archive.'.tmp',
                $directory.\DIRECTORY_SEPARATOR.$archive.'.info.json',
            ]);
        }

        foreach ($this->temporaryFiles as $file) {
            if (\is_file($file)) {
                \unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testCreatesArchiveFromMultipleUploads(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/archiver/create', [], [
            'files' => [
                $this->upload('hello.txt', 'hello'),
                $this->upload('second.txt', 'second'),
            ],
        ]);

        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        $this->archives[] = $data['archive'];

        static::assertMatchesRegularExpression('/^archive[a-f0-9]{16}$/', $data['archive']);
        static::assertSame(11, $data['size']);
        static::assertSame('zip', $data['format']);
        static::assertSame(['hello.txt', 'second.txt'], \array_column($data['items'], 'path'));
    }

    public function testAddsListsDownloadsAndDeletesFile(): void
    {
        $client = static::createClient();
        $archive = $this->createArchive($client);

        $client->request('POST', '/api/archiver/'.$archive.'/files', [], [
            'files' => [
                $this->upload('second.txt', 'second'),
                $this->upload('third.txt', 'third'),
            ],
        ]);
        static::assertResponseIsSuccessful();
        static::assertSame(16, $this->json($client)['size']);

        $client->request('GET', '/api/archiver/'.$archive);
        static::assertResponseIsSuccessful();
        static::assertSame(['hello.txt', 'second.txt', 'third.txt'], \array_column($this->json($client)['items'], 'path'));

        $client->request('GET', '/api/archiver/'.$archive.'/file?'.\http_build_query(['path' => 'second.txt']));
        static::assertResponseIsSuccessful();
        static::assertSame('second', \file_get_contents($this->binaryFile($client)));

        $client->request('GET', '/api/archiver/'.$archive.'/download');
        static::assertResponseIsSuccessful();
        static::assertSame('application/zip', $client->getResponse()->headers->get('Content-Type'));

        $zipPath = $this->store($client->getInternalResponse()->getContent(), 'archive.zip');
        $zip = new \ZipArchive();
        static::assertTrue($zip->open($zipPath));
        static::assertSame(['hello.txt', 'second.txt', 'third.txt'], [$zip->getNameIndex(0), $zip->getNameIndex(1), $zip->getNameIndex(2)]);
        $zip->close();

        $client->request('DELETE', '/api/archiver/'.$archive.'/files?'.\http_build_query(['path' => 'hello.txt']));
        static::assertResponseIsSuccessful();
        static::assertSame(['second.txt', 'third.txt'], \array_column($this->json($client)['items'], 'path'));
    }

    public function testDeletesWholeArchive(): void
    {
        $client = static::createClient();
        $archive = $this->createArchive($client);

        $client->request('DELETE', '/api/archiver/'.$archive);

        static::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/api/archiver/'.$archive);

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testExtractsZipArchive(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/archiver/extract', [], [
            'files' => [$this->zipUpload('source.zip', ['nested/file.txt' => 'content'])],
        ]);

        static::assertResponseIsSuccessful();

        $data = $this->json($client);
        $this->archives[] = $data['archive'];

        static::assertSame(['nested', 'nested/file.txt'], \array_column($data['items'], 'path'));
        static::assertSame(7, $data['size']);
    }

    public function testRejectsMultipleFilesForExtract(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/archiver/extract', [], [
            'files' => [
                $this->zipUpload('first.zip', ['one.txt' => '1']),
                $this->zipUpload('second.zip', ['two.txt' => '2']),
            ],
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRejectsUnsafePath(): void
    {
        $client = static::createClient();
        $archive = $this->createArchive($client);

        $client->request('DELETE', '/api/archiver/'.$archive.'/files?'.\http_build_query(['path' => '../hello.txt']));

        static::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRejectsUnknownArchive(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/archiver/archive0123456789abcdef');

        static::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRejectsEmptySource(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/archiver/create');

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        static::assertSame('Укажите файл или ссылку.', $this->json($client)['detail']);
    }

    public function testRejectsUnsupportedArchive(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/archiver/extract', [], [
            'files' => [$this->upload('plain.txt', 'not an archive')],
        ]);

        static::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function createArchive(KernelBrowser $client): string
    {
        $client->request('POST', '/api/archiver/create', [], [
            'files' => [$this->upload('hello.txt', 'hello')],
        ]);

        static::assertResponseIsSuccessful();

        $archive = $this->json($client)['archive'];
        $this->archives[] = $archive;

        return $archive;
    }

    private function upload(string $name, string $content): UploadedFile
    {
        $path = \sys_get_temp_dir().'/wapinet_upload_'.\bin2hex(\random_bytes(4));
        \file_put_contents($path, $content);
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * @param array<string, string> $entries
     */
    private function zipUpload(string $name, array $entries): UploadedFile
    {
        $path = \sys_get_temp_dir().'/wapinet_zip_'.\bin2hex(\random_bytes(4));
        $zip = new \ZipArchive();
        static::assertTrue($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE));

        foreach ($entries as $entry => $content) {
            static::assertTrue($zip->addFromString($entry, $content));
        }

        static::assertTrue($zip->close());
        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }

    private function binaryFile(KernelBrowser $client): string
    {
        $response = $client->getResponse();

        if (!$response instanceof BinaryFileResponse) {
            static::fail('Expected a binary response.');
        }

        return $response->getFile()->getPathname();
    }

    private function store(string $content, string $name): string
    {
        $path = \sys_get_temp_dir().'/wapinet_store_'.\bin2hex(\random_bytes(4)).'-'.$name;
        \file_put_contents($path, $content);
        $this->temporaryFiles[] = $path;

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function json(KernelBrowser $client): array
    {
        return \json_decode((string) $client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
    }
}
