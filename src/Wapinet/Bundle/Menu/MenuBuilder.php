<?php

namespace Wapinet\Bundle\Menu;

use Knp\Menu\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;

class MenuBuilder
{
    private $factory;

    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function createMainMenu(Request $request)
    {
        $menu = $this->factory->createItem('root');

        //TODO:дизайн меню
        $route = $request->get('_route');
        $menu->addChild('Current', array('route' => $route));

        $menu->setChildrenAttribute('data-role', 'listview');

        $menu->addChild('Home', array('route' => 'index'));
        $menu->addChild('About Site', array(
            'route' => 'about',
            //'routeParameters' => array('id' => 42)
        ));
        // ... add more children

        return $menu;
    }
}
