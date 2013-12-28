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
