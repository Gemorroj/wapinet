<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Breadcrumbs extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_breadcrumbs', $this->getBreadcrumbs(...), ['is_safe' => ['html']]),
        ];
    }

    public function getBreadcrumbs(array $options = []): string
    {
        if (!$options) {
            return '';
        }

        \ksort($options, \SORT_NUMERIC);

        $out = '<div data-role="navbar" data-iconpos="left"><ul>';
        foreach ($options as $value) {
            $out .= '<li><a class="ui-corner-all" data-icon="arrow-l" href="'.\htmlspecialchars($value['uri']).'">'.\htmlspecialchars($value['title'], \ENT_NOQUOTES).'</a></li>';
        }
        $out .= '</ul></div>';

        return $out;
    }
}
