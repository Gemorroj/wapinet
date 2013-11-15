<?php

namespace Wapinet\Bundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Menu extends \Twig_Extension
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
            new \Twig_SimpleFunction('wapinet_menu', array($this, 'getMenu'), array('is_safe' => array('html'))),
        );
    }

    /**
     * @param array $options
     * @return string
     */
    public function getMenu(array $options = array())
    {
        $router = $this->container->get('router');

        $out = '<ul>';
        $out .= '<li><a href="' . $router->generate('index') . '">Главная</a></li>';
        ksort($options, SORT_NUMERIC);
        foreach ($options as $value) {
            $out .= '<li><a href="' . htmlspecialchars($value['uri']) . '">' . htmlspecialchars($value['title'], ENT_NOQUOTES) . '</a></li>';
        }
        $out .= '</ul>';

        return $out;
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_menu';
    }
}
