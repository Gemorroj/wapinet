<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Validator;

use App\Service\Api\Validator\ValidatorRunner;
use CSSValidator\Message\Error as CssError;
use CSSValidator\Message\Warning as CssWarning;
use CSSValidator\Response as CssResponse;
use HTMLValidator\Message\Error as HtmlError;
use HTMLValidator\Message\Warning as HtmlWarning;
use HTMLValidator\Response as HtmlResponse;
use PHPUnit\Framework\TestCase;

final class ValidatorRunnerTest extends TestCase
{
    public function testMapsPhpResult(): void
    {
        $result = ValidatorRunner::fromPhpResult([
            'validity' => false,
            'errors' => [
                ['line' => 4, 'type' => 'Parse error', 'message' => 'Unclosed "("'],
            ],
        ], '<?php if (');

        self::assertFalse($result->valid);
        self::assertSame('<?php if (', $result->source);
        self::assertSame(\PHP_VERSION, $result->phpVersion);
        self::assertCount(1, $result->messages);
        self::assertSame('error', $result->messages[0]->severity);
        self::assertSame('Parse error', $result->messages[0]->type);
        self::assertSame(4, $result->messages[0]->line);
        self::assertSame('Unclosed "("', $result->messages[0]->message);
    }

    public function testMapsHtmlResponse(): void
    {
        $response = new HtmlResponse()
            ->setValid(false)
            ->setType('text/html')
            ->setEncoding('UTF-8')
            ->setErrors([
                new HtmlError([
                    'firstLine' => 2,
                    'lastLine' => 3,
                    'firstColumn' => 4,
                    'lastColumn' => 5,
                    'message' => 'Element p not allowed',
                    'extract' => '<p>',
                ]),
            ])
            ->setWarnings([
                new HtmlWarning([
                    'lastLine' => 7,
                    'lastColumn' => 8,
                    'message' => 'Consider adding lang',
                ]),
            ]);

        $result = ValidatorRunner::fromHtmlResponse($response, '<html></html>');

        self::assertFalse($result->valid);
        self::assertSame('<html></html>', $result->source);
        self::assertSame('text/html', $result->documentType);
        self::assertSame('UTF-8', $result->encoding);
        self::assertCount(2, $result->messages);
        self::assertSame('error', $result->messages[0]->severity);
        self::assertSame(2, $result->messages[0]->line);
        self::assertSame(4, $result->messages[0]->column);
        self::assertSame('<p>', $result->messages[0]->extract);
        self::assertSame('warning', $result->messages[1]->severity);
        self::assertSame(7, $result->messages[1]->line);
        self::assertSame(8, $result->messages[1]->column);
    }

    public function testMapsCssResponse(): void
    {
        $document = new \DOMDocument();
        $document->loadXML(<<<'XML'
            <cssvalidationresponse>
                <uri>https://example.com/style.css</uri>
                <error>
                    <line>3</line>
                    <errortype>parse-error</errortype>
                    <message>Unknown property</message>
                </error>
                <warning>
                    <line>5</line>
                    <type>warning</type>
                    <message>Property is vendor-specific</message>
                    <level>0</level>
                </warning>
            </cssvalidationresponse>
            XML);

        $errorNode = $document->getElementsByTagName('error')->item(0);
        $warningNode = $document->getElementsByTagName('warning')->item(0);
        self::assertInstanceOf(\DOMElement::class, $errorNode);
        self::assertInstanceOf(\DOMElement::class, $warningNode);

        $response = new CssResponse()
            ->setValid(false)
            ->setCssLevel('css21')
            ->setErrors([new CssError($errorNode)])
            ->setWarnings([new CssWarning($warningNode)]);

        $result = ValidatorRunner::fromCssResponse($response, 'body { color: red; }');

        self::assertFalse($result->valid);
        self::assertSame('body { color: red; }', $result->source);
        self::assertSame('css21', $result->profile);
        self::assertCount(2, $result->messages);
        self::assertSame('error', $result->messages[0]->severity);
        self::assertSame('parse-error', $result->messages[0]->type);
        self::assertSame(3, $result->messages[0]->line);
        self::assertSame('Unknown property', $result->messages[0]->message);
        self::assertSame('warning', $result->messages[1]->severity);
        self::assertSame('warning', $result->messages[1]->type);
        self::assertSame(5, $result->messages[1]->line);
    }
}
