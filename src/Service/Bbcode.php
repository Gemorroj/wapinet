<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Routing\RequestContext;
use Xbbcode\Xbbcode;

final readonly class Bbcode
{
    public function __construct(private RequestContext $requestContext)
    {
    }

    public function render(string $text): string
    {
        $xbbcode = new Xbbcode('//'.$this->requestContext->getHost().'/resources/xbbcode');
        $xbbcode->setCodeHighlighting(false);
        $xbbcode->parse($text);

        return $xbbcode->getHtml();
    }
}
