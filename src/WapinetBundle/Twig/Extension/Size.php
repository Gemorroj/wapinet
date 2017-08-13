<?php

namespace WapinetBundle\Twig\Extension;

class Size extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('wapinet_size', array($this, 'getSize')),
        );
    }

    /**
     * @param int $fileSizeInBytes
     * @return string|null
     */
    public function getSize($fileSizeInBytes)
    {
        $i = -1;
        $byteUnits = array(' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB');
        do {
            $fileSizeInBytes /= 1024;
            $i++;
        } while ($fileSizeInBytes > 1024);


        return \round(\max($fileSizeInBytes, 0.1), 1) . $byteUnits[$i];
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_size';
    }
}
