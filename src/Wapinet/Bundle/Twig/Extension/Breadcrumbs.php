<?php

namespace Wapinet\Bundle\Twig\Extension;

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
        return array(
            new \Twig_SimpleFunction('wapinet_breadcrumbs', array($this, 'getBreadcrumbs'), array('is_safe' => array('html'))),
        );
    }

    /**
     * @param array $options
     * @return string
     */
    public function getBreadcrumbs(array $options = array())
    {
        if (!$options) {
            return  '';
        }

        $out = '<div data-role="controlgroup" data-type="horizontal" class="breadcrumbs ui-mini ui-field-contain ui-btn-left">';
        //$out .= '<a class="ui-btn ui-icon-home ui-btn-icon-notext ui-corner-all" href="' . $this->container->get('router')->generate('index') . '">Главная</a>';

        ksort($options, SORT_NUMERIC);
        foreach ($options as $key => $value) {
            $out .= '<a class="ui-btn ui-icon-arrow-l ui-btn-icon-left ui-corner-all" href="' . htmlspecialchars($value['uri']) . '">' . htmlspecialchars($value['title'], ENT_NOQUOTES) . '</a>';
        }

        $out .= '</div>';

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
