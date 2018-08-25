<?php

namespace App\Twig\Extension;

class Breadcrumbs extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('wapinet_breadcrumbs', [$this, 'getBreadcrumbs'], ['is_safe' => ['html']]),
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

        \ksort($options, \SORT_NUMERIC);

        $out = '<div data-role="navbar" data-iconpos="left"><ul>';
        foreach ($options as $key => $value) {
            $out .= '<li><a class="ui-corner-all" data-icon="arrow-l" href="'.\htmlspecialchars($value['uri']).'">'.\htmlspecialchars($value['title'], \ENT_NOQUOTES).'</a></li>';
        }
        $out .= '</ul></div>';

        return $out;
    }
}
