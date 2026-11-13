<?php

namespace App\Twig\Extension;

use Symfony\Component\Routing\RequestContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Xbbcode\Xbbcode;

class Bbcode extends AbstractExtension
{
    public function __construct(private readonly RequestContext $requestContext)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_bbcode_parse', function (string $text): string {
                // хост нужен для email
                $host = $this->requestContext->getHost();
                $xbbcode = new Xbbcode('//'.$host.'/resources/xbbcode');
                $xbbcode->setCodeHighlighting(false);
                $xbbcode->parse($text);

                return $xbbcode->getHtml();
            }, ['is_safe' => ['html']]),
        ];
    }
}
