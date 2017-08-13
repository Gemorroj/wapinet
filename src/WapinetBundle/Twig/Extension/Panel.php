<?php

namespace WapinetBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use WapinetUserBundle\Entity\Panel as UserPanel;
use WapinetUserBundle\Entity\User;

class Panel extends \Twig_Extension
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
            new \Twig_SimpleFunction('wapinet_panel', array($this, 'getPanel')),
        );
    }

    /**
     * @param array $options
     * @return array
     */
    public function getPanel(array $options = array())
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (\is_object($user) && $user instanceof User) {
            $panel = $user->getPanel();
        } else {
            $panel = new UserPanel();
        }

        return $panel->getIterator();
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_panel';
    }
}
