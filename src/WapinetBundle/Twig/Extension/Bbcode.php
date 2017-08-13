<?php

namespace WapinetBundle\Twig\Extension;

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
        return array(
            new \Twig_SimpleFilter('wapinet_bbcode_parse', array($this, 'bbcodeParse'), array('is_safe' => array('html'))),
        );
    }


    /**
     * @param string $text
     * @return string
     */
    public function bbcodeParse($text)
    {
        // хост нужен для email
        $host = $this->requestContext->getHost();
        $xbbcode = new Xbbcode('//'.$host.'/bundles/wapinet/xbbcode');
        $xbbcode->setTagHandler('spoiler', WapinetSpoiler::class);
        $xbbcode->parse($text);

        return $xbbcode->getHtml();
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_bbcode';
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
