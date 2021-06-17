<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\FileUrlDataTransformer;
use App\Service\Curl;
use App\Tests\WebTestCaseWapinet;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileUrlDataTransformerTest extends WebTestCaseWapinet
{
    public function getOriginalNameDataProvider(): array
    {
        return [
            [
                [],
                'http://example.com/file.zip',
                '/file.zip',
            ],
            [
                ['Content-Disposition' => 'form-data'],
                'http://example.com/file.zip',
                '/file.zip',
            ],
            [
                ['Content-Disposition' => 'form-data; name="fieldName"'],
                'http://example.com/file.zip',
                '/file.zip',
            ],
            [
                ['Content-Disposition' => 'form-data; name="fieldName"; filename="filename.jpg"'],
                'http://example.com/file.zip',
                'filename.jpg',
            ],
            [
                ['Content-Disposition' => 'attachment; filename*=UTF-8\'\'Na%C3%AFve%20file.txt'],
                'http://example.com/file.zip',
                'Naïve file.txt',
            ],
            [
                ['Content-Disposition' => 'attachment; filename="EURO rates"; filename*=utf-8\'\'%e2%82%ac%20rates.txt'],
                'http://example.com/file.zip',
                '€ rates.txt',
            ],
            [
                ['Content-Disposition' => 'attachment; filename="EURO rates"; filename*=iso-8859-1\'en\'%A3%20rates.txt'],
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
                ['Location' => '/file.zip'],
                'http://example.com',
                '/file.zip',
            ],
        ];
    }

    /**
     * @dataProvider getOriginalNameDataProvider
     */
    public function testGetOriginalName(array $headers, string $url, string $expectedFilename): void
    {
        $container = static::getContainer();
        $parameterBag = $container->get(ParameterBagInterface::class);
        $curl = $container->get(Curl::class);

        $fileUrlDataTransformer = new FileUrlDataTransformer($parameterBag, $curl);

        $method = new \ReflectionMethod($fileUrlDataTransformer, 'getOriginalName');
        $method->setAccessible(true);

        $result = $method->invoke($fileUrlDataTransformer, new ResponseHeaderBag($headers), $url);
        static::assertSame($expectedFilename, $result);
    }
}
