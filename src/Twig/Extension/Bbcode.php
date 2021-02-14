<?php

namespace App\Twig\Extension;

use Symfony\Component\Routing\RequestContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Xbbcode\Tag\Spoiler;
use Xbbcode\Xbbcode;

class Bbcode extends AbstractExtension
{
    private RequestContext $requestContext;

    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
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
    /**
     * {@inheritdoc}
     */
    protected function getSpoiler($id)
    {
        return '<input data-inline="true" data-mini="true" class="bb_spoiler" type="button" value="Спойлер" onclick="$(\'#'.$id.'\').toggle(); return false;" />';
    }
}
