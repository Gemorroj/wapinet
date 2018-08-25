<?php

namespace App\Twig\Extension;

class Size extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('wapinet_size', [$this, 'getSize']),
        ];
    }

    /**
     * @param int|float|null $fileSizeInBytes
     *
     * @return string|null
     */
    public function getSize($fileSizeInBytes)
    {
        if (null === $fileSizeInBytes) {
            return '';
        }

        $i = -1;
        $byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
        do {
            $fileSizeInBytes /= 1024;
            ++$i;
        } while ($fileSizeInBytes > 1024);

        return \round(\max($fileSizeInBytes, 0.1), 1).$byteUnits[$i];
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
