<?php

namespace App\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Breadcrumbs extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

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
            $out .= '<li><a class="ui-corner-all" data-icon="arrow-l" href="' . \htmlspecialchars($value['uri']) . '">' . \htmlspecialchars($value['title'], \ENT_NOQUOTES) . '</a></li>';
        }
        $out .= '</ul></div>';

        return $out;
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_breadcrumbs';
    }
}
