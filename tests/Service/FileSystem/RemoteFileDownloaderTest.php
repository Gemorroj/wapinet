<?php

declare(strict_types=1);

namespace App\Tests\Service\FileSystem;

use App\Service\FileSystem\RemoteFileDownloader;
use App\Service\FileSystem\RemoteFileException;
use App\Service\Network\DnsLookup;
use App\Service\Network\OutboundUrlBlockedException;
use App\Service\Network\OutboundUrlGuard;
use App\Service\Network\SafeHttpFetcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class RemoteFileDownloaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_download_'.\bin2hex(\random_bytes(4));
        \mkdir($this->directory, 0o777, true);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->directory.'/*') ?: [] as $file) {
            @\unlink($file);
        }

        @\rmdir($this->directory);
    }

    public static function originalNameDataProvider(): array
    {
        return [
            [[], 'http://example.com/file.zip', 'file.zip'],
            [['content-disposition' => ['form-data']], 'http://example.com/file.zip', 'file.zip'],
            [['content-disposition' => ['form-data; name="fieldName"']], 'http://example.com/file.zip', 'file.zip'],
            [['content-disposition' => ['form-data; name="fieldName"; filename="filename.jpg"']], 'http://example.com/file.zip', 'filename.jpg'],
            [['content-disposition' => ['attachment; filename*=UTF-8\'\'Na%C3%AFve%20file.txt']], 'http://example.com/file.zip', 'Naïve file.txt'],
            [['content-disposition' => ['attachment; filename="EURO rates"; filename*=utf-8\'\'%e2%82%ac%20rates.txt']], 'http://example.com/file.zip', '€ rates.txt'],
            [['content-disposition' => ['attachment; filename="EURO rates"; filename*=iso-8859-1\'en\'%A3%20rates.txt']], 'http://example.com/file.zip', '£ rates.txt'],
            [[], 'http://example.com/', 'index.html'],
            [[], 'http://example.com', 'index.html'],
        ];
    }

    #[DataProvider('originalNameDataProvider')]
    public function testOriginalName(array $headers, string $url, string $expected): void
    {
        $responseHeaders = [];

        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $responseHeaders[] = $name.': '.$value;
            }
        }

        $client = new MockHttpClient(new MockResponse('data', ['response_headers' => $responseHeaders]));

        static::assertSame($expected, $this->downloader($client)->download($url)->getClientOriginalName());
    }

    public function testDownloadsContent(): void
    {
        $client = new MockHttpClient(new MockResponse('hello', [
            'response_headers' => ['content-type: text/plain'],
        ]));

        $file = $this->downloader($client)->download('https://example.com/file.txt');

        static::assertSame('hello', \file_get_contents($file->getPathname()));
        static::assertSame(5, $file->getSize());
        static::assertSame('text/plain', $file->getMimeType());
    }

    public function testFollowsRedirect(): void
    {
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 302, 'response_headers' => ['location: https://example.com/file.txt']]),
            new MockResponse('data'),
        ]);

        $file = $this->downloader($client)->download('https://example.com/download');

        static::assertSame('data', \file_get_contents($file->getPathname()));
    }

    public function testRejectsEmptyFile(): void
    {
        $this->expectException(RemoteFileException::class);
        $this->expectExceptionMessage('Файл по ссылке пустой.');

        $this->downloader(new MockHttpClient(new MockResponse('')))->download('https://example.com/empty.txt');
    }

    public function testRejectsNonHttpUrl(): void
    {
        $this->expectException(RemoteFileException::class);

        $this->downloader(new MockHttpClient())->download('file:///etc/passwd');
    }

    public function testRejectsPrivateAddress(): void
    {
        $this->expectException(OutboundUrlBlockedException::class);

        $this->downloader(new MockHttpClient())->download('http://127.0.0.1/secret');
    }

    private function downloader(MockHttpClient $client): RemoteFileDownloader
    {
        $dns = new MockHttpClient(static fn (): MockResponse => new MockResponse((string) \json_encode([
            'Answer' => [['name' => 'example.com', 'type' => 1, 'data' => '93.184.216.34']],
        ])));

        return new RemoteFileDownloader(
            new SafeHttpFetcher($client, new OutboundUrlGuard(new DnsLookup($dns, new ArrayAdapter()))),
            $this->directory,
        );
    }
}
