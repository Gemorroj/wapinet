<?php

namespace Wapinet\Bundle\Twig\Extension;

use Xbbcode\Xbbcode;

class Bbcode extends \Twig_Extension
{
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
        $xbbcode = new Xbbcode($text, '/bundles/wapinet/xbbcode');
        $xbbcode->setTagHandler('spoiler', 'Wapinet\Bundle\Twig\Extension\WapinetSpoiler');

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
        return '<input data-inline="true" class="bb_spoiler" type="button" value="Спойлер" onclick="$(\'#' . $id . '\').toggle();" />';
    }
}
