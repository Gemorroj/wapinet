<?php

namespace App\Twig\Extension;

use Symfony\Component\Routing\RequestContext;
use Xbbcode\Xbbcode;

class Bbcode extends \Twig_Extension
{
    /**
     * @var RequestContext
     */
    protected $requestContext;

    /**
     * @param RequestContext $requestContext
     */
    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('wapinet_bbcode_parse', function (string $text) : string {
                // хост нужен для email
                $host = $this->requestContext->getHost();
                $xbbcode = new Xbbcode('//'.$host.'/build/app/xbbcode');
                $xbbcode->setTagHandler('spoiler', WapinetSpoiler::class);
                $xbbcode->parse($text);

                return $xbbcode->getHtml();
            }, ['is_safe' => ['html']]),
        ];
    }
}

class WapinetSpoiler extends \Xbbcode\Tag\Spoiler
{
    /**
     * @param string $id
     * @return string
     */
    protected function getSpoiler($id)
    {
        return '<input data-inline="true" data-mini="true" class="bb_spoiler" type="button" value="Спойлер" onclick="$(\'#' . $id . '\').toggle(); return false;" />';
    }
}
