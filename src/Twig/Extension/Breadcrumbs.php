<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function htmlspecialchars;
use function ksort;
use const ENT_NOQUOTES;
use const SORT_NUMERIC;

class Breadcrumbs extends AbstractExtension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('wapinet_breadcrumbs', [$this, 'getBreadcrumbs'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param array $options
     *
     * @return string
     */
    public function getBreadcrumbs(array $options = [])
    {
        if (!$options) {
            return  '';
        }

        ksort($options, SORT_NUMERIC);

        $out = '<div data-role="navbar" data-iconpos="left"><ul>';
        foreach ($options as $key => $value) {
            $out .= '<li><a class="ui-corner-all" data-icon="arrow-l" href="'. htmlspecialchars($value['uri']).'">'. htmlspecialchars($value['title'], ENT_NOQUOTES).'</a></li>';
        }
        $out .= '</ul></div>';

        return $out;
    }
}
