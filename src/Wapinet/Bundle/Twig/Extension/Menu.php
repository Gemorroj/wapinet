<?php

namespace Wapinet\Bundle\Twig\Extension;

class Menu extends \Twig_Extension
{
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
    public function getMenu(array $options = array(array('uri' => '/', 'title' => 'wapinet.ru')))
    {
        $out = '<div data-role="controlgroup" data-type="horizontal" data-mini="true" class="ui-btn-left">';
        //$out .= '<a href="javascript:history.back();" data-role="button" data-icon="back" data-iconpos="notext">Назад</a>';
        //$out .= '<a href="#menu" data-role="button" data-icon="bars">Меню</a>';
        $out .= '<a href="#menu" data-role="button" data-icon="bars" data-iconpos="notext">Меню</a>';

        ksort($options, SORT_NUMERIC);
        $last = array_pop($options);

        foreach ($options as $value) {
            $out .= '<a data-role="button" data-icon="arrow-l" href="' . htmlspecialchars($value['uri']) . '">' . htmlspecialchars($value['title'], ENT_NOQUOTES) . '</a>';
        }

        $out .= '</div>';
        $out .= '<h2>' . htmlspecialchars($last['title'], ENT_NOQUOTES) . '</h2>';

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
