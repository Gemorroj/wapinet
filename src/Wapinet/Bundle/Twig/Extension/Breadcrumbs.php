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
        $out = '';

        $out .= '<div data-role="controlgroup" data-type="horizontal" data-mini="true" class="ui-btn-left">';
        //$out .= '<a href="javascript:history.back();" data-role="button" data-icon="back" data-iconpos="notext">Назад</a>';
        //$out .= '<a href="#menu" data-role="button" data-icon="bars">Меню</a>';
        //$out .= '<a href="#menu" data-role="button" data-icon="bars" data-iconpos="notext">Меню</a>';
        $out .= '<a data-role="button" data-iconpos="notext" data-icon="home" href="' . $this->container->get('router')->generate('index') . '">Главная</a>';

        ksort($options, SORT_NUMERIC);
        foreach ($options as $value) {
            $out .= '<a data-role="button" data-icon="arrow-l" href="' . htmlspecialchars($value['uri']) . '">' . htmlspecialchars($value['title'], ENT_NOQUOTES) . '</a>';
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
