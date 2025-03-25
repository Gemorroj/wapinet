<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\FileUrlDataTransformer;
use App\Tests\WebTestCaseWapinet;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FileUrlDataTransformerTest extends WebTestCaseWapinet
{
    public static function getOriginalNameDataProvider(): array
    {
        return [
            [
                [],
                'http://example.com/file.zip',
                '/file.zip',
            ],
            [
                ['content-disposition' => ['form-data']],
                'http://example.com/file.zip',
                '/file.zip',
            ],
            [
                ['content-disposition' => ['form-data; name="fieldName"']],
                'http://example.com/file.zip',
                '/file.zip',
            ],
            [
                ['content-disposition' => ['form-data; name="fieldName"; filename="filename.jpg"']],
                'http://example.com/file.zip',
                'filename.jpg',
            ],
            [
                ['content-disposition' => ['attachment; filename*=UTF-8\'\'Na%C3%AFve%20file.txt']],
                'http://example.com/file.zip',
                'Naïve file.txt',
            ],
            [
                ['content-disposition' => ['attachment; filename="EURO rates"; filename*=utf-8\'\'%e2%82%ac%20rates.txt']],
                'http://example.com/file.zip',
                '€ rates.txt',
            ],
            [
                ['content-disposition' => ['attachment; filename="EURO rates"; filename*=iso-8859-1\'en\'%A3%20rates.txt']],
                'http://example.com/file.zip',
                '£ rates.txt',
            ],
            [
                [],
                'http://example.com/',
                'index.html',
            ],
            [
                [],
                'http://example.com',
                'index.html',
            ],
            [
                ['location' => ['/file.zip']],
                'http://example.com',
                '/file.zip',
            ],
        ];
    }

    #[DataProvider('getOriginalNameDataProvider')]
    public function testGetOriginalName(array $headers, string $url, string $expectedFilename): void
    {
        $container = static::getContainer();
        $parameterBag = $container->get(ParameterBagInterface::class);
        $httpClient = $container->get(HttpClientInterface::class);

        $fileUrlDataTransformer = new FileUrlDataTransformer($parameterBag, $httpClient);

        $method = new \ReflectionMethod($fileUrlDataTransformer, 'getOriginalName');
        $method->setAccessible(true);

        $result = $method->invoke($fileUrlDataTransformer, $headers, $url);
        static::assertSame($expectedFilename, $result);
    }
}
