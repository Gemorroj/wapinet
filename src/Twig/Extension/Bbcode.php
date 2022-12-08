<?php

namespace App\Twig\Extension;

use Symfony\Component\Routing\RequestContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Xbbcode\Tag\Spoiler;
use Xbbcode\Xbbcode;

class Bbcode extends AbstractExtension
{
    public function __construct(private RequestContext $requestContext)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_bbcode_parse', function (string $text): string {
                // хост нужен для email
                $host = $this->requestContext->getHost();
                $xbbcode = new Xbbcode('//'.$host.'/build/resources/xbbcode');
                $xbbcode->setTagHandler('spoiler', WapinetSpoiler::class);
                $xbbcode->parse($text);

                return $xbbcode->getHtml();
            }, ['is_safe' => ['html']]),
        ];
    }
}

class WapinetSpoiler extends Spoiler
{
    protected function getSpoiler($id): string
    {
        return '<input data-inline="true" data-mini="true" class="bb_spoiler" type="button" value="Спойлер" onclick="$(\'#'.$id.'\').toggle(); return false;" />';
    }
}
