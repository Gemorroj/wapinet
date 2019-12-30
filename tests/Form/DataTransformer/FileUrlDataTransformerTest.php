<?php

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\FileUrlDataTransformer;
use App\Service\Curl;
use App\Tests\WebTestCaseWapinet;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class FileUrlDataTransformerTest extends WebTestCaseWapinet
{
    public function getOriginalNameDataProvider(): array
    {
        return [
            [
                new ResponseHeaderBag([]),
                'http://example.com/file.zip',
                '/file.zip',
            ],
            [
                new ResponseHeaderBag(['Content-Disposition' => 'form-data']),
                'http://example.com/file.zip',
                '/file.zip',
            ],
            [
                new ResponseHeaderBag(['Content-Disposition' => 'form-data; name="fieldName"']),
                'http://example.com/file.zip',
                '/file.zip',
            ],
            [
                new ResponseHeaderBag(['Content-Disposition' => 'form-data; name="fieldName"; filename="filename.jpg"']),
                'http://example.com/file.zip',
                'filename.jpg',
            ],
            [
                new ResponseHeaderBag(['Content-Disposition' => 'attachment; filename*=UTF-8\'\'Na%C3%AFve%20file.txt']),
                'http://example.com/file.zip',
                'Naïve file.txt',
            ],
            [
                new ResponseHeaderBag(['Content-Disposition' => 'attachment; filename="EURO rates"; filename*=utf-8\'\'%e2%82%ac%20rates.txt']),
                'http://example.com/file.zip',
                '€ rates.txt',
            ],
            [
                new ResponseHeaderBag(['Content-Disposition' => 'attachment; filename="EURO rates"; filename*=iso-8859-1\'en\'%A3%20rates.txt']),
                'http://example.com/file.zip',
                '£ rates.txt',
            ],
            [
                new ResponseHeaderBag([]),
                'http://example.com/',
                'index.html',
            ],
            [
                new ResponseHeaderBag([]),
                'http://example.com',
                'index.html',
            ],
            [
                new ResponseHeaderBag(['Location' => '/file.zip']),
                'http://example.com',
                '/file.zip',
            ],
        ];
    }

    /**
     * @dataProvider getOriginalNameDataProvider
     */
    public function testGetOriginalName(ResponseHeaderBag $headerBag, string $url, string $expectedFilename): void
    {
        static::bootKernel();
        $obj = new FileUrlDataTransformer(
            static::$container->get('parameter_bag'),
            static::$container->get(Curl::class)
        );
        $method = new ReflectionMethod($obj, 'getOriginalName');
        $method->setAccessible(true);

        $result = $method->invoke($obj, $headerBag, $url);
        static::assertSame($expectedFilename, $result);
    }
}
