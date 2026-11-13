<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\File;

use App\Service\Api\File\RemoteFileFetcher;
use App\Service\FileSystem\RemoteFileDownloader;
use App\Service\Network\DnsLookup;
use App\Service\Network\OutboundUrlGuard;
use App\Service\Network\SafeHttpFetcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class RemoteFileFetcherTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet_remote_'.\bin2hex(\random_bytes(4));

        if (!\mkdir($this->directory) && !\is_dir($this->directory)) {
            static::fail('Unable to create a temporary directory.');
        }
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->directory.'/*') ?: [] as $file) {
            @\unlink($file);
        }

        @\rmdir($this->directory);
    }

    public function testDownloadsFileWithNameFromContentDisposition(): void
    {
        $client = new MockHttpClient(new MockResponse('hello', [
            'response_headers' => [
                'content-type: text/plain',
                'content-disposition: attachment; filename="remote.txt"',
            ],
        ]));

        $file = $this->fetcher($client)->fetch('https://example.com/download');

        static::assertSame('remote.txt', $file->getClientOriginalName());
        static::assertSame(5, $file->getSize());
        static::assertSame('hello', \file_get_contents($file->getPathname()));
    }

    public function testFallsBackToUrlBasename(): void
    {
        $client = new MockHttpClient(new MockResponse('data'));

        $file = $this->fetcher($client)->fetch('https://example.com/path/archive.zip');

        static::assertSame('archive.zip', $file->getClientOriginalName());
    }

    public function testFollowsRedirect(): void
    {
        $client = new MockHttpClient([
            new MockResponse('', ['http_code' => 302, 'response_headers' => ['location: https://example.com/file.txt']]),
            new MockResponse('data'),
        ]);

        $file = $this->fetcher($client)->fetch('https://example.com/download');

        static::assertSame('data', \file_get_contents($file->getPathname()));
    }

    public function testRejectsNonHttpUrl(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->fetcher(new MockHttpClient())->fetch('file:///etc/passwd');
    }

    public function testRejectsPrivateAddress(): void
    {
        $this->expectException(UnprocessableEntityHttpException::class);

        $this->fetcher(new MockHttpClient())->fetch('http://127.0.0.1/secret');
    }

    private function fetcher(MockHttpClient $client): RemoteFileFetcher
    {
        return new RemoteFileFetcher($this->downloader($client));
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
