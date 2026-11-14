<?php

declare(strict_types=1);

namespace App\Tests\Service\FileContent;

use App\Service\FileContent\SourceException;
use App\Service\FileContent\SourceLoader;
use App\Service\FileSystem\RemoteFileDownloader;
use App\Service\Network\DnsLookup;
use App\Service\Network\OutboundUrlGuard;
use App\Service\Network\SafeHttpFetcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class SourceLoaderTest extends TestCase
{
    public function testLoadsCodeFromUrl(): void
    {
        $loader = $this->loader('<?php echo 1;');

        self::assertSame('<?php echo 1;', $loader->load(null, 'http://8.8.8.8/a.php', 1024));
    }

    public function testRejectsTooLargeUrlFile(): void
    {
        $loader = $this->loader(\str_repeat('a', 64));

        $this->expectException(SourceException::class);
        $this->expectExceptionMessage('Файл по ссылке слишком большой.');

        $loader->load(null, 'http://8.8.8.8/a.php', 16);
    }

    private function loader(string $content): SourceLoader
    {
        $httpClient = new MockHttpClient(new MockResponse($content, [
            'response_headers' => ['content-type' => 'text/plain'],
        ]));
        $dns = new DnsLookup(new MockHttpClient(), new ArrayAdapter());
        $fetcher = new SafeHttpFetcher($httpClient, new OutboundUrlGuard($dns));
        $downloader = new RemoteFileDownloader($fetcher, \sys_get_temp_dir());

        return new SourceLoader($downloader);
    }
}
