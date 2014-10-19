<?php

namespace Wapinet\Bundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Wapinet\UserBundle\Entity\Panel as UserPanel;
use Wapinet\UserBundle\Entity\User;

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
        $user = $this->container->get('security.context')->getToken()->getUser();
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
