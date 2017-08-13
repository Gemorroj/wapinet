<?php

namespace WapinetBundle\Twig\Extension;

class Count extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('wapinet_count', array($this, 'getCount')),
        );
    }

    /**
     * @param int $count
     * @return string
     */
    public function getCount($count)
    {
        return number_format($count, 0, ',', ' ');
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_count';
    }
}
